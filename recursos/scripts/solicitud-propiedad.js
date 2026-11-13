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
    const valid = empty || checks[name](value, el);
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
    for (const [fieldName, checkboxName, label] of [['planos','faltan_planos','planos'],['escrituras','falta_escritura','escritura']]) {
      const input=form.querySelector(`[name="${fieldName}[]"]`);
      const missing=form.querySelector(`[name="${checkboxName}"]`);
      const hasFile=input && input.files.length>0;
      if (!hasFile && !missing?.checked) {
        valid=false;
        input?.parentElement.classList.add('has-error');
        let message=input?.parentElement.querySelector('[data-required-document-error]');
        if(!message && input?.parentElement){message=document.createElement('small');message.dataset.requiredDocumentError='';message.className='field-feedback field-error';input.parentElement.append(message)}
        if(message)message.textContent=`Adjuntá los ${label} o marcá que todavía no los tenés.`;
      } else {
        input?.parentElement.querySelector('[data-required-document-error]')?.remove();
        input?.parentElement.classList.remove('has-error');
      }
    }
    const notes=form.querySelector('[data-document-notes]');
    if(notes?.required && notes.value.trim().length<10)valid=false;
    return valid;
  }

  function validateMedia() {
    const input=form.querySelector('[name="multimedia[]"]');
    const feedback=document.getElementById('ayuda-multimedia');
    const field=input?.closest('.field');
    if(!input)return true;
    const files=[...input.files];
    let message='';
    if(files.length>8)message='Podés adjuntar hasta 8 fotos o videos.';
    else if(files.some(file=>file.size<=0||file.size>2*1024*1024))message='Cada foto o video debe pesar hasta 2 MB.';
    else if(files.some(file=>!input.accept.split(',').includes(file.type)))message='Usá JPG, PNG, WEBP, MP4 o WEBM.';
    if(feedback)feedback.textContent=message;
    field?.classList.toggle('has-error',Boolean(message));
    return !message;
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
    if (feedback) feedback.textContent = message;
    field?.classList.add('has-error');
  }

  function clearNumberError(el) {
    const field=el.closest('.field');
    field?.querySelector('[data-live-error]')?.remove();
    field?.classList.remove('has-error');
  }

  const offerGroup=form.querySelector('.offer-fields');
  offerGroup?.addEventListener('input',validateOffers);
  offerGroup?.addEventListener('change',validateOffers);
  form.querySelectorAll('[name="faltan_planos"],[name="falta_escritura"],[name="planos[]"],[name="escrituras[]"]').forEach(el=>el.addEventListener('change',validateRequiredDocuments));
  form.querySelector('[data-document-notes]')?.addEventListener('input',el=>{if(el.currentTarget.dataset.touched==='true')validate(el.currentTarget)});
  form.querySelector('[name="multimedia[]"]')?.addEventListener('change',validateMedia);

  form.addEventListener('submit', event => {
    const invalid = controls.filter(el => !validate(el, true));
    const offersValid = validateOffers();
    const documentsValid=validateRequiredDocuments();
    const mediaValid=validateMedia();
    const invalidFiles=[...form.querySelectorAll(':invalid')].filter(el=>!el.disabled);
    if (invalid.length || !offersValid || !documentsValid || !mediaValid || invalidFiles.length) {
      event.preventDefault();
      const first = invalid[0] || invalidFiles[0];
      if (first) { first.focus(); first.reportValidity(); }
      else form.querySelector('.offer-fields')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });
});
