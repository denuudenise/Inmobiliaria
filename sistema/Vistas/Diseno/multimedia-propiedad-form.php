<?php
$mediaRows=$mediaPropertyId>0?query_rows("SELECT id_multimedia,url_archivo,tipo FROM propiedad_multimedia WHERE id_propiedad=? ORDER BY orden,id_multimedia",[$mediaPropertyId]):[];
$mediaPhotos=array_values(array_filter($mediaRows,static fn($m)=>$m['tipo']==='Imagen'&&Archivos::isLocalFile((string)$m['url_archivo'])));
$mediaVideos=array_values(array_filter($mediaRows,static fn($m)=>$m['tipo']==='Video'&&Archivos::isLocalFile((string)$m['url_archivo'])));
$mediaVideo=$mediaVideos[0]??null;
?>
<div class="field full media-manager" data-media-manager>
    <h3>Galería de la propiedad</h3>
    <p class="help-text">Ordená las fotos con las flechas. La primera será la portada que se verá en el catálogo. Después podés poner ambientes y, al final, detalles o exteriores.</p>
    <ol class="media-sort-list" data-sort-list>
        <?php foreach($mediaPhotos as $position=>$photo): ?><li data-media-item="e:<?= (int)$photo['id_multimedia'] ?>"><img src="<?= e($photo['url_archivo']) ?>" alt="Foto <?= $position+1 ?>"><span data-position-label><?= $position===0?'Portada':'Foto '.($position+1) ?></span><button type="button" data-move="up" aria-label="Subir foto">↑</button><button type="button" data-move="down" aria-label="Bajar foto">↓</button><button type="button" data-remove-media class="media-remove" aria-label="Eliminar foto">×</button></li><?php endforeach; ?>
    </ol>
    <input type="hidden" name="orden_fotos" value="" data-order-value>
    <label for="fotos">Agregar fotos (JPG, PNG o WEBP, hasta 8 por carga; 2 MB por foto)</label>
    <input id="fotos" name="fotos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple data-new-photos>
    <small>Las miniaturas muestran la vista previa. Podés mover también las nuevas fotos antes de guardar.</small>
    <div class="media-video-box">
        <label for="video">Video opcional (MP4, hasta 20 MB)</label>
        <?php if($mediaVideo): ?><video controls preload="metadata" src="<?= e($mediaVideo['url_archivo']) ?>"></video><label class="check-row"><input type="checkbox" name="eliminar_video" value="1"> Eliminar el video actual</label><?php else: ?><p class="help-text">Todavía no hay un video cargado.</p><?php endif; ?>
        <input id="video" name="video" type="file" accept="video/mp4" data-property-video>
        <?php if($mediaVideo): ?><small>Si elegís otro MP4, reemplazará el actual.</small><?php endif; ?>
    </div>
</div>
