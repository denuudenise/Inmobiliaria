<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/sistema/Nucleo/inicio.php';
$config=require dirname(__DIR__).'/configuracion/mysql.php';
$server=new PDO("mysql:host={$config['host']};port={$config['port']};charset=utf8mb4",$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$name='inmobiliaria_test_'.bin2hex(random_bytes(6));
$server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
function check(bool $ok,string $message): void { if (!$ok) throw new RuntimeException($message); echo "OK: $message\n"; }
try {
    putenv('INMOBILIARIA_DB_NAME='.$name);
    $db=Database::connection();
    $sql=file_get_contents(dirname(__DIR__).'/inmobiliaria (5).sql');
    if (preg_match('/^\s*(?:USE\s|CREATE\s+DATABASE|DROP\s+DATABASE)/mi',$sql)) throw new RuntimeException('El SQL debe contener únicamente tablas de la base temporal.');
    $db->exec($sql);
    $db->exec(file_get_contents(dirname(__DIR__).'/base_datos/migraciones/004_documentacion_impuestos.sql'));
    $db->exec(file_get_contents(dirname(__DIR__).'/base_datos/migraciones/005_ofertas_caracteristicas.sql'));
    $users=new UsuarioDAO($db); $dao=new GestionDAO($db);
    $client=$users->createClient('Prueba','Cliente','99999001','','prueba-modelo@example.test',password_hash('Prueba123!',PASSWORD_DEFAULT));
    check(count(query_rows('SELECT * FROM clientes WHERE id_cliente=?',[$client]))===1,'Registro crea ficha de cliente');
    $agent=$users->createUser('Prueba','Agente','99999002','','agente-modelo@example.test','test',2);
    check(!query_rows('SELECT * FROM clientes WHERE id_cliente=?',[$agent]),'Agente no genera ficha de cliente');
    execute_sql('UPDATE clientes SET id_agente_asignado=? WHERE id_cliente=?',[$agent,$client]);
    $panel=file_get_contents(dirname(__DIR__).'/agente.php');
    preg_match('/\$people\s*=\s*query_rows\(\s*\x27(.*?)\x27\s*,\s*\[/s',$panel,$match);
    $people=query_rows($match[1],['%','%','%',0,0,$agent,$agent,$agent,$agent]);
    check(in_array($client,array_map('intval',array_column($people,'id_usuario')),true),'Panel incluye cliente asignado sin actividad previa');
    $admin=['id'=>(int)$db->query('SELECT id_usuario FROM usuarios WHERE id_rol=1 LIMIT 1')->fetchColumn(),'role'=>'Administrador'];
    $data=['titulo'=>'Depósito prueba','direccion'=>'Prueba 123','localidad'=>'Prueba','descripcion'=>'Integración','precio'=>'100000','moneda'=>'ARS','superficie'=>'100','ambientes'=>'1','dormitorios'=>'0','banos'=>'1','tipo'=>'7','operacion'=>'1','agente'=>$agent,'verificacion'=>'3','estado'=>'1'];
    $dao->saveProperty($data,$admin);
    $id=(int)$db->query('SELECT MAX(id_propiedad) FROM propiedades')->fetchColumn();
    check(count(query_rows('SELECT * FROM propiedad_ofertas WHERE id_propiedad=? AND moneda="ARS" AND precio=100000',[$id]))===1,'Depósito guarda oferta y moneda');
    check(count(query_rows('SELECT * FROM publicaciones WHERE id_propiedad=? AND estado="Publicada"',[$id]))===1,'Alta guarda publicación');
    execute_sql('INSERT INTO propiedad_ofertas (id_propiedad,id_tipo_transaccion,precio,moneda) VALUES (?,2,5000,"ARS")',[$id]);
    $data['id']=$id; $data['precio']='110000'; $data['verificacion']='2';
    $dao->saveProperty($data,$admin);
    check((float)query_rows('SELECT precio FROM propiedad_ofertas WHERE id_propiedad=? AND id_tipo_transaccion=1',[$id])[0]['precio']===110000.0,'Edición sincroniza precio');
    check((float)query_rows('SELECT precio FROM propiedad_ofertas WHERE id_propiedad=? AND id_tipo_transaccion=2',[$id])[0]['precio']===5000.0,'Edición conserva otras ofertas');
    check(query_rows('SELECT estado FROM publicaciones WHERE id_propiedad=?',[$id])[0]['estado']==='Pendiente','Edición sincroniza revisión');
    $data['verificacion']='3'; $dao->saveProperty($data,$admin);
    $dao->closeOperation(['propiedad'=>$id,'cliente'=>$client,'monto'=>'109000'],$admin);
    $sale=query_rows('SELECT * FROM propiedades_cerradas WHERE id_propiedad=?',[$id])[0];
    check($sale['moneda']==='ARS' && (int)$sale['id_tipo_transaccion']===1,'Venta conserva moneda y operación');
    check(count(query_rows('SELECT * FROM roles_cliente WHERE id_cliente=? AND tipo_rol="Comprador"',[$client]))===1,'Venta registra rol Comprador');
    check((new PropiedadDAO($db))->findClosedOperationsForClient($client)[0]['moneda']==='ARS','Historial muestra moneda de cierre');
    $request=['tipo'=>'Depósito','operacion'=>'Venta','titulo'=>'Solicitud prueba','direccion'=>'Prueba 45','localidad'=>'Prueba','descripcion'=>'Prueba integración','precio'=>'90000','moneda'=>'ARS','superficie'=>'150','ambientes'=>'1','dormitorios'=>'0','banos'=>'1','antiguedad'=>'0'];
    $requestId=(new SolicitudPropiedadDAO($db))->create($client,$request);
    $dao->moderate($requestId,'asignar',$admin,'',$agent);
    $dao->moderate($requestId,'aprobar',$admin,'',0);
    $approved=query_rows('SELECT * FROM propiedades WHERE id_solicitud=?',[$requestId])[0];
    check((int)$approved['id_propietario']===$client,'Aprobación conserva propietario');
    check(count(query_rows('SELECT * FROM publicaciones WHERE id_propiedad=? AND id_solicitante=? AND estado="Publicada"',[$approved['id_propiedad'],$client]))===1,'Aprobación guarda publicación del cliente');
    check(count(query_rows('SELECT * FROM propiedad_ofertas WHERE id_propiedad=?',[$approved['id_propiedad']]))===1,'Aprobación guarda oferta');
    // Una falla en la última escritura no debe dejar usuarios o propiedades parciales.
    $db->exec("CREATE TRIGGER test_fail_client BEFORE INSERT ON clientes FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fallo simulado'");
    try { $users->createClient('Rollback','Cliente','99999003','','rollback@example.test','test'); throw new LogicException('Debió fallar'); } catch (PDOException $expected) {}
    check(!query_rows('SELECT * FROM usuarios WHERE email="rollback@example.test"'),'Falla de ficha revierte alta completa');
    $db->exec('DROP TRIGGER test_fail_client');
    $before=(int)$db->query('SELECT COUNT(*) FROM propiedades')->fetchColumn();
    $db->exec("CREATE TRIGGER test_fail_publication BEFORE INSERT ON publicaciones FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fallo simulado'");
    unset($data['id']);
    try { $dao->saveProperty($data,$admin); throw new LogicException('Debió fallar'); } catch (PDOException $expected) {}
    check((int)$db->query('SELECT COUNT(*) FROM propiedades')->fetchColumn()===$before,'Falla de publicación revierte propiedad y oferta');
    check(!query_rows('SELECT o.id_propiedad FROM propiedad_ofertas o LEFT JOIN propiedades p ON p.id_propiedad=o.id_propiedad WHERE p.id_propiedad IS NULL'),'Sin ofertas huérfanas');
    echo "Pruebas completadas.\n";
} finally {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    $server->exec("DROP DATABASE `$name`");
    echo "Base temporal eliminada.\n";
}
