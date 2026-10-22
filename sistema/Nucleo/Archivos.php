<?php
declare(strict_types=1);
final class Archivos
{
    public static function isLocalFile(string $url): bool
    {
        $root = realpath(dirname(__DIR__, 2));
        if ($root === false || $url === '' || str_contains($url, "\0")) return false;

        $relativePath = str_starts_with($url, '/Inmobiliaria/')
            ? substr($url, strlen('/Inmobiliaria/'))
            : ltrim($url, '/');
        $file = realpath($root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));

        return $file !== false
            && str_starts_with(strtolower($file), strtolower($root . DIRECTORY_SEPARATOR))
            && is_file($file);
    }

    public static function images(string $field): array
    {
        if (!isset($_FILES[$field])) return [];
        $upload=$_FILES[$field]; $paths=[];
        if (!is_array($upload['name']) || count($upload['name'])>8) throw new RuntimeException('Podés subir hasta ocho fotos.');
        $directory=dirname(__DIR__,2).'/archivos/propiedades';
        if (!is_dir($directory) && !mkdir($directory,0750,true)) throw new RuntimeException('No se pudo preparar la carpeta de fotos.');
        try {
            foreach ($upload['name'] as $i=>$name) {
                if ($upload['error'][$i]===UPLOAD_ERR_NO_FILE) continue;
                if ($upload['error'][$i]!==UPLOAD_ERR_OK || $upload['size'][$i]>2*1024*1024) throw new RuntimeException('Cada foto debe pesar hasta 2 MB.');
                $tmp=$upload['tmp_name'][$i];
                $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
                $extension=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
                if (!$extension || !is_uploaded_file($tmp)) throw new RuntimeException('Usá imágenes JPG, PNG o WEBP.');
                $filename=bin2hex(random_bytes(16)).'.'.$extension;
                if (!move_uploaded_file($tmp,$directory.'/'.$filename)) throw new RuntimeException('No se pudo guardar la foto.');
                $paths[]='/Inmobiliaria/archivos/propiedades/'.$filename;
            }
            return $paths;
        } catch(Throwable $e) { self::remove($paths); throw $e; }
    }
    public static function remove(array $paths): void {
        foreach($paths as $path) {
            if (preg_match('~^/Inmobiliaria/archivos/propiedades/[a-f0-9]{32}\.(jpg|png|webp)$~',$path)) {
                $file=dirname(__DIR__,2).substr($path,strlen('/Inmobiliaria'));
                if (is_file($file)) unlink($file);
            }
        }
    }
}
