<?php
require __DIR__.'/sistema/Nucleo/inicio.php';
$user=require_user(['Cliente']);
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD']==='GET') {
 echo json_encode(array_map('intval',array_column(query_rows('SELECT id_propiedad FROM favoritos WHERE id_usuario=?',[$user['id']]),'id_propiedad'))); exit;
}
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
verify_csrf(); $id=(int)($_POST['id']??0);
if (($_POST['guardar']??'')==='1') {
 if (!(new PropiedadDAO(Database::connection()))->findById($id)) { http_response_code(404); echo json_encode(['error'=>'La propiedad ya no está disponible.']); exit; }
 execute_sql('INSERT IGNORE INTO favoritos (id_usuario,id_propiedad) VALUES (?,?)',[$user['id'],$id]);
} else execute_sql('DELETE FROM favoritos WHERE id_usuario=? AND id_propiedad=?',[$user['id'],$id]);
echo json_encode(['ok'=>true]);
