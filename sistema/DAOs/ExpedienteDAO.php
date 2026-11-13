<?php
declare(strict_types=1);

final class ExpedienteDAO
{
    private array $created=[];
    public function __construct(private PDO $db) {}

    public function context(string $kind, int $id, array $user, bool $lock=false): array
    {
        if (!in_array($kind,['propiedad','solicitud'],true) || $id<1) throw new RuntimeException('Ficha no disponible.');
        $sql=$kind==='propiedad'
            ? 'SELECT p.*,s.id_cliente AS solicitante FROM propiedades p LEFT JOIN solicitudes_publicacion s ON s.id_solicitud=p.id_solicitud WHERE p.id_propiedad=?'
            : 'SELECT s.*,p.id_propiedad FROM solicitudes_publicacion s LEFT JOIN propiedades p ON p.id_solicitud=s.id_solicitud WHERE s.id_solicitud=?';
        $q=$this->db->prepare($sql.($lock?' FOR UPDATE':'')); $q->execute([$id]); $r=$q->fetch();
        if (!$r) throw new RuntimeException('Ficha no disponible.');
        // Después de aprobar, la asignación vigente de la propiedad es la autoridad.
        if ($kind==='solicitud' && $r['id_propiedad']) return $this->context('propiedad',(int)$r['id_propiedad'],$user,$lock);
        $owner=$kind==='propiedad'?(int)($r['id_propietario']??$r['solicitante']??0):(int)$r['id_cliente'];
        $agent=$kind==='propiedad'?(int)$r['id_agente_creador']:(int)$r['id_agente'];
        $allowed=$user['role']==='Administrador' || ($user['role']==='Agente' && $agent===(int)$user['id']) || ($user['role']==='Cliente' && $owner===(int)$user['id']);
        if (!$allowed) throw new RuntimeException('No tenés acceso a esta documentación.');
        return ['kind'=>$kind,'id'=>$id,'property'=>$kind==='propiedad'?$id:null,'request'=>$kind==='solicitud'?$id:($r['id_solicitud']??null),'title'=>$r['titulo'],'faltan_planos'=>(int)($r['faltan_planos']??0),'falta_escritura'=>(int)($r['falta_escritura']??0),'observaciones_documentacion'=>(string)($r['observaciones_documentacion']??'')];
    }

    public static function tax(array $data): ?array
    {
        $keys=['impuesto_concepto','impuesto_base','impuesto_porcentaje','impuesto_periodo'];
        if (!array_filter($keys,fn($k)=>trim((string)($data[$k]??''))!=='')) return null;
        $concept=trim((string)($data['impuesto_concepto']??''));
        $base=trim((string)($data['impuesto_base']??'')); $rate=trim((string)($data['impuesto_porcentaje']??''));
        $period=(string)($data['impuesto_periodo']??''); $currency=(string)($data['impuesto_moneda']??'');
        if ($concept==='' || mb_strlen($concept)>100 || !preg_match('/^\d{1,9}(?:\.\d{1,2})?$/D',$base) || !preg_match('/^\d{1,3}(?:\.\d{1,2})?$/D',$rate) || (float)$rate>100 || !in_array($currency,['ARS','USD'],true) || !preg_match('/^(?:19|20|21)\d{2}-(?:0[1-9]|1[0-2])$/D',$period)) throw new RuntimeException('Revisá el impuesto: concepto, base positiva, porcentaje entre 0 y 100, moneda y período mensual.');
        $cents=(int)round((float)$base*100); $percent=(int)round((float)$rate*100);
        if ($cents<=0) throw new RuntimeException('La base del impuesto debe ser mayor a cero.');
        $amount=intdiv($cents*$percent+5000,10000);
        return [$concept,number_format($cents/100,2,'.',''),number_format($percent/100,2,'.',''),number_format($amount/100,2,'.',''),$currency,$period];
    }

    /** Se llama dentro de la transacción del alta o edición. */
    public function save(?int $property, ?int $request, int $userId, array $data): void
    {
        if (!$this->db->inTransaction() || (!$property && !$request)) throw new LogicException('Se requiere una ficha y una transacción.');
        $tax=self::tax($data);
        $planosErrors=$_FILES['planos']['error']??[];
        $escriturasErrors=$_FILES['escrituras']['error']??[];
        $hasPlanos=is_array($planosErrors) && in_array(UPLOAD_ERR_OK,$planosErrors,true);
        $hasEscritura=is_array($escriturasErrors) && in_array(UPLOAD_ERR_OK,$escriturasErrors,true);
        $faltanPlanos=isset($data['faltan_planos']) && !$hasPlanos?1:0;
        $faltaEscritura=isset($data['falta_escritura']) && !$hasEscritura?1:0;
        $documentNotes=trim((string)($data['observaciones_documentacion']??''));
        if (mb_strlen($documentNotes)>1000 || (($faltanPlanos || $faltaEscritura) && mb_strlen($documentNotes)<10)) throw new RuntimeException('Si te falta algún documento, explicá brevemente la situación (al menos 10 caracteres).');
        $groups=['planos'=>'Plano','escrituras'=>'Escritura','comprobantes'=>'Comprobante']; $count=0;
        foreach ($groups as $field=>$type) foreach (($_FILES[$field]['error']??[]) as $error) if ($error!==UPLOAD_ERR_NO_FILE) $count++;
        if ($count>8) throw new RuntimeException('Adjuntá hasta 8 documentos privados por envío.');
        $folder=dirname(__DIR__).'/privados';
        $allowed=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        try {
            $table=$property?'propiedades':'solicitudes_publicacion';
            $key=$property?'id_propiedad':'id_solicitud';
            $this->db->prepare("UPDATE $table SET faltan_planos=?, falta_escritura=?, observaciones_documentacion=? WHERE $key=?")->execute([$faltanPlanos,$faltaEscritura,$documentNotes!==''?$documentNotes:null,$property??$request]);
            if ($property && $request) $this->db->prepare('UPDATE solicitudes_publicacion SET faltan_planos=?, falta_escritura=?, observaciones_documentacion=? WHERE id_solicitud=?')->execute([$faltanPlanos,$faltaEscritura,$documentNotes!==''?$documentNotes:null,$request]);
            foreach ($groups as $field=>$type) {
                $files=$_FILES[$field]??null; if (!$files) continue;
                if (!is_array($files['name']??null)) throw new RuntimeException('Carga de documentos inválida.');
                foreach ($files['name'] as $i=>$name) {
                    if ($files['error'][$i]===UPLOAD_ERR_NO_FILE) continue;
                    if ($files['error'][$i]!==UPLOAD_ERR_OK || $files['size'][$i]<=0 || $files['size'][$i]>2*1024*1024 || !is_uploaded_file($files['tmp_name'][$i])) throw new RuntimeException('Cada documento debe cargarse correctamente y pesar hasta 2 MB.');
                    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($files['tmp_name'][$i]);
                    if (!isset($allowed[$mime])) throw new RuntimeException('Usá PDF, JPG, PNG o WEBP para los documentos.');
                    if (!is_dir($folder) && !mkdir($folder,0700,true)) throw new RuntimeException('No se pudo preparar el archivo privado.');
                    $filename=bin2hex(random_bytes(24)).'.'.$allowed[$mime]; $path=$folder.'/'.$filename;
                    if (!move_uploaded_file($files['tmp_name'][$i],$path)) throw new RuntimeException('No se pudo guardar el documento.');
                    $this->created[]=$path;
                    $this->db->prepare('INSERT INTO documentos (id_propiedad,id_solicitud,tipo,nombre,archivo,id_usuario) VALUES (?,?,?,?,?,?)')->execute([$property,$request,$type,mb_substr(basename(str_replace('\\','/',(string)$name)),0,255),$filename,$userId]);
                }
            }
            if ($tax) $this->db->prepare('INSERT INTO propiedad_impuestos (id_propiedad,id_solicitud,concepto,base,porcentaje,importe,moneda,periodo,id_usuario) VALUES (?,?,?,?,?,?,?,?,?)')->execute([$property,$request,...$tax,$userId]);
        } catch (Throwable $e) { $this->cleanup(); throw $e; }
    }

    public function cleanup(): void { foreach ($this->created as $path) if (is_file($path)) unlink($path); $this->created=[]; }
    public function records(array $context): array
    {
        $field=$context['kind']==='propiedad'?'id_propiedad':'id_solicitud'; $result=[];
        foreach (['documentos','propiedad_impuestos'] as $table) {
            $q=$this->db->prepare("SELECT * FROM $table WHERE $field=? ORDER BY fecha DESC"); $q->execute([$context['id']]); $result[$table]=$q->fetchAll();
        }
        return $result;
    }
    public function link(int $request, int $property): void
    {
        foreach (['documentos','propiedad_impuestos'] as $table) $this->db->prepare("UPDATE $table SET id_propiedad=? WHERE id_solicitud=?")->execute([$property,$request]);
        $this->db->prepare('UPDATE propiedades p JOIN solicitudes_publicacion s ON s.id_solicitud=? SET p.faltan_planos=s.faltan_planos,p.falta_escritura=s.falta_escritura,p.observaciones_documentacion=s.observaciones_documentacion WHERE p.id_propiedad=?')->execute([$request,$property]);
    }
}
