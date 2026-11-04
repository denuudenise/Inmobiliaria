<?php
declare(strict_types=1);

final class SeguimientoDAO
{
    public function __construct(private PDO $db) {}

    private function transaction(callable $work): mixed
    {
        $this->db->beginTransaction();
        try { $result = $work(); $this->db->commit(); return $result; }
        catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }

    private function property(int $id, array $user): array
    {
        $q=$this->db->prepare('SELECT p.*,o.precio,o.moneda,o.id_tipo_transaccion FROM propiedades p LEFT JOIN propiedad_ofertas o ON o.id_propiedad=p.id_propiedad WHERE p.id_propiedad=? ORDER BY o.id_tipo_transaccion LIMIT 1');
        $q->execute([$id]); $property=$q->fetch();
        if (!$property || ($user['role']==='Agente' && (int)$property['id_agente_creador']!==(int)$user['id'])) throw new RuntimeException('La propiedad no está disponible para tu usuario.');
        return $property;
    }

    private function client(int $id): void
    {
        $q=$this->db->prepare('SELECT id_usuario FROM usuarios WHERE id_usuario=? AND id_rol=3 AND activo=1'); $q->execute([$id]);
        if (!$q->fetchColumn()) throw new RuntimeException('Seleccioná un cliente activo.');
    }

    public function clientsFor(array $user, bool $admin): array
    {
        if ($admin) return query_rows('SELECT id_usuario,nombre,apellido FROM usuarios WHERE id_rol=3 AND activo=1 ORDER BY nombre,apellido');
        return query_rows(
            'SELECT DISTINCT u.id_usuario,u.nombre,u.apellido FROM usuarios u
             LEFT JOIN clientes ficha ON ficha.id_cliente=u.id_usuario
             WHERE u.id_rol=3 AND u.activo=1 AND (
                ficha.id_agente_asignado=?
                OR EXISTS(SELECT 1 FROM contactos c WHERE c.id_cliente=u.id_usuario AND c.id_agente=?)
                OR EXISTS(SELECT 1 FROM visitas v WHERE v.id_cliente=u.id_usuario AND v.id_agente=?)
                OR EXISTS(SELECT 1 FROM tareas_seguimiento t WHERE t.id_cliente=u.id_usuario AND t.id_agente=?)
                OR EXISTS(SELECT 1 FROM solicitudes_publicacion s WHERE s.id_cliente=u.id_usuario AND s.id_agente=?)
                OR EXISTS(SELECT 1 FROM intereses i JOIN propiedades p ON p.id_propiedad=i.id_propiedad WHERE i.id_cliente=u.id_usuario AND p.id_agente_creador=?)
             ) ORDER BY u.nombre,u.apellido',
            [$user['id'],$user['id'],$user['id'],$user['id'],$user['id'],$user['id']]
        );
    }

    public function recordInteraction(array $user, array $data): void
    {
        $client=(int)($data['cliente']??0); $property=(int)($data['propiedad']??0);
        $kind=trim((string)($data['tipo_interaccion']??'')); $note=trim((string)($data['observaciones']??''));
        if (!in_array($kind,['Llamada','WhatsApp','Correo','Envío de ficha','Seguimiento','Nota interna'],true) || mb_strlen($note)<3 || mb_strlen($note)>5000) throw new RuntimeException('Completá el tipo y una observación de al menos 3 caracteres.');
        $this->property($property,$user); $this->client($client);
        $this->transaction(function()use($user,$client,$property,$kind,$note){
            $q=$this->db->prepare('INSERT INTO contactos (id_cliente,id_agente,id_propiedad,tipo,descripcion,estado) VALUES (?,?,?,?,?,\'Cerrada\')');
            $q->execute([$client,$user['id'],$property,$kind,$note]); $id=(int)$this->db->lastInsertId();
            (new AuditoriaDAO($this->db))->record((int)$user['id'],'Registrar interacción','contactos',$id,$kind.' · cliente '.$client.' · propiedad '.$property);
        });
    }

    public function scheduleVisit(array $user, array $data): void
    {
        $client=(int)($data['cliente']??0); $property=(int)($data['propiedad']??0); $date=trim((string)($data['fecha_hora']??'')); $note=trim((string)($data['observaciones']??''));
        $parsed=DateTime::createFromFormat('!Y-m-d\\TH:i',$date);
        if (!$parsed || $parsed->format('Y-m-d\\TH:i')!==$date || $parsed < new DateTime('-1 minute')) throw new RuntimeException('Elegí una fecha y hora de visita válidas.');
        $this->property($property,$user); $this->client($client);
        $this->transaction(function()use($user,$client,$property,$parsed,$note){
            $q=$this->db->prepare('INSERT INTO visitas (id_propiedad,id_cliente,id_agente,fecha_hora,estado,observaciones) VALUES (?,?,?,? ,\'Programada\',?)');
            $q->execute([$property,$client,$user['id'],$parsed->format('Y-m-d H:i:s'),$note]); $id=(int)$this->db->lastInsertId();
            (new AuditoriaDAO($this->db))->record((int)$user['id'],'Programar visita','visitas',$id,'Cliente '.$client.' · propiedad '.$property);
        });
    }

    public function createTask(array $user, array $data): void
    {
        $client=(int)($data['cliente']??0); $property=(int)($data['propiedad']??0); $title=trim((string)($data['titulo']??'')); $detail=trim((string)($data['detalle']??'')); $due=trim((string)($data['fecha_limite']??''));
        $parsed=DateTime::createFromFormat('!Y-m-d\\TH:i',$due);
        if (mb_strlen($title)<3 || mb_strlen($title)>180 || !$parsed || $parsed->format('Y-m-d\\TH:i')!==$due) throw new RuntimeException('Completá un título y una fecha límite válidos.');
        $this->property($property,$user); $this->client($client);
        $this->transaction(function()use($user,$client,$property,$title,$detail,$parsed){
            $q=$this->db->prepare('INSERT INTO tareas_seguimiento (id_agente,id_cliente,id_propiedad,titulo,detalle,fecha_limite) VALUES (?,?,?,?,?,?)');
            $q->execute([$user['id'],$client,$property,$title,$detail,$parsed->format('Y-m-d H:i:s')]); $id=(int)$this->db->lastInsertId();
            (new AuditoriaDAO($this->db))->record((int)$user['id'],'Crear tarea de seguimiento','tareas_seguimiento',$id,$title);
        });
    }

    public function completeTask(array $user, int $taskId): void
    {
        $q=$this->db->prepare('SELECT * FROM tareas_seguimiento WHERE id_tarea=?');$q->execute([$taskId]);$task=$q->fetch();
        if(!$task || ($user['role']!=='Administrador' && (int)$task['id_agente']!==(int)$user['id']))throw new RuntimeException('La tarea no está disponible.');
        $this->transaction(function()use($user,$task){
            $this->db->prepare("UPDATE tareas_seguimiento SET estado='Completada',fecha_completada=NOW() WHERE id_tarea=?")->execute([$task['id_tarea']]);
            (new AuditoriaDAO($this->db))->record((int)$user['id'],'Completar tarea','tareas_seguimiento',(int)$task['id_tarea'],$task['titulo'],['estado'=>['anterior'=>$task['estado'],'nuevo'=>'Completada']]);
        });
    }

    public function submitOffer(array $user, int $propertyId, int $operationId, array $data): void
    {
        if (($user['role']??'')!=='Cliente') throw new RuntimeException('Ingresá como cliente para enviar una oferta.');
        $amount=trim((string)($data['monto']??'')); $expiry=trim((string)($data['fecha_vencimiento']??'')); $note=trim((string)($data['observaciones']??''));
        if (!preg_match('/^\\d{1,10}(?:\\.\\d{1,2})?$/D',$amount) || (float)$amount<=0 || (float)$amount>9999999999) throw new RuntimeException('Ingresá un monto de oferta válido.');
        if ($expiry!=='' && (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/D',$expiry) || $expiry<date('Y-m-d'))) throw new RuntimeException('La fecha de vencimiento debe ser hoy o posterior.');
        $q=$this->db->prepare('SELECT p.id_agente_creador,o.moneda FROM propiedades p JOIN propiedad_ofertas o ON o.id_propiedad=p.id_propiedad WHERE p.id_propiedad=? AND o.id_tipo_transaccion=? AND p.id_estado_verificacion=3 AND p.id_estado_comercial=1');
        $q->execute([$propertyId,$operationId]);$row=$q->fetch();
        if(!$row)throw new RuntimeException('La publicación ya no está disponible para esa operación.');
        $this->transaction(function()use($user,$propertyId,$operationId,$row,$amount,$expiry,$note){
            $q=$this->db->prepare("INSERT INTO ofertas_cliente (id_propiedad,id_cliente,id_agente,id_tipo_transaccion,monto,moneda,fecha_vencimiento,estado,observaciones) VALUES (?,?,?,?,?,?,?,'Pendiente',?)");
            $q->execute([$propertyId,$user['id'],$row['id_agente_creador'],$operationId,$amount,$row['moneda'],$expiry!==''?$expiry:null,$note]);$id=(int)$this->db->lastInsertId();
            (new AuditoriaDAO($this->db))->record((int)$user['id'],'Enviar oferta','ofertas_cliente',$id,'Oferta para propiedad '.$propertyId.' por '.$amount.' '.$row['moneda']);
        });
    }

    public function reviewOffer(array $user, int $offerId, string $status, string $note=''): void
    {
        if(!in_array($status,['Aceptada','Rechazada','Vencida'],true))throw new RuntimeException('Estado de oferta inválido.');
        $q=$this->db->prepare('SELECT o.*,p.id_agente_creador FROM ofertas_cliente o JOIN propiedades p ON p.id_propiedad=o.id_propiedad WHERE o.id_oferta=?');$q->execute([$offerId]);$offer=$q->fetch();
        if(!$offer || ($user['role']!=='Administrador' && (int)$offer['id_agente_creador']!==(int)$user['id']))throw new RuntimeException('No tenés acceso a esta oferta.');
        if($offer['estado']!=='Pendiente')throw new RuntimeException('La oferta ya fue revisada.');
        if($status==='Aceptada' && $offer['fecha_vencimiento'] && $offer['fecha_vencimiento']<date('Y-m-d'))throw new RuntimeException('La oferta venció y no se puede aceptar.');
        $this->transaction(function()use($user,$offer,$status,$note){
            $this->db->prepare('UPDATE ofertas_cliente SET estado=?,observaciones=COALESCE(NULLIF(?,\'\'),observaciones),id_revisor=?,fecha_revision=NOW() WHERE id_oferta=?')->execute([$status,$note,$user['id'],$offer['id_oferta']]);
            (new AuditoriaDAO($this->db))->record((int)$user['id'],'Revisar oferta','ofertas_cliente',(int)$offer['id_oferta'],$status,['estado'=>['anterior'=>$offer['estado'],'nuevo'=>$status,'motivo'=>$note]]);
        });
    }

    public function createReservation(array $user, array $data): void
    {
        if(!in_array($user['role'],['Agente','Administrador'],true))throw new RuntimeException('No tenés permiso para crear reservas.');
        $offerId=(int)($data['oferta']??0);$limit=trim((string)($data['fecha_limite']??''));$method=trim((string)($data['medio_pago']??''));$note=trim((string)($data['observaciones']??''));
        $date=DateTime::createFromFormat('!Y-m-d\\TH:i',$limit);
        if(!$date || $date->format('Y-m-d\\TH:i')!==$limit || $date<=new DateTime())throw new RuntimeException('La reserva necesita una fecha límite futura.');
        $q=$this->db->prepare("SELECT o.*,p.id_estado_comercial,p.id_agente_creador FROM ofertas_cliente o JOIN propiedades p ON p.id_propiedad=o.id_propiedad WHERE o.id_oferta=?");$q->execute([$offerId]);$offer=$q->fetch();
        if(!$offer || $offer['estado']!=='Aceptada' || ($user['role']!=='Administrador' && (int)$offer['id_agente_creador']!==(int)$user['id']))throw new RuntimeException('Elegí una oferta aceptada que esté a tu cargo.');
        if(!in_array((int)$offer['id_estado_comercial'],[1,2,5],true))throw new RuntimeException('La propiedad no admite reservas en su estado actual.');
        $existing=query_rows("SELECT id_reserva FROM reservas WHERE id_propiedad=? AND estado IN ('Pendiente','Activa') LIMIT 1",[$offer['id_propiedad']]);
        if($existing)throw new RuntimeException('La propiedad ya tiene una reserva pendiente o activa.');
        $this->transaction(function()use($user,$data,$offer,$date,$method,$note){
            $q=$this->db->prepare("INSERT INTO reservas (id_propiedad,id_cliente,id_agente,id_tipo_transaccion,id_oferta,monto,moneda,fecha_limite,estado,estado_pago,medio_pago,observaciones) VALUES (?,?,?,?,?,?,?,?,'Pendiente','Pendiente',?,?)");
            $q->execute([$offer['id_propiedad'],$offer['id_cliente'],$offer['id_agente_creador'],$offer['id_tipo_transaccion'],$offer['id_oferta'],$offer['monto'],$offer['moneda'],$date->format('Y-m-d H:i:s'),$method,$note]);$id=(int)$this->db->lastInsertId();
            (new AuditoriaDAO($this->db))->record((int)$user['id'],'Crear reserva pendiente','reservas',$id,'Reserva originada en oferta '.$offer['id_oferta']);
        });
    }

    public function updateReservation(array $user, int $reservationId, string $action, string $payment='Pendiente'): void
    {
        if($user['role']!=='Administrador')throw new RuntimeException('Solo un administrador puede autorizar o cancelar reservas.');
        $q=$this->db->prepare('SELECT * FROM reservas WHERE id_reserva=?');$q->execute([$reservationId]);$reservation=$q->fetch();
        if(!$reservation)throw new RuntimeException('Reserva no encontrada.');
        if($action==='pago'){
            if(!in_array($payment,['Pendiente','Pagado','Rechazado'],true))throw new RuntimeException('Estado de pago inválido.');
            $this->transaction(function()use($user,$reservation,$payment){$this->db->prepare('UPDATE reservas SET estado_pago=? WHERE id_reserva=?')->execute([$payment,$reservation['id_reserva']]);(new AuditoriaDAO($this->db))->record((int)$user['id'],'Actualizar pago de reserva','reservas',(int)$reservation['id_reserva'],$payment,['estado_pago'=>['anterior'=>$reservation['estado_pago'],'nuevo'=>$payment]]);});
            return;
        }
        if(!in_array($action,['autorizar','cancelar'],true) || !in_array($reservation['estado'],['Pendiente','Activa'],true))throw new RuntimeException('La reserva no admite ese cambio.');
        $this->transaction(function()use($user,$reservation,$action){
            $next=$action==='autorizar'?'Activa':'Cancelada';
            if($action==='autorizar'){
                if(strtotime($reservation['fecha_limite'])<=time())throw new RuntimeException('La fecha límite de la reserva ya venció.');
                $another=query_rows("SELECT id_reserva FROM reservas WHERE id_propiedad=? AND estado='Activa' AND id_reserva<>? LIMIT 1",[$reservation['id_propiedad'],$reservation['id_reserva']]);
                if($another)throw new RuntimeException('La propiedad ya tiene otra reserva activa.');
            }
            $this->db->prepare('UPDATE reservas SET estado=?,id_autorizador=? WHERE id_reserva=?')->execute([$next,$user['id'],$reservation['id_reserva']]);
            if($action==='autorizar'){
                $property=$this->db->prepare('SELECT id_estado_comercial FROM propiedades WHERE id_propiedad=? FOR UPDATE');$property->execute([$reservation['id_propiedad']]);$old=(int)$property->fetchColumn();
                if($old!==2){$this->db->prepare('UPDATE propiedades SET id_estado_comercial=2 WHERE id_propiedad=?')->execute([$reservation['id_propiedad']]);$this->db->prepare('INSERT INTO historial_estados (id_propiedad,id_usuario,estado_anterior,estado_nuevo,motivo) VALUES (?,?,?,2,?)')->execute([$reservation['id_propiedad'],$user['id'],$old,'Reserva '.$reservation['id_reserva'].' autorizada']);}
            }elseif($reservation['estado']==='Activa'){
                $other=query_rows("SELECT id_reserva FROM reservas WHERE id_propiedad=? AND estado='Activa' AND id_reserva<>? LIMIT 1",[$reservation['id_propiedad'],$reservation['id_reserva']]);
                if(!$other){$old=(int)$this->db->query('SELECT id_estado_comercial FROM propiedades WHERE id_propiedad='.(int)$reservation['id_propiedad'])->fetchColumn();$this->db->prepare('UPDATE propiedades SET id_estado_comercial=1 WHERE id_propiedad=?')->execute([$reservation['id_propiedad']]);if($old!==1)$this->db->prepare('INSERT INTO historial_estados (id_propiedad,id_usuario,estado_anterior,estado_nuevo,motivo) VALUES (?,?,?,1,?)')->execute([$reservation['id_propiedad'],$user['id'],$old,'Reserva '.$reservation['id_reserva'].' cancelada']);}
            }
            (new AuditoriaDAO($this->db))->record((int)$user['id'],$action==='autorizar'?'Autorizar reserva':'Cancelar reserva','reservas',(int)$reservation['id_reserva'],$next,['estado'=>['anterior'=>$reservation['estado'],'nuevo'=>$next]]);
        });
    }

    public function panelData(array $user, bool $admin): array
    {
        $agentFilter=$admin?'':' AND p.id_agente_creador=?';
        $agentParam=$admin?[]:[$user['id']];
        return [
            'interactions'=>query_rows('SELECT c.*,u.nombre AS cliente,u.apellido,p.titulo FROM contactos c JOIN usuarios u ON u.id_usuario=c.id_cliente JOIN propiedades p ON p.id_propiedad=c.id_propiedad WHERE c.tipo<>"Consulta"'.$agentFilter.' ORDER BY c.fecha DESC LIMIT 30',$agentParam),
            'visits'=>query_rows('SELECT v.*,u.nombre AS cliente,u.apellido,p.titulo FROM visitas v JOIN usuarios u ON u.id_usuario=v.id_cliente JOIN propiedades p ON p.id_propiedad=v.id_propiedad WHERE 1=1'.($admin?'':' AND v.id_agente=?').' ORDER BY v.fecha_hora DESC LIMIT 30',$admin?[]:[$user['id']]),
            'tasks'=>query_rows('SELECT t.*,u.nombre AS cliente,u.apellido,p.titulo FROM tareas_seguimiento t JOIN usuarios u ON u.id_usuario=t.id_cliente LEFT JOIN propiedades p ON p.id_propiedad=t.id_propiedad WHERE 1=1'.($admin?'':' AND t.id_agente=?').' ORDER BY t.estado="Pendiente" DESC,t.fecha_limite LIMIT 30',$admin?[]:[$user['id']]),
            'offers'=>query_rows('SELECT o.*,u.nombre AS cliente,u.apellido,p.titulo,t.nombre AS operacion FROM ofertas_cliente o JOIN usuarios u ON u.id_usuario=o.id_cliente JOIN propiedades p ON p.id_propiedad=o.id_propiedad JOIN tipos_transaccion t ON t.id_tipo_transaccion=o.id_tipo_transaccion WHERE 1=1'.$agentFilter.' ORDER BY o.estado="Pendiente" DESC,o.fecha_creacion DESC LIMIT 50',$agentParam),
            'reservations'=>query_rows('SELECT r.*,u.nombre AS cliente,u.apellido,p.titulo,t.nombre AS operacion FROM reservas r JOIN usuarios u ON u.id_usuario=r.id_cliente JOIN propiedades p ON p.id_propiedad=r.id_propiedad JOIN tipos_transaccion t ON t.id_tipo_transaccion=r.id_tipo_transaccion WHERE 1=1'.($admin?'':' AND r.id_agente=?').' ORDER BY r.estado="Pendiente" DESC,r.fecha_reserva DESC LIMIT 50',$admin?[]:[$user['id']]),
        ];
    }

    public function auditLog(int $limit=150): array
    {
        $limit=max(1,min(300,$limit));
        return query_rows('SELECT a.*,u.nombre,u.apellido FROM auditoria a JOIN usuarios u ON u.id_usuario=a.id_usuario ORDER BY a.fecha DESC,a.id_evento DESC LIMIT '.$limit);
    }
}
