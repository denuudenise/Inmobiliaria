<?php
declare(strict_types=1);

final class GestionDAO
{
    public function __construct(private PDO $db) {}

    public function moderate(int $id, string $action, array $user, string $reason, int $agent): void
    {
        $this->db->beginTransaction();
        try {
            $s=$this->db->prepare('SELECT * FROM solicitudes_publicacion WHERE id_solicitud=? FOR UPDATE');
            $s->execute([$id]); $r=$s->fetch();
            if (!$r) throw new RuntimeException('Solicitud no encontrada.');
            if ($action==='asignar') {
                if ($user['role']!=='Administrador') {
                    if ($r['id_agente'] && (int)$r['id_agente']!==$user['id']) throw new RuntimeException('La solicitud ya tiene un agente.');
                    $agent=$user['id'];
                }
                if (!query_rows('SELECT id_usuario FROM usuarios WHERE id_usuario=? AND id_rol=2 AND activo=1',[$agent])) throw new RuntimeException('Seleccioná un agente activo.');
                execute_sql('UPDATE solicitudes_publicacion SET id_agente=? WHERE id_solicitud=?',[$agent,$id]);
            } else {
                if ($user['role']!=='Administrador') throw new RuntimeException('Solo un administrador puede aprobar o rechazar.');
                if ((int)$r['id_estado_verificacion']!==2) throw new RuntimeException('Esta solicitud ya fue resuelta.');
                if ($action==='rechazar') {
                    if (mb_strlen($reason)<10) throw new RuntimeException('Explicá el motivo del rechazo (al menos 10 caracteres).');
                    execute_sql('UPDATE solicitudes_publicacion SET id_estado_verificacion=4,motivo_rechazo=? WHERE id_solicitud=?',[$reason,$id]);
                } elseif ($action==='aprobar') {
                    $agent=(int)($r['id_agente']??0);
                    if (!$agent || !query_rows('SELECT id_usuario FROM usuarios WHERE id_usuario=? AND id_rol=2 AND activo=1',[$agent])) throw new RuntimeException('Asigná un agente activo antes de aprobar.');
                    if ((float)$r['precio_estimado']<=0) throw new RuntimeException('La solicitud necesita un precio mayor a cero antes de publicarse.');
                    execute_sql('INSERT INTO propiedades (id_tipo_propiedad,id_tipo_transaccion,titulo,direccion,localidad,precio,moneda,descripcion,superficie_total_m2,ambientes,dormitorios,banos,antiguedad_anos,id_agente_creador,id_admin_verificador,id_estado_verificacion,id_estado_comercial,fecha_verificacion,id_solicitud) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,3,1,NOW(),?)',[$r['id_tipo_propiedad'],$r['id_tipo_transaccion'],$r['titulo'],$r['direccion'],$r['localidad'],$r['precio_estimado'],$r['moneda'],$r['descripcion'],$r['superficie_total_m2'],$r['ambientes'],$r['dormitorios'],$r['banos'],$r['antiguedad_anos'],$agent,$user['id'],$id]);
                    $property=(int)$this->db->lastInsertId();
                    (new ExpedienteDAO($this->db))->link($id,$property);
                    (new ModeloCompartidoDAO($this->db))->client((int)$r['id_cliente'],'Propietario');
                    execute_sql('UPDATE propiedades SET id_propietario=? WHERE id_propiedad=?',[$r['id_cliente'],$property]);
                    (new ModeloCompartidoDAO($this->db))->property($property,(int)$r['id_cliente']);
                    (new OfertaDAO($this->db))->approve($id,$property);
                    execute_sql('INSERT INTO propiedad_multimedia (id_propiedad,url_archivo,tipo) SELECT ?,url_archivo,tipo FROM solicitud_multimedia WHERE id_solicitud=?',[$property,$id]);
                    execute_sql('UPDATE solicitudes_publicacion SET id_estado_verificacion=3,motivo_rechazo=NULL WHERE id_solicitud=?',[$id]);
                } else throw new RuntimeException('Acción inválida.');
            }
            $this->db->commit();
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }

    public function saveProperty(array $data, array $user): void
    {
        $data=(new OfertaDAO($this->db))->normalize($data);
        $id=(int)($data['id']??0);
        $title=trim($data['titulo']??''); $address=trim($data['direccion']??''); $city=trim($data['localidad']??'');
        if ($title==='' || mb_strlen($title)>200 || $address==='' || mb_strlen($address)>255 || $city==='' || mb_strlen($city)>100 || trim($data['descripcion']??'')==='') throw new RuntimeException('Revisá título, dirección, localidad y descripción.');
        if (!is_numeric($data['precio']??'') || (float)$data['precio']<=0 || (float)$data['precio']>9999999999 || !is_numeric($data['superficie']??'') || (float)$data['superficie']<=0 || (float)$data['superficie']>999999) throw new RuntimeException('Ingresá precio y superficie válidos.');
        foreach (['ambientes','dormitorios','banos'] as $field) if (filter_var($data[$field]??'',FILTER_VALIDATE_INT)===false || (int)$data[$field]<0 || (int)$data[$field]>100) throw new RuntimeException('Los ambientes, dormitorios y baños deben ser enteros entre 0 y 100.');
        if (!in_array($data['moneda']??'', ['USD','ARS'],true) || !query_rows('SELECT 1 FROM tipos_propiedad WHERE id_tipo_propiedad=?',[(int)($data['tipo']??0)]) || !query_rows('SELECT 1 FROM tipos_transaccion WHERE id_tipo_transaccion=?',[(int)($data['operacion']??0)])) throw new RuntimeException('Tipo, operación o moneda inválidos.');
        $paths=[];
        $expediente=new ExpedienteDAO($this->db);
        $this->db->beginTransaction();
        try {
            $old=null;
            if ($id) {
                $q=$this->db->prepare('SELECT * FROM propiedades WHERE id_propiedad=? FOR UPDATE'); $q->execute([$id]); $old=$q->fetch();
                if (!$old || ($user['role']!=='Administrador' && (int)$old['id_agente_creador']!==$user['id'])) throw new RuntimeException('No podés editar esta propiedad.');
                if (in_array((int)$old['id_estado_comercial'],[3,4],true)) throw new RuntimeException('Una operación cerrada no se edita desde el catálogo.');
            }
            $agent=$user['role']==='Administrador'?(int)($data['agente']??0):$user['id'];
            if (!query_rows('SELECT id_usuario FROM usuarios WHERE id_usuario=? AND id_rol=2 AND activo=1',[$agent])) throw new RuntimeException('Seleccioná un agente activo.');
            $verification=$user['role']==='Administrador'?(int)($data['verificacion']??2):2;
            $commercial=(int)($data['estado']??1);
            if (!in_array($verification,[2,3,4,5],true) || !in_array($commercial,[1,2,5,6],true)) throw new RuntimeException('Estado inválido.');
            $values=[$title,$address,$city,$data['precio'],$data['moneda'],$data['descripcion'],$data['superficie'],$data['ambientes'],$data['dormitorios'],$data['banos'],$data['tipo'],$data['operacion'],$agent,$verification,$commercial,$user['role']==='Administrador'?$user['id']:null];
            $fields='titulo=?,direccion=?,localidad=?,precio=?,moneda=?,descripcion=?,superficie_total_m2=?,ambientes=?,dormitorios=?,banos=?,id_tipo_propiedad=?,id_tipo_transaccion=?,id_agente_creador=?,id_estado_verificacion=?,id_estado_comercial=?,id_admin_verificador=?';
            if ($id) execute_sql('UPDATE propiedades SET '.$fields.' WHERE id_propiedad=?',[...$values,$id]);
            else { execute_sql('INSERT INTO propiedades SET '.$fields,$values); $id=(int)$this->db->lastInsertId(); }
            (new ModeloCompartidoDAO($this->db))->property($id,(int)$user['id']);
            (new OfertaDAO($this->db))->save('propiedad',$id,$data);
            $expediente->save($id,$old['id_solicitud']??null,(int)$user['id'],$data);
            $paths=Archivos::images('fotos');
            foreach ($paths as $path) execute_sql("INSERT INTO propiedad_multimedia (id_propiedad,url_archivo,tipo) VALUES (?,?,'Imagen')",[$id,$path]);
            $this->db->commit();
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); Archivos::remove($paths); $expediente->cleanup(); throw $e; }
    }

    public function closeOperation(array $data, array $user): void
    {
        $selection=explode(':',(string)($data['propiedad']??''));
        $data['propiedad']=$selection[0];
        if (isset($selection[1])) $data['operacion']=(int)$selection[1];
        $this->db->beginTransaction();
        try {
            $q=$this->db->prepare('SELECT * FROM propiedades WHERE id_propiedad=? FOR UPDATE'); $q->execute([(int)($data['propiedad']??0)]); $p=$q->fetch();
            if (!$p || ($user['role']!=='Administrador' && (int)$p['id_agente_creador']!==$user['id'])) throw new RuntimeException('Propiedad no disponible para tu usuario.');
            if ((int)$p['id_estado_verificacion']!==3 || !in_array((int)$p['id_estado_comercial'],[1,2,5],true)) throw new RuntimeException('La propiedad debe estar aprobada y disponible, reservada o en trámite.');
            $operation=(int)($data['operacion']??$p['id_tipo_transaccion']);
            $q=$this->db->prepare('SELECT * FROM propiedad_ofertas WHERE id_propiedad=? AND id_tipo_transaccion=?');$q->execute([$p['id_propiedad'],$operation]);$offer=$q->fetch();
            if (!$offer) throw new RuntimeException('La operación elegida no está disponible para esta propiedad.');
            $p['id_tipo_transaccion']=$operation; $p['moneda']=$offer['moneda'];
            $client=(int)($data['cliente']??0); $amount=$data['monto']??'';
            if (!query_rows('SELECT id_usuario FROM usuarios WHERE id_usuario=? AND id_rol=3 AND activo=1',[$client]) || !is_numeric($amount) || (float)$amount<=0 || (float)$amount>9999999999) throw new RuntimeException('Revisá cliente e importe.');
            if ((int)$p['id_tipo_transaccion']===1) {
                execute_sql('INSERT INTO propiedades_cerradas (id_propiedad,id_cliente_comprador,id_agente_cierre,precio_final,id_tipo_transaccion,moneda) VALUES (?,?,?,?,?,?)',[$p['id_propiedad'],$client,$p['id_agente_creador'],$amount,$p['id_tipo_transaccion'],$p['moneda']]); $state=3;
            } else {
                $start=$data['inicio']??''; $end=$data['fin']??'';
                foreach ([$start,$end] as $date) { $d=DateTime::createFromFormat('!Y-m-d',$date); if (!$d || $d->format('Y-m-d')!==$date) throw new RuntimeException('Ingresá fechas válidas para el alquiler.'); }
                if ($end<=$start) throw new RuntimeException('El fin debe ser posterior al inicio.');
                execute_sql("INSERT INTO alquileres (id_propiedad,id_cliente,id_agente,monto_mensual,fecha_inicio,fecha_fin,estado,moneda,id_tipo_transaccion) VALUES (?,?,?,?,?,?,'Activo',?,?)",[$p['id_propiedad'],$client,$p['id_agente_creador'],$amount,$start,$end,$p['moneda'],$operation]); $state=4;
            }
            (new ModeloCompartidoDAO($this->db))->client($client,$state===3?'Comprador':'Inquilino');
            execute_sql('UPDATE propiedades SET id_estado_comercial=? WHERE id_propiedad=?',[$state,$p['id_propiedad']]);
            $this->db->commit();
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }
}
