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
                $safeName=basename(str_replace('\\','/',(string)$name));
                if ($upload['error'][$i]!==UPLOAD_ERR_OK) throw new RuntimeException('No se pudo cargar la foto "'.$safeName.'". Revisá el archivo e intentá nuevamente.');
                if ($upload['size'][$i]>2*1024*1024) throw new RuntimeException('La foto "'.$safeName.'" supera el límite de 2 MB.');
                $tmp=$upload['tmp_name'][$i];
                $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
                $extension=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
                if (!$extension || !is_uploaded_file($tmp)) throw new RuntimeException('La foto "'.$safeName.'" debe ser JPG, PNG o WEBP.');
                $filename=bin2hex(random_bytes(16)).'.'.$extension;
                if (!move_uploaded_file($tmp,$directory.'/'.$filename)) throw new RuntimeException('No se pudo guardar la foto.');
                $paths[]='/Inmobiliaria/archivos/propiedades/'.$filename;
            }
            return $paths;
        } catch(Throwable $e) { self::remove($paths); throw $e; }
    }
    public static function video(string $field): ?string
    {
        if (!isset($_FILES[$field]) || (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        $upload=$_FILES[$field]; $tmp=(string)($upload['tmp_name']??'');
        if ((int)($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || (int)($upload['size']??0)<=0 || (int)$upload['size']>20*1024*1024 || !is_uploaded_file($tmp)) throw new RuntimeException('El video debe ser MP4 y pesar hasta 20 MB.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $header=file_get_contents($tmp,false,null,0,16) ?: '';
        if ($mime!=='video/mp4' || strlen($header)<8 || substr($header,4,4)!=='ftyp') throw new RuntimeException('Subí un video MP4 válido.');
        $directory=dirname(__DIR__,2).'/archivos/propiedades';
        if (!is_dir($directory) && !mkdir($directory,0750,true)) throw new RuntimeException('No se pudo preparar la carpeta de videos.');
        $filename=bin2hex(random_bytes(16)).'.mp4';
        if (!move_uploaded_file($tmp,$directory.'/'.$filename)) throw new RuntimeException('No se pudo guardar el video.');
        return '/Inmobiliaria/archivos/propiedades/'.$filename;
    }
    public static function remove(array $paths): void {
        foreach($paths as $path) {
            if (preg_match('~^/Inmobiliaria/archivos/(?:propiedades|solicitudes)/[a-f0-9]{32}\.(jpg|png|webp|mp4)$~',$path)) {
                $file=dirname(__DIR__,2).substr($path,strlen('/Inmobiliaria'));
                if (is_file($file)) unlink($file);
            }
        }
    }
    public static function removeUnreferenced(PDO $db,array $paths): void
    {
        $check=$db->prepare('SELECT 1 FROM propiedad_multimedia WHERE url_archivo=? UNION ALL SELECT 1 FROM solicitud_multimedia WHERE url_archivo=? LIMIT 1');
        $orphaned=[];
        foreach(array_unique($paths) as $path){$check->execute([$path,$path]);if(!$check->fetchColumn())$orphaned[]=$path;}
        self::remove($orphaned);
    }
    public static function orderedImages(string $json, array $existing, int $newCount): array
    {
        $oldIds=array_map(static fn($row)=>(int)$row['id_multimedia'],$existing);
        if (trim($json)==='') return [...array_map(static fn($id)=>['kind'=>'existing','id'=>$id],$oldIds),...array_map(static fn($i)=>['kind'=>'new','index'=>$i],$newCount?range(0,$newCount-1):[])];
        $entries=json_decode($json,true);
        if (!is_array($entries)) throw new RuntimeException('No se pudo leer el orden de las fotos. Volvé a ordenarlas e intentá nuevamente.');
        $allowed=array_fill_keys($oldIds,true);$seenOld=[];$seenNew=[];$ordered=[];
        foreach($entries as $entry){
            if(!is_string($entry)) throw new RuntimeException('El orden de las fotos no es válido.');
            if(preg_match('/^e:(\d+)$/',$entry,$m)){ $id=(int)$m[1];if(!isset($allowed[$id])||isset($seenOld[$id]))throw new RuntimeException('El orden de las fotos no coincide con esta propiedad.');$seenOld[$id]=true;$ordered[]=['kind'=>'existing','id'=>$id]; }
            elseif(preg_match('/^n:(\d+)$/',$entry,$m)){ $i=(int)$m[1];if($i>=$newCount||isset($seenNew[$i]))throw new RuntimeException('El orden de las fotos nuevas no es válido.');$seenNew[$i]=true;$ordered[]=['kind'=>'new','index'=>$i]; }
            else throw new RuntimeException('El orden de las fotos no es válido.');
        }
        if(count($seenOld)!==count($oldIds)||count($seenNew)!==$newCount)throw new RuntimeException('Falta una foto en el orden seleccionado.');
        return $ordered;
    }
}
