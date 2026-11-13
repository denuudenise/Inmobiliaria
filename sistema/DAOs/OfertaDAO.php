<?php
declare(strict_types=1);
final class OfertaDAO
{
 public function __construct(private PDO $db) {}
 public function normalize(array $data, bool $names=false): array
 {
  if (!isset($data['ofertas_form'])) return $data;
  $input=$data['ofertas']??[]; $offers=[];
  if (!is_array($input)) throw new RuntimeException('Operaciones inválidas.');
  $types=$this->db->query('SELECT * FROM tipos_transaccion ORDER BY id_tipo_transaccion')->fetchAll();
  foreach($types as $type){
   $id=(int)$type['id_tipo_transaccion'];$row=$input[$id]??[];
   if(!is_array($row))throw new RuntimeException('Operación inválida.');
   if(empty($row['activa']))continue;
   $price=(string)($row['precio']??'');$currency=(string)($row['moneda']??'');
   if(!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D',$price) || (float)$price<=0 || (float)$price>9999999999.99 || !in_array($currency,['ARS','USD'],true))throw new RuntimeException('Ingresá un precio mayor a cero y moneda para cada operación seleccionada.');
   $minimum=null;
   if($id===1){
    $minimum=(string)($row['precio_minimo']??'');
    if(!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D',$minimum)||(float)$minimum<=0||(float)$minimum>(float)$price)throw new RuntimeException('Indicá un monto mínimo de venta mayor a cero y no superior al precio publicado.');
   }
   $offers[$id]=['precio'=>$price,'moneda'=>$currency,'nombre'=>$type['nombre'],'precio_minimo'=>$minimum];
  }
  if(!$offers)throw new RuntimeException('Seleccioná al menos una operación: venta, alquiler o alquiler temporario.');
  $first=array_key_first($offers);$data['operacion']=$names?$offers[$first]['nombre']:$first;$data['precio']=$offers[$first]['precio'];$data['moneda']=$offers[$first]['moneda'];
  $data['ofertas_normalizadas']=$offers;
  return $data;
 }
 public function read(string $kind,int $id): array
 {
  [$table,$key]=$kind==='solicitud'?['solicitud_ofertas','id_solicitud']:['propiedad_ofertas','id_propiedad'];
  $q=$this->db->prepare("SELECT o.*,t.nombre FROM $table o JOIN tipos_transaccion t ON t.id_tipo_transaccion=o.id_tipo_transaccion WHERE o.$key=? ORDER BY o.id_tipo_transaccion");$q->execute([$id]);$rows=$q->fetchAll();
  if (!$rows && $kind==='solicitud') { $q=$this->db->prepare('SELECT s.id_tipo_transaccion,s.precio_estimado AS precio,s.moneda,t.nombre FROM solicitudes_publicacion s JOIN tipos_transaccion t ON t.id_tipo_transaccion=s.id_tipo_transaccion WHERE s.id_solicitud=? AND s.precio_estimado>0');$q->execute([$id]);$rows=$q->fetchAll(); }
  return $rows;
 }
 public function features(string $kind,int $id): array
 {
  [$table,$key]=$kind==='solicitud'?['solicitud_caracteristicas','id_solicitud']:['propiedad_caracteristicas','id_propiedad'];
  $q=$this->db->prepare("SELECT c.* FROM $table pc JOIN caracteristicas c ON c.id_caracteristica=pc.id_caracteristica WHERE pc.$key=? ORDER BY c.nombre");$q->execute([$id]);return $q->fetchAll();
 }
 public function save(string $kind,int $id,array $data): void
 {
  if(!$this->db->inTransaction())throw new LogicException('Se requiere una transacción.');
  [$table,$key,$features]=$kind==='solicitud'?['solicitud_ofertas','id_solicitud','solicitud_caracteristicas']:['propiedad_ofertas','id_propiedad','propiedad_caracteristicas'];
  if(isset($data['ofertas_form'])){
   $normalized=$this->normalize($data);$offers=$normalized['ofertas_normalizadas'];
   $this->db->prepare("DELETE FROM $table WHERE $key=?")->execute([$id]);
   foreach($offers as $type=>$offer)$this->db->prepare("INSERT INTO $table ($key,id_tipo_transaccion,precio,moneda,precio_minimo) VALUES (?,?,?,?,?)")->execute([$id,$type,$offer['precio'],$offer['moneda'],$offer['precio_minimo']]);
  }
  if(isset($data['caracteristicas_form'])){
   $selected=$data['caracteristicas']??[];if(!is_array($selected))throw new RuntimeException('Características inválidas.');
   $allowed=array_map('strval',$this->db->query('SELECT id_caracteristica FROM caracteristicas')->fetchAll(PDO::FETCH_COLUMN));
   foreach($selected as $feature)if(!is_scalar($feature)||!in_array((string)$feature,$allowed,true))throw new RuntimeException('Característica inválida.');
   $this->db->prepare("DELETE FROM $features WHERE $key=?")->execute([$id]);
   foreach(array_unique($selected) as $feature)$this->db->prepare("INSERT INTO $features ($key,id_caracteristica) VALUES (?,?)")->execute([$id,$feature]);
  }
 }
 public function approve(int $request,int $property): void
 {
  $offers=$this->read('solicitud',$request);
  if($offers){$this->db->prepare('DELETE FROM propiedad_ofertas WHERE id_propiedad=?')->execute([$property]);foreach($offers as $o)$this->db->prepare('INSERT INTO propiedad_ofertas (id_propiedad,id_tipo_transaccion,precio,moneda,precio_minimo) VALUES (?,?,?,?,?)')->execute([$property,$o['id_tipo_transaccion'],$o['precio'],$o['moneda'],$o['precio_minimo']??null]);}
  $this->db->prepare('INSERT INTO propiedad_caracteristicas (id_propiedad,id_caracteristica) SELECT ?,id_caracteristica FROM solicitud_caracteristicas WHERE id_solicitud=?')->execute([$property,$request]);
 }
}
