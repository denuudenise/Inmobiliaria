<fieldset class="private-fields full">
<legend>Documentación privada</legend>
<p class="help-text">Solo pueden consultarla el propietario, administración y el agente responsable. Podés adjuntar hasta 8 documentos en total, de hasta 2 MB cada uno. Si todavía no tenés los planos o la escritura, podés indicarlo y enviar la solicitud igual.</p>
<div class="private-grid">
<?php foreach (['planos'=>'Planos','escrituras'=>'Escrituras','comprobantes'=>'Comprobantes'] as $key=>$label): ?>
<div class="field file-picker" data-file-picker>
<label for="<?= $key ?>"><?= $label ?></label>
<input id="<?= $key ?>" type="file" name="<?= $key ?>[]" accept="application/pdf,image/jpeg,image/png,image/webp" multiple data-private-files data-accumulate-files>
<button class="btn btn-outline" type="button" data-add-files="<?= $key ?>">＋ Agregar archivos</button>
<ul class="selected-files" data-selected-files="<?= $key ?>" aria-live="polite"></ul>
</div>
<?php endforeach; ?>
</div>
<div class="field full"><span class="field-label">¿Te faltan documentos?</span>
<label class="check-row"><input type="checkbox" name="faltan_planos" value="1" data-document-missing <?= (($_SERVER['REQUEST_METHOD']??'GET')==='POST'?!empty($_POST['faltan_planos']):!empty($documentState['faltan_planos']??false))?'checked':'' ?>> Todavía no tengo los planos</label>
<label class="check-row"><input type="checkbox" name="falta_escritura" value="1" data-document-missing <?= (($_SERVER['REQUEST_METHOD']??'GET')==='POST'?!empty($_POST['falta_escritura']):!empty($documentState['falta_escritura']??false))?'checked':'' ?>> Todavía no tengo la escritura</label>
<label for="observaciones_documentacion">Aclaraciones sobre la documentación</label>
<textarea id="observaciones_documentacion" name="observaciones_documentacion" maxlength="1000" data-document-notes placeholder="Por ejemplo: estoy solicitando una copia de la escritura y la enviaré cuando la tenga."><?= e((string)($_POST['observaciones_documentacion']??$documentState['observaciones_documentacion']??'')) ?></textarea>
<small>Si marcás que te falta algún documento, contanos brevemente el motivo o cuándo pensás presentarlo.</small>
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
