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

  const errors = new Map();
  let submitted = form.dataset.serverErrors === 'true';
  const summary = document.createElement('div');
  summary.className = 'validation-summary';
  summary.setAttribute('role', 'alert');
  summary.hidden = true;
  form.prepend(summary);

  function setError(el, message) {
    if (!el) return;
    const field = el.closest('.field') || el.parentElement;
    let feedback = document.getElementById(`ayuda-${el.name}`);
    if (!feedback) feedback = field.querySelector(`[data-validation-for="${el.id}"]`);
    if (!el.id) el.id = `validation-field-${[...form.elements].indexOf(el)}`;
    if (!feedback) {
      feedback = document.createElement('small');
      feedback.className = 'field-feedback field-error';
      feedback.dataset.validationFor = el.id;
      feedback.id = `error-${el.id}`;
      field.append(feedback);
    }
    if (!feedback.id) feedback.id = `error-${el.id}`;
    const described = new Set((el.getAttribute('aria-describedby') || '').split(' ').filter(Boolean));
    described.add(feedback.id);
    el.setAttribute('aria-describedby', [...described].join(' '));
    el.setAttribute('aria-invalid', message ? 'true' : 'false');
    // The summary and inline messages handle feedback without a second native scroll.
    el.setCustomValidity('');
    if (message) errors.set(el, message); else errors.delete(el);
    feedback.textContent = message;
    feedback.hidden = !message;
    feedback.classList.toggle('field-error', Boolean(message));
    field.classList.toggle('has-error', Boolean(field.querySelector('[aria-invalid="true"]')));
    field.classList.remove('is-valid');
  }

  function validate(el) {
    const value = el.type === 'checkbox' ? '' : el.value.trim();
    const empty = el.required && (el.type === 'checkbox' ? !el.checked : !value);
    const valid = !empty && checks[el.dataset.validate](value, el);
    setError(el, valid ? '' : messages[el.dataset.validate]);
    return valid;
  }

  function validateOffers() {
    const rows = [...form.querySelectorAll('[data-offer-row]')];
    const selected = rows.filter(row => row.querySelector('input[type="checkbox"]').checked);
    rows.forEach((row, index) => {
      const box = row.querySelector('input[type="checkbox"]');
      setError(box, !selected.length && index === 0 ? 'Seleccioná al menos una operación.' : '');
      const numbers = [...row.querySelectorAll('.offer-details input[type="number"]')];
      numbers.forEach(el => setError(el, ''));
      if (!box.checked) return;
      const price = numbers[0];
      const minimum = row.querySelector('[data-minimum-price]');
      if (!price.value || Number(price.value) <= 0 || Number(price.value) > 9999999999.99 || !price.validity.valid)
        setError(price, 'Ingresá un precio mayor a cero para cada operación seleccionada.');
      if (minimum && (!minimum.value || Number(minimum.value) <= 0 || Number(minimum.value) > Number(price.value) || !minimum.validity.valid))
        setError(minimum, 'El monto mínimo debe ser mayor a cero y no puede superar el precio de venta.');
    });
    const group = form.querySelector('.offer-fields');
    group?.classList.toggle('has-error', Boolean(group.querySelector('[aria-invalid="true"]')));
    const oldFeedback = form.querySelector('#ayuda-ofertas');
    if (oldFeedback) oldFeedback.textContent = '';
  }

  function validateDocuments() {
    const notes = form.querySelector('[data-document-notes]');
    const missing = [...form.querySelectorAll('[data-document-missing]')];
    if (notes) notes.required = missing.some(box => box.checked);
    for (const [name, boxName, message] of [
      ['planos', 'faltan_planos', 'Adjuntá los planos o marcá que todavía no los tenés.'],
      ['escrituras', 'falta_escritura', 'Adjuntá la escritura o marcá que todavía no la tenés.']
    ]) {
      const input = form.querySelector(`[name="${name}[]"]`);
      const box = form.querySelector(`[name="${boxName}"]`);
      const absent = !input?.files.length && !box?.checked;
      setError(input, absent ? message : '');
      box?.closest('label')?.classList.toggle('document-choice-error', absent);
    }
    const files = [...form.querySelectorAll('[data-private-files]')];
    const total = files.reduce((sum, input) => sum + input.files.length, 0);
    files.forEach(input => {
      if (!input.files.length) { if (input.name === 'comprobantes[]') setError(input, ''); return; }
      const badSize = [...input.files].some(file => file.size <= 0 || file.size > 2 * 1024 * 1024);
      const badType = [...input.files].some(file => !input.accept.split(',').includes(file.type));
      setError(input, total > 8 ? 'Adjuntá hasta 8 documentos privados por envío.' : badSize ? 'Cada documento debe cargarse correctamente y pesar hasta 2 MB.' : badType ? 'Usá PDF, JPG, PNG o WEBP para los documentos.' : '');
    });
    if (notes) validate(notes);
  }

  function validateMedia() {
    const photos = form.querySelector('[name="fotos[]"]');
    const video = form.querySelector('[name="video"]');
    if (photos) {
      const files = [...photos.files];
      const large = files.find(file => file.size <= 0 || file.size > 2 * 1024 * 1024);
      const unsupported = files.find(file => !photos.accept.split(',').includes(file.type));
      setError(photos, files.length > 8 ? 'Podés adjuntar hasta 8 fotos.' : large ? `La foto "${large.name}" supera el límite de 2 MB.` : unsupported ? `El archivo "${unsupported.name}" no es una foto JPG, PNG o WEBP.` : '');
    }
    const file = video?.files[0];
    setError(video, file && (file.type !== 'video/mp4' || file.size <= 0 || file.size > 20 * 1024 * 1024) ? `El video "${file.name}" debe ser MP4 y pesar hasta 20 MB.` : '');
  }

  function validateTax() {
    const fields = ['concepto', 'base', 'porcentaje', 'periodo'].map(key => form.querySelector(`[name="impuesto_${key}"]`));
    if (fields.some(el => !el)) return;
    fields.forEach(el => setError(el, ''));
    if (!fields.some(el => el.value.trim())) return;
    const [concept, base, rate, period] = fields;
    if (!concept.value.trim()) setError(concept, 'Completá el concepto del impuesto.');
    if (!/^\d{1,9}(?:\.\d{1,2})?$/.test(base.value) || Number(base.value) <= 0) setError(base, 'La base del impuesto debe ser mayor a cero.');
    if (!/^\d{1,3}(?:\.\d{1,2})?$/.test(rate.value) || Number(rate.value) > 100) setError(rate, 'Ingresá un porcentaje entre 0 y 100.');
    if (!/^(?:19|20|21)\d{2}-(?:0[1-9]|1[0-2])$/.test(period.value)) setError(period, 'Completá el período mensual del impuesto.');
  }

  function orderedErrors() {
    return [...form.querySelectorAll('input,select,textarea')].filter(el => errors.has(el) && !el.disabled);
  }
  function focusProblem(el) {
    if (!el) return;
    for (let parent = el.parentElement; parent; parent = parent.parentElement)
      if (parent.tagName === 'DETAILS') parent.open = true;
    el.focus({preventScroll: true});
    el.scrollIntoView({behavior: 'instant', block: 'center'});
  }
  function showSummary() {
    summary.replaceChildren();
    const pending = orderedErrors();
    summary.hidden = !pending.length;
    if (!pending.length) return;
    const heading = document.createElement('strong');
    heading.textContent = 'No se envió la solicitud. Revisá estos campos:';
    summary.append(heading);
    const list = document.createElement('ul');
    pending.forEach(el => {
      const item = document.createElement('li');
      const link = document.createElement('a');
      link.href = `#${el.id}`;
      link.textContent = errors.get(el);
      link.addEventListener('click', event => { event.preventDefault(); focusProblem(el); });
      item.append(link); list.append(item);
    });
    summary.append(list);
  }
  function validateAll() {
    validateDocuments();
    controls.forEach(validate);
    validateOffers(); validateMedia(); validateTax();
    for (const el of form.querySelectorAll('input,select,textarea')) {
      if (!el.disabled && !el.validity.valid && !errors.has(el))
        setError(el, el.validationMessage || 'Completá este campo.');
    }
    showSummary();
  }
  form.addEventListener('submit', event => {
    submitted = true;
    validateAll();
    const first = orderedErrors()[0];
    if (first) { event.preventDefault(); focusProblem(first); }
  });
  for (const type of ['input', 'change']) form.addEventListener(type, event => {
    if (submitted) validateAll();
    else if (event.target.matches('[data-validate]') && event.target.dataset.touched === 'true') validate(event.target);
  });
  controls.forEach(el => el.addEventListener('blur', () => {
    el.dataset.touched = 'true';
    validate(el);
  }));
  if (submitted) {
    for (const field of form.querySelectorAll('.field.has-error')) {
      const el = field.querySelector('[aria-invalid="true"]') || field.querySelector('textarea,input:not([type="hidden"]),select');
      const feedback = field.querySelector('.field-feedback');
      if (el && feedback?.textContent.trim()) setError(el, feedback.textContent.trim());
    }
    const offerMessage = form.querySelector('#ayuda-ofertas')?.textContent.trim();
    if (offerMessage) {
      const selected = [...form.querySelectorAll('[data-offer-row]')].find(row => row.querySelector('input[type="checkbox"]').checked);
      setError(selected?.querySelector('input[type="number"]') || form.querySelector('[data-offer-row] input[type="checkbox"]'), offerMessage);
    }
    const media = form.querySelector('[data-server-media-error]');
    if (media?.dataset.serverMediaError) setError(form.querySelector(/^el video/i.test(media.dataset.serverMediaError) ? '[name="video"]' : '[name="fotos[]"]'), media.dataset.serverMediaError);
    showSummary();
    focusProblem(orderedErrors()[0]);
  }
});
