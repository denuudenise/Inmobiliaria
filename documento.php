<?php
declare(strict_types=1);
require __DIR__.'/sistema/Nucleo/inicio.php';
$user=require_user();
$rows=query_rows('SELECT * FROM documentos WHERE id_documento=?',[(int)($_GET['id']??0)]);
$doc=$rows[0]??null;
try {
    if (!$doc) throw new RuntimeException('No disponible.');
    (new ExpedienteDAO(Database::connection()))->context($doc['id_propiedad']?'propiedad':'solicitud',(int)($doc['id_propiedad']?:$doc['id_solicitud']),$user);
    // Solo se sirven archivos privados administrados por esta web; nunca rutas del usuario.
    if (!preg_match('/^[a-f0-9]{48}\.(pdf|jpg|png|webp)$/D',$doc['archivo'])) throw new RuntimeException('Archivo no disponible en este equipo.');
    $path=__DIR__.'/sistema/privados/'.$doc['archivo'];
    if (!is_file($path)) throw new RuntimeException('Archivo no disponible.');
} catch (RuntimeException $e) { http_response_code(404); exit('Documento no disponible.'); }
header('Content-Type: '.(new finfo(FILEINFO_MIME_TYPE))->file($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');
header("Content-Disposition: attachment; filename*=UTF-8''".rawurlencode($doc['nombre']));
header('Content-Length: '.filesize($path));
readfile($path);
