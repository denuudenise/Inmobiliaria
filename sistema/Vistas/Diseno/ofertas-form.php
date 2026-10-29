<?php
$offerDAO=new OfertaDAO(Database::connection());
$existingOffers=isset($offerId)&&$offerId?$offerDAO->read($offerKind,(int)$offerId):[];
$selectedOffers=[];foreach($existingOffers as $existing)$selectedOffers[$existing['id_tipo_transaccion']]=['activa'=>1,'precio'=>$existing['precio'],'moneda'=>$existing['moneda']];
if(isset($_POST['ofertas_form']))$selectedOffers=is_array($_POST['ofertas']??null)?$_POST['ofertas']:[];
$selectedFeatures=isset($offerId)&&$offerId?array_column($offerDAO->features($offerKind,(int)$offerId),'id_caracteristica'):[];
if(isset($_POST['caracteristicas_form']))$selectedFeatures=is_array($_POST['caracteristicas']??null)?$_POST['caracteristicas']:[];
?>
<fieldset class="private-fields full offer-fields"><legend>Operaciones y precios</legend>
<p>Podés seleccionar una, dos o las tres opciones. Cada operación tiene su propio precio y moneda.</p>
<input type="hidden" name="ofertas_form" value="1">
<?php foreach(query_rows('SELECT * FROM tipos_transaccion ORDER BY id_tipo_transaccion') as $type): $oid=(int)$type['id_tipo_transaccion'];$selected=$selectedOffers[$oid]??[]; ?>
<div class="offer-row" data-offer-row>
<label class="check-option"><input type="checkbox" name="ofertas[<?= $oid ?>][activa]" value="1" <?= !empty($selected['activa'])?'checked':'' ?>><span><?= e($type['nombre']) ?></span></label>
<div class="field"><label for="offer-price-<?= $oid ?>">Precio<?= $oid!==1?' mensual':'' ?></label><input id="offer-price-<?= $oid ?>" name="ofertas[<?= $oid ?>][precio]" type="number" min="0.01" max="9999999999.99" step="0.01" value="<?= e((string)($selected['precio']??'')) ?>"></div>
<div class="field"><label for="offer-currency-<?= $oid ?>">Moneda</label><select id="offer-currency-<?= $oid ?>" name="ofertas[<?= $oid ?>][moneda]"><?php foreach(['ARS'=>'Pesos (ARS)','USD'=>'Dólares (USD)'] as $currency=>$label): ?><option value="<?= $currency ?>" <?= ($selected['moneda']??'ARS')===$currency?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
</div><?php endforeach; ?>
</fieldset>
<fieldset class="private-fields full"><legend>Características adicionales</legend><input type="hidden" name="caracteristicas_form" value="1"><div class="feature-options">
<?php foreach(query_rows('SELECT * FROM caracteristicas ORDER BY nombre') as $feature): ?><label class="check-option"><input type="checkbox" name="caracteristicas[]" value="<?= (int)$feature['id_caracteristica'] ?>" <?= in_array($feature['id_caracteristica'],$selectedFeatures)?'checked':'' ?>><span><?= e($feature['nombre']) ?></span></label><?php endforeach; ?>
</div></fieldset>
