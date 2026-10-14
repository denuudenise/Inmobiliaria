<?php
// Uso exclusivo desde CLI. No modifica las cuentas existentes.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/sistema/Nucleo/inicio.php';
$db=Database::connection(); $db->beginTransaction();
try {
 $ids=[];
 foreach ([['admin','Ana','Torres',1,'90000001','AdminDemo!2026'],['agente','Lucía','Fernández',2,'90000002','AgenteDemo!2026'],['cliente','Juan','Pérez',3,'90000003','ClienteDemo!2026']] as [$key,$name,$surname,$role,$dni,$password]) {
  $email=$key.'@nuevohogar.test'; $existing=query_rows('SELECT id_usuario FROM usuarios WHERE email=?',[$email]);
  if (!$existing) execute_sql('INSERT INTO usuarios (nombre,apellido,dni,telefono,email,password_hash,id_rol,activo) VALUES (?,?,?,?,?,?,?,1)',[$name,$surname,$dni,'1140000000',$email,password_hash($password,PASSWORD_DEFAULT),$role]);
  $ids[$key]=(int)query_rows('SELECT id_usuario FROM usuarios WHERE email=?',[$email])[0]['id_usuario'];
 }
 $properties=[
 ['Casa con jardín y pileta en San Isidro','San Isidro','Los Ceibos 124',1,1,250000,'USD',240,5,3,2,3,1,'casa.jpg'],
 ['Departamento luminoso en Palermo','Palermo, CABA','Armenia 1850',2,1,180000,'USD',85,3,2,1,3,1,'interior.jpg'],
 ['Casa familiar en alquiler en Tigre','Tigre','Los Robles 480',1,2,1200000,'ARS',190,4,3,2,3,1,'casa2.jpg'],
 ['Terreno para construir en Luján','Luján','Camino del Sol 750',5,1,45000,'USD',500,0,0,0,3,1,'propiedad.svg'],
 ['Oficina con vista abierta en Belgrano','Belgrano, CABA','Cabildo 2100',4,2,680000,'ARS',60,2,0,1,3,1,'interior2.jpg'],
 ['Departamento temporario en Vicente López','Vicente López','Maipú 920',2,3,850000,'ARS',58,2,1,1,3,1,'interior.jpg'],
 ['Casa en revisión en Olivos','Olivos','Arenales 640',1,1,210000,'USD',150,4,3,2,2,1,'casa2.jpg'],
 ['Departamento vendido en Florida','Florida','San Martín 1600',2,1,115000,'USD',72,3,2,1,3,3,'interior2.jpg'],
 ['Departamento alquilado en Martínez','Martínez','Alvear 800',2,2,720000,'ARS',65,3,2,1,3,4,'interior.jpg']
 ];
 $propIds=[];
 foreach($properties as [$title,$city,$address,$type,$operation,$price,$currency,$area,$rooms,$beds,$baths,$verified,$state,$photo]) {
  $existing=query_rows('SELECT id_propiedad FROM propiedades WHERE titulo=? AND id_agente_creador=?',[$title,$ids['agente']]);
  if ($existing) { $propIds[]=(int)$existing[0]['id_propiedad']; continue; }
  execute_sql('INSERT INTO propiedades (titulo,localidad,direccion,id_tipo_propiedad,id_tipo_transaccion,precio,moneda,superficie_total_m2,ambientes,dormitorios,banos,id_estado_verificacion,id_estado_comercial,id_agente_creador,id_admin_verificador,descripcion,antiguedad_anos,fecha_verificacion) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,8,NOW())',[$title,$city,$address,$type,$operation,$price,$currency,$area,$rooms,$beds,$baths,$verified,$state,$ids['agente'],$ids['admin'],'Propiedad de demostración para probar el sistema. Espacios luminosos y excelente distribución, cerca de comercios y transporte. Las direcciones, precios y fotografías son ilustrativos. Coordiná una visita desde el formulario de consulta.']);
  $pid=(int)$db->lastInsertId(); $propIds[]=$pid;
  execute_sql("INSERT INTO propiedad_multimedia (id_propiedad,url_archivo,tipo,orden) VALUES (?,?,'Imagen',1)",[$pid,'/Inmobiliaria/recursos/imagenes/'.$photo]);
  if ($type!==5) { execute_sql("INSERT INTO propiedad_multimedia (id_propiedad,url_archivo,tipo,orden) VALUES (?,?,'Imagen',2)",[$pid,'/Inmobiliaria/recursos/imagenes/interior2.jpg']); execute_sql('INSERT IGNORE INTO propiedad_caracteristicas VALUES (?,1),(?,7)',[$pid,$pid]); }
 }
 foreach ([['Casa para publicar en Olivos',2,null],['Departamento para completar en San Fernando',4,'Agregá una descripción más detallada del estado de la propiedad.'],['Casa publicada en San Isidro',3,null]] as [$title,$state,$reason]) {
  if (!query_rows('SELECT id_solicitud FROM solicitudes_publicacion WHERE id_cliente=? AND titulo=?',[$ids['cliente'],$title])) {
   execute_sql('INSERT INTO solicitudes_publicacion (id_cliente,id_agente,id_tipo_propiedad,id_tipo_transaccion,id_estado_verificacion,titulo,direccion,localidad,descripcion,precio_estimado,moneda,superficie_total_m2,ambientes,dormitorios,banos,motivo_rechazo) VALUES (?,?,1,1,?,?,?, ?,?,195000,\'USD\',130,4,3,2,?)',[$ids['cliente'],$ids['agente'],$state,$title,'San Lorenzo 580','Olivos','Solicitud de demostración. Casa luminosa de tres dormitorios con patio y cochera.',$reason]);
   $sid=(int)$db->lastInsertId(); execute_sql("INSERT INTO solicitud_multimedia (id_solicitud,url_archivo,tipo) VALUES (?,?,'Imagen')",[$sid,'/Inmobiliaria/recursos/imagenes/casa.jpg']);
   if ($state===3) execute_sql('UPDATE propiedades SET id_solicitud=? WHERE id_propiedad=? AND id_solicitud IS NULL',[$sid,$propIds[0]]);
  }
 }
 if (!query_rows('SELECT id_consulta FROM consultas WHERE id_cliente=? AND id_propiedad=?',[$ids['cliente'],$propIds[1]])) execute_sql('INSERT INTO consultas (id_cliente,id_propiedad,mensaje) VALUES (?,?,?)',[$ids['cliente'],$propIds[1],'Hola, quisiera coordinar una visita el próximo sábado. ¿Está disponible por la mañana?']);
 execute_sql('INSERT IGNORE INTO favoritos (id_usuario,id_propiedad) VALUES (?,?)',[$ids['cliente'],$propIds[0]]);
 if (!query_rows('SELECT id_operacion FROM propiedades_cerradas WHERE id_propiedad=?',[$propIds[7]])) execute_sql('INSERT INTO propiedades_cerradas (id_propiedad,id_cliente_comprador,id_agente_cierre,precio_final) VALUES (?,?,?,115000)',[$propIds[7],$ids['cliente'],$ids['agente']]);
 if (!query_rows('SELECT id_alquiler FROM alquileres WHERE id_propiedad=?',[$propIds[8]])) execute_sql("INSERT INTO alquileres (id_propiedad,id_cliente,id_agente,monto_mensual,fecha_inicio,fecha_fin,estado) VALUES (?,?,?,720000,'2026-10-01','2027-09-30','Activo')",[$propIds[8],$ids['cliente'],$ids['agente']]);
 $db->commit(); echo "Datos demo listos.\n";
} catch(Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
