<?php
require __DIR__.'/sistema/Nucleo/inicio.php';
$user=require_user(['Cliente']);
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD']==='GET') {
 echo json_encode(array_map('intval',array_column(query_rows('SELECT id_propiedad FROM intereses WHERE id_cliente=? AND es_favorito=1',[$user['id']]),'id_propiedad'))); exit;
}
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
verify_csrf(); $id=(int)($_POST['id']??0);
if (($_POST['guardar']??'')==='1') {
 if (!(new PropiedadDAO(Database::connection()))->findById($id)) { http_response_code(404); echo json_encode(['error'=>'La propiedad ya no está disponible.']); exit; }
 execute_sql("INSERT INTO intereses (id_cliente,id_propiedad,es_favorito) VALUES (?,?,1) ON DUPLICATE KEY UPDATE es_favorito=1",[$user['id'],$id]);
} else execute_sql('UPDATE intereses SET es_favorito=0 WHERE id_cliente=? AND id_propiedad=?',[$user['id'],$id]);
echo json_encode(['ok'=>true]);
