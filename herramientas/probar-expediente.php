<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/sistema/Nucleo/inicio.php';
$config=require dirname(__DIR__).'/configuracion/mysql.php';
$server=new PDO("mysql:host={$config['host']};port={$config['port']};charset=utf8mb4",$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$name='inmobiliaria_test_'.bin2hex(random_bytes(6));
$server->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4");
$process=null;$router=null;
try {
 putenv('INMOBILIARIA_DB_NAME='.$name);$db=Database::connection();
 $db->exec(file_get_contents(dirname(__DIR__).'/inmobiliaria (5).sql'));
 $db->exec(file_get_contents(dirname(__DIR__).'/base_datos/migraciones/004_documentacion_impuestos.sql'));
 $db->exec(file_get_contents(dirname(__DIR__).'/base_datos/migraciones/005_ofertas_caracteristicas.sql'));
 $users=new UsuarioDAO($db);$password='Prueba!2026';$ids=[];
 foreach (['cliente'=>3,'otro'=>3,'agente'=>2,'ajeno'=>2,'admin'=>1] as $key=>$role) $ids[$key]=$users->createUser('Prueba',$key,(string)(99001000+count($ids)),'',$key.'@expediente.test',password_hash($password,PASSWORD_DEFAULT),$role);
 $request=['tipo'=>'Casa','operacion'=>'Venta','titulo'=>'Ficha privada prueba','direccion'=>'Prueba 123','localidad'=>'Prueba','descripcion'=>'Solicitud de prueba aislada','precio'=>'100000','moneda'=>'ARS','superficie'=>'100','ambientes'=>'3','dormitorios'=>'2','banos'=>'1','antiguedad'=>'0'];
 $offerData=['ofertas_form'=>1,'ofertas'=>[1=>['activa'=>1,'precio'=>'100000','moneda'=>'USD'],2=>['activa'=>1,'precio'=>'450000','moneda'=>'ARS'],3=>['activa'=>1,'precio'=>'800','moneda'=>'USD']],'caracteristicas_form'=>1,'caracteristicas'=>[1,2,8]];
 $id=(new SolicitudPropiedadDAO($db))->create($ids['cliente'],$request,[],$offerData);
 execute_sql('UPDATE solicitudes_publicacion SET id_agente=? WHERE id_solicitud=?',[$ids['agente'],$id]);
 $tax=ExpedienteDAO::tax(['impuesto_concepto'=>'ABL','impuesto_base'=>'125000.50','impuesto_porcentaje'=>'3.25','impuesto_moneda'=>'ARS','impuesto_periodo'=>'2026-10','impuesto_importe'=>'1']);
 if ($tax[3]!=='4062.52') throw new RuntimeException('Cálculo incorrecto.');
 foreach (['-1','101','abc','1e2'] as $invalid) {
  try { ExpedienteDAO::tax(['impuesto_concepto'=>'ABL','impuesto_base'=>'100','impuesto_porcentaje'=>$invalid,'impuesto_moneda'=>'ARS','impuesto_periodo'=>'2026-10']);throw new LogicException('Porcentaje inválido aceptado'); } catch (RuntimeException $expected) {}
 }
 echo "OK: cálculo con redondeo y rechazo de porcentajes inválidos.\n";
 $root=dirname(__DIR__);$router=tempnam(sys_get_temp_dir(),'inmo-router-');
 file_put_contents($router,'<?php $root='.var_export($root,true).'; $url=parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH); $relative=substr($url,strlen("/Inmobiliaria/")); if(!str_starts_with($url,"/Inmobiliaria/") || str_contains($relative,"..") || preg_match("~^(sistema|configuracion|base_datos|herramientas)/~",$relative)){http_response_code(403);exit;} $path=$root."/".($relative?:"index.php"); if(!is_file($path)){http_response_code(404);exit;} if(pathinfo($path,PATHINFO_EXTENSION)==="php"){require $path;}else{header("Content-Type: ".(["css"=>"text/css","js"=>"application/javascript","jpg"=>"image/jpeg","svg"=>"image/svg+xml"][pathinfo($path,PATHINFO_EXTENSION)]??"application/octet-stream"));readfile($path);}');
 $port=random_int(18700,19000);
 $process=proc_open([PHP_BINARY,'-S','127.0.0.1:'.$port,$router],[0=>['pipe','r'],1=>['file','NUL','a'],2=>['file','NUL','a']],$pipes,$root);
 if (!is_resource($process)) throw new RuntimeException('Servidor temporal no disponible');
 putenv('INMO_TEST_URL=http://127.0.0.1:'.$port.'/Inmobiliaria');putenv('INMO_TEST_REQUEST='.$id);
 $node=getenv('INMO_NODE')?:'C:/Users/camii/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node.exe';
 $browser=proc_open([$node,__DIR__.'/probar-expediente.cjs'],[0=>['pipe','r'],1=>STDOUT,2=>STDERR],$nodePipes,$root);
 $code=proc_close($browser);if($code!==0)throw new RuntimeException('Falló prueba de navegador');
 $saved=query_rows('SELECT * FROM propiedad_impuestos WHERE id_solicitud=?',[$id]);
 if(count($saved)!==1 || $saved[0]['importe']!=='4062.52' || !$saved[0]['id_propiedad'])throw new RuntimeException('Persistencia o aprobación incorrecta');
 $docs=query_rows('SELECT * FROM documentos WHERE id_solicitud=?',[$id]);
 if(count($docs)!==1 || !$docs[0]['id_propiedad'])throw new RuntimeException('Documentos no vinculados');
 echo "OK: impuesto y documento persisten y se vinculan al aprobar.\n";
 $property=(int)$docs[0]['id_propiedad'];$offers=new OfertaDAO($db);
 if(count($offers->read('propiedad',$property))!==3 || count($offers->features('propiedad',$property))!==3)throw new RuntimeException('No se transfirieron ofertas/características');
 $catalog=new PropiedadDAO($db);
 if(!array_filter($catalog->findPublished(['operation'=>'Alquiler','currency'=>'ARS','max_price'=>450000]),fn($r)=>(int)$r['id_propiedad']===$property))throw new RuntimeException('No encontró alquiler en ARS');
 if(array_filter($catalog->findPublished(['operation'=>'Venta','currency'=>'ARS']),fn($r)=>(int)$r['id_propiedad']===$property))throw new RuntimeException('Mezcló monedas entre operaciones');
 (new GestionDAO($db))->closeOperation(['propiedad'=>$property.':2','cliente'=>$ids['otro'],'monto'=>'440000','inicio'=>'2026-11-01','fin'=>'2027-11-01'],['id'=>$ids['admin'],'role'=>'Administrador']);
 $rental=query_rows('SELECT * FROM alquileres WHERE id_propiedad=?',[$property])[0];
 if($rental['moneda']!=='ARS'||(int)$rental['id_tipo_transaccion']!==2)throw new RuntimeException('Cierre con moneda incorrecta');
 // Desmarcar una opción elimina solo esa oferta; una selección inválida no modifica nada.
 $db->beginTransaction();$offers->save('solicitud',$id,['ofertas_form'=>1,'ofertas'=>[1=>['activa'=>1,'precio'=>'100000','moneda'=>'USD']],'caracteristicas_form'=>1,'caracteristicas'=>[8]]);$db->commit();
 if(count($offers->read('solicitud',$id))!==1||count($offers->features('solicitud',$id))!==1)throw new RuntimeException('No eliminó opciones desmarcadas');
 $db->beginTransaction();try{$offers->save('solicitud',$id,['ofertas_form'=>1,'ofertas'=>[]]);throw new LogicException('Aceptó cero ofertas');}catch(RuntimeException $expected){$db->rollBack();}
 if(count($offers->read('solicitud',$id))!==1)throw new RuntimeException('Se perdieron ofertas tras error');
 echo "OK: tres ofertas/características, filtros sin cruzar monedas, alquiler ARS y deselección segura.\n";

} finally {
 if(is_resource($process)){proc_terminate($process);proc_close($process);}
 if($router && is_file($router))unlink($router);
 if(isset($db)) foreach($db->query('SELECT archivo FROM documentos')->fetchAll(PDO::FETCH_COLUMN) as $file) if(preg_match('/^[a-f0-9]{48}\.(pdf|jpg|png|webp)$/D',$file)){ $path=dirname(__DIR__).'/sistema/privados/'.$file;if(is_file($path))unlink($path); }
 $server->exec("DROP DATABASE `$name`");echo "Base temporal y archivos de prueba eliminados.\n";
}
