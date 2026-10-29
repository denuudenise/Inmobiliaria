document.addEventListener('DOMContentLoaded',()=>{
 document.querySelectorAll('[data-tax-form]').forEach(form=>{
  const calculate=()=>{
   const base=form.querySelector('[name=impuesto_base]').value,rate=form.querySelector('[name=impuesto_porcentaje]').value;
   const currency=form.querySelector('[name=impuesto_moneda]').value;
   const total=form.querySelector('[data-tax-total]');
   total.textContent=base!==''&&rate!==''&&Number(base)>0&&Number(rate)>=0&&Number(rate)<=100 ? currency+' '+(Math.round(Math.round(Number(base)*100)*Math.round(Number(rate)*100)/10000)/100).toLocaleString('es-AR',{minimumFractionDigits:2,maximumFractionDigits:2}) : '—';
  };form.addEventListener('input',calculate);calculate();
 });
 document.querySelectorAll('[data-private-files]').forEach(input=>{
  const status=document.createElement('p');status.className='help-text';status.setAttribute('role','status');input.after(status);
  input.addEventListener('change',()=>{
   const files=Array.from(input.files);let error='';
   const count=Array.from(input.form.querySelectorAll('[data-private-files]')).reduce((sum,el)=>sum+el.files.length,0);
   if(count>8)error='Máximo 8 documentos privados por envío.';
   if(files.some(f=>f.size>2097152||f.size===0))error='Cada documento debe pesar entre 1 byte y 2 MB.';
   if(files.some(f=>!input.accept.split(',').includes(f.type)))error='Usá PDF, JPG, PNG o WEBP.';
   input.setCustomValidity(error);status.textContent=error||files.map(f=>f.name+' · listo para enviar').join(' / ');
   // La validación total se reevalúa en todos los campos al cambiar la selección.
   input.form.querySelectorAll('[data-private-files]').forEach(other=>{if(other!==input && other.validationMessage==='Máximo 8 documentos privados por envío.' && count<=8)other.setCustomValidity('');});
  });
 });
});

document.addEventListener('DOMContentLoaded',()=>{
 document.querySelectorAll('.offer-fields').forEach(group=>{
  const rows=Array.from(group.querySelectorAll('[data-offer-row]'));
  const update=()=>{
   const any=rows.some(row=>row.querySelector('[type=checkbox]').checked);
   rows.forEach((row,index)=>{
    const check=row.querySelector('[type=checkbox]'),price=row.querySelector('[type=number]'),currency=row.querySelector('select');
    price.disabled=!check.checked;currency.disabled=!check.checked;price.required=check.checked;
    check.setCustomValidity(index===0&&!any?'Seleccioná al menos una operación.':'');row.classList.toggle('offer-selected',check.checked);
   });
  };group.addEventListener('change',update);update();
 });
});
