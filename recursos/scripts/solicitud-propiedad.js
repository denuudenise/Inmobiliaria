document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('solicitud-propiedad-form');
  if (!form) return;

  const controls = [...form.querySelectorAll('[data-validate]')];
  const messages = {
    tipo: 'Seleccioná un tipo de propiedad.',
    titulo: 'Usá entre 5 y 200 caracteres con letras y números; revisá los símbolos.',
    direccion: 'Ingresá una calle y altura de 3 a 255 caracteres con al menos una letra.',
    localidad: 'Ingresá una localidad de 2 a 100 caracteres con al menos una letra.',
    descripcion: 'Escribí una descripción de 10 a 3000 caracteres.',
    superficie: 'Ingresá una superficie mayor que 0 y de hasta 999.999 m².',
    entero: 'Usá un número entero entre 0 y 1000 o dejá el campo vacío.',
    checkbox: 'Marcá esta casilla para poder enviar la solicitud.',
    document_notes: 'Si te falta un documento, explicá la situación en al menos 10 caracteres.'
  };
  const checks = {
    tipo: value => value !== '',
    titulo: value => value.length >= 5 && value.length <= 200 && /[\p{L}\p{N}]/u.test(value) && /^[\p{L}\p{M}\p{N}\s.,:;()\/#º°&+'-]+$/u.test(value),
    direccion: value => value.length >= 3 && value.length <= 255 && /[\p{L}\p{M}]/u.test(value) && /^[\p{L}\p{M}\p{N}\s.,#º°/'-]+$/u.test(value),
    localidad: value => value.length >= 2 && value.length <= 100 && /[\p{L}\p{M}]/u.test(value) && /^[\p{L}\p{M}\p{N}\s.,'-]+$/u.test(value),
    descripcion: value => value.length >= 10 && value.length <= 3000 && /[\p{L}\p{N}]/u.test(value),
    superficie: (value, el) => value !== '' && Number.isFinite(Number(value)) && Number(value) > 0 && Number(value) <= 999999 && el.validity.stepMismatch === false,
    entero: (value, el) => value === '' || (Number.isInteger(Number(value)) && Number(value) >= 0 && Number(value) <= 1000),
    checkbox: (value, el) => el.checked,
    document_notes: (value, el) => !el.required || (value.length >= 10 && value.length <= 1000)
  };

  function validate(el, force = false) {
    const name = el.dataset.validate;
    const value = el.type === 'checkbox' ? '' : el.value.trim();
    const empty = el.required && (el.type === 'checkbox' ? !el.checked : value === '');
    const valid = !empty && checks[name](value, el);
    const show = force || el.dataset.touched === 'true';
    const field = el.closest('.field');
    const feedback = document.getElementById(`ayuda-${el.name}`) || (field ? field.querySelector('.field-feedback') : null);
    el.setAttribute('aria-invalid', show && !valid ? 'true' : 'false');
    el.setCustomValidity(show && !valid ? (empty ? 'Completá este campo.' : messages[name]) : '');
    field?.classList.toggle('has-error', show && !valid);
    field?.classList.toggle('is-valid', show && valid && value !== '');
    if (feedback && show) {
      feedback.textContent = !valid ? (empty ? 'Este campo es obligatorio.' : messages[name]) : (value ? 'Correcto.' : (el.dataset.hint || ''));
    }
    return valid;
  }

  controls.forEach(el => {
    if (el.getAttribute('aria-invalid') === 'true') el.dataset.touched = 'true';
    const feedback = document.getElementById(`ayuda-${el.name}`);
    el.addEventListener('focus', () => {
      el.dataset.touched = 'true';
      el.closest('.field')?.classList.add('is-touched');
      if (el.value || el.type === 'checkbox') validate(el);
      else if (feedback) feedback.textContent = el.dataset.hint || 'Completá este campo.';
    });
    el.addEventListener('blur', () => { el.dataset.touched = 'true'; validate(el); });
    el.addEventListener('input', () => { if (el.dataset.touched === 'true') validate(el); });
    el.addEventListener('change', () => { el.dataset.touched = 'true'; validate(el); });
  });

  function validateOffers() {
    const group = form.querySelector('.offer-fields');
    if (!group) return true;
    const rows = [...group.querySelectorAll('[data-offer-row]')];
    const selected = rows.filter(row => row.querySelector('input[type="checkbox"]').checked);
    const feedback = group.querySelector('#ayuda-ofertas');
    let message = selected.length ? '' : 'Seleccioná al menos una operación.';
    group.querySelectorAll('[data-offer-row] input[type="checkbox"]').forEach((checkbox,index)=>{
      checkbox.setAttribute('aria-invalid',!selected.length && index===0?'true':'false');
    });
    for (const row of selected) {
      const numericFields = [...row.querySelectorAll('.offer-details input[type="number"]')];
      const price = numericFields[0];
      if (!price.value || Number(price.value) <= 0 || Number(price.value) > 9999999999.99 || price.validity.stepMismatch) {
        message = 'Ingresá un precio mayor a cero para cada operación seleccionada.';
        price.dataset.touched = 'true';
        validateNumber(price, message);
        break;
      }
      clearNumberError(price);
      const minimum = row.querySelector('[data-minimum-price]');
      if (minimum && (!minimum.value || Number(minimum.value) <= 0 || Number(minimum.value) > Number(price.value) || minimum.validity.stepMismatch)) {
        message = 'El monto mínimo debe ser mayor a cero y no puede superar el precio de venta.';
        minimum.dataset.touched = 'true';
        validateNumber(minimum, message);
        break;
      }
      if (minimum) clearNumberError(minimum);
    }
    rows.filter(row => !row.querySelector('input[type="checkbox"]').checked).forEach(row=>row.querySelectorAll('[data-live-error]').forEach(error=>{error.remove();error.closest('.field')?.classList.remove('has-error')}));
    if (feedback) feedback.textContent = message;
    group.classList.toggle('has-error', Boolean(message));
    return !message;
  }

  function validateRequiredDocuments() {
    let valid=true;
    for (const [fieldName, checkboxName, label, pronoun] of [['planos','faltan_planos','los planos','los tenés'],['escrituras','falta_escritura','la escritura','la tenés']]) {
      const input=form.querySelector(`[name="${fieldName}[]"]`);
      const missing=form.querySelector(`[name="${checkboxName}"]`);
      const hasFile=input && input.files.length>0;
      if (!hasFile && !missing?.checked) {
        valid=false;
        input?.setAttribute('aria-invalid','true');
        input?.parentElement.classList.add('has-error');
        let message=input?.parentElement.querySelector('[data-required-document-error]');
        if(!message && input?.parentElement){message=document.createElement('small');message.dataset.requiredDocumentError='';message.className='field-feedback field-error';input.parentElement.append(message)}
        if(message)message.textContent=`Adjuntá ${label} o marcá que todavía no ${pronoun}.`;
      } else {
        input?.setAttribute('aria-invalid','false');
        input?.parentElement.querySelector('[data-required-document-error]')?.remove();
        input?.parentElement.classList.remove('has-error');
      }
    }
    const notes=form.querySelector('[data-document-notes]');
    if(notes?.required && notes.value.trim().length<10)valid=false;
    return valid;
  }

  function validateMedia() {
    const input=form.querySelector('[name="fotos[]"]');
    const video=form.querySelector('[name="video"]');
    const feedback=document.getElementById('ayuda-multimedia');
    if(!input)return true;
    const files=[...input.files];
    let photoMessage='';
    let videoMessage='';
    if(files.length>8)photoMessage='Podés adjuntar hasta 8 fotos.';
    else {
      const oversized=files.find(file=>file.size<=0||file.size>2*1024*1024);
      const unsupported=files.find(file=>!input.accept.split(',').includes(file.type));
      if(oversized)photoMessage=`La foto "${oversized.name}" supera el límite de 2 MB.`;
      else if(unsupported)photoMessage=`El archivo "${unsupported.name}" no es una foto JPG, PNG o WEBP.`;
    }
    if(video?.files[0]){
      const selectedVideo=video.files[0];
      if(selectedVideo.type!=='video/mp4'||selectedVideo.size>20*1024*1024)videoMessage=`El video "${selectedVideo.name}" debe ser MP4 y pesar hasta 20 MB.`;
    }
    const messages=[photoMessage,videoMessage].filter(Boolean);
    if(feedback)feedback.textContent=messages.join(' ');
    input.setAttribute('aria-invalid',photoMessage?'true':'false');
    video?.setAttribute('aria-invalid',videoMessage?'true':'false');
    return messages.length===0;
  }

  function focusProblem(el, showNativeMessage = false) {
    if (!el) return;
    el.setAttribute('aria-invalid','true');
    if(!el.closest('.media-manager'))el.closest('.field')?.classList.add('has-error');
    const section=el.closest('.field, .offer-fields, .file-picker') || el;
    section.scrollIntoView({behavior:'smooth',block:'center'});
    el.focus({preventScroll:true});
    if(showNativeMessage && typeof el.reportValidity==='function') el.reportValidity();
  }

  function offerProblemTarget() {
    const group=form.querySelector('.offer-fields');
    if(!group?.classList.contains('has-error')) return null;
    const selectedRows=[...group.querySelectorAll('[data-offer-row]')]
      .filter(row=>row.querySelector('input[type="checkbox"]')?.checked);
    for(const row of selectedRows){
      const fields=[...row.querySelectorAll('.offer-details input[type="number"]')]
        .filter(input=>!input.closest('[hidden]'));
      const invalid=fields.find(input=>!input.value || Number(input.value)<=0 || !input.validity.valid);
      if(invalid)return invalid;
    }
    return group.querySelector('input[type="checkbox"]');
  }

  function validateNumber(el, message) {
    const field = el.closest('.field');
    let feedback = field?.querySelector('[data-live-error]');
    if (!feedback && field) {
      feedback = document.createElement('small');
      feedback.dataset.liveError = '';
      feedback.className = 'field-feedback field-error';
      field.append(feedback);
    }
    el.setAttribute('aria-invalid','true');
    if (feedback) feedback.textContent = message;
    field?.classList.add('has-error');
  }

  function clearNumberError(el) {
    const field=el.closest('.field');
    el.setAttribute('aria-invalid','false');
    field?.querySelector('[data-live-error]')?.remove();
    field?.classList.remove('has-error');
  }

  const offerGroup=form.querySelector('.offer-fields');
  offerGroup?.addEventListener('input',validateOffers);
  offerGroup?.addEventListener('change',validateOffers);
  form.querySelectorAll('[name="faltan_planos"],[name="falta_escritura"],[name="planos[]"],[name="escrituras[]"]').forEach(el=>el.addEventListener('change',validateRequiredDocuments));
  form.querySelector('[data-document-notes]')?.addEventListener('input',el=>{if(el.currentTarget.dataset.touched==='true')validate(el.currentTarget)});
  form.querySelector('[name="fotos[]"]')?.addEventListener('change',validateMedia);
  form.querySelector('[name="video"]')?.addEventListener('change',validateMedia);

  if(form.dataset.serverErrors==='true'){
    const firstField=form.querySelector('.field.has-error, .offer-fields.has-error');
    let serverTarget=firstField?.querySelector('input:not([type="hidden"]),select,textarea');
    if(!serverTarget){
      const mediaField=form.querySelector('.media-manager[data-server-media-error]:not([data-server-media-error=""])');
      const serverMessage=mediaField?.dataset.serverMediaError||'';
      if(mediaField){
        serverTarget=/^el video/i.test(serverMessage)
          ? form.querySelector('[name="video"]')
          : form.querySelector('[name="fotos[]"]');
      }
    }
    focusProblem(serverTarget || offerProblemTarget());
  }

  form.addEventListener('submit', event => {
    const invalid = controls.filter(el => !validate(el, true));
    const offersValid = validateOffers();
    const documentsValid=validateRequiredDocuments();
    const mediaValid=validateMedia();
    const invalidFiles=[...form.querySelectorAll(':invalid')].filter(el=>!el.disabled);
    if (invalid.length || !offersValid || !documentsValid || !mediaValid || invalidFiles.length) {
      event.preventDefault();
      const first = invalid[0]
        || invalidFiles[0]
        || form.querySelector('.file-picker.has-error input[type="file"]')
        || offerProblemTarget()
        || form.querySelector('[name="fotos[]"][aria-invalid="true"], [name="video"][aria-invalid="true"]');
      if(first)focusProblem(first,true);
    }
  });
});
