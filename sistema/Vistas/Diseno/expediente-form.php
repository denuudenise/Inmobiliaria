<fieldset class="private-fields full">
<legend>Documentación privada</legend>
<p class="help-text">Solo pueden consultarla el propietario, administración y el agente responsable. PDF, JPG, PNG o WEBP; hasta 8 documentos de 2 MB por envío.</p>
<div class="private-grid">
<?php foreach (['planos'=>'Planos','escrituras'=>'Escrituras','comprobantes'=>'Comprobantes'] as $key=>$label): ?>
<div class="field"><label for="<?= $key ?>"><?= $label ?></label><input id="<?= $key ?>" type="file" name="<?= $key ?>[]" accept="application/pdf,image/jpeg,image/png,image/webp" multiple data-private-files></div>
<?php endforeach; ?>
</div>
</fieldset>
<fieldset class="private-fields full" data-tax-form>
<legend>Agregar impuesto (opcional)</legend>
<p class="help-text">Importe = base × porcentaje / 100. Podés agregar más impuestos desde la ficha.</p>
<div class="private-grid">
<?php foreach (['concepto'=>['Concepto','text'],'base'=>['Base imponible','number'],'porcentaje'=>['Porcentaje (%)','number'],'periodo'=>['Período','month']] as $key=>$cfg): ?>
<div class="field"><label for="impuesto_<?= $key ?>"><?= $cfg[0] ?></label><input id="impuesto_<?= $key ?>" name="impuesto_<?= $key ?>" type="<?= $cfg[1] ?>" value="<?= e((string)($_POST['impuesto_'.$key]??'')) ?>" <?= $cfg[1]==='number'?'min="0" step="0.01"':'' ?> <?= $key==='porcentaje'?'max="100"':'' ?> <?= $key==='concepto'?'maxlength="100"':'' ?>></div>
<?php endforeach; ?>
<div class="field"><label for="impuesto_moneda">Moneda del impuesto</label><select id="impuesto_moneda" name="impuesto_moneda"><?php foreach (['ARS','USD'] as $currency): ?><option <?= ($_POST['impuesto_moneda']??'ARS')===$currency?'selected':'' ?>><?= $currency ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Importe calculado</label><output data-tax-total aria-live="polite">—</output></div>
</div>
</fieldset>
