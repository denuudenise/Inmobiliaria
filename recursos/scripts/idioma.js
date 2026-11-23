(() => {
    'use strict';

    const catalog = JSON.parse(document.getElementById('ui-catalog').textContent);
    const originals = new WeakMap();
    const validation = new WeakMap();
    const attributes = ['placeholder', 'title', 'alt', 'aria-label'];
    const excluded = 'script, style, code, [translate="no"]';
    let language = document.documentElement.lang;

    function label(source) {
        return Object.hasOwn(catalog, source) ? catalog[source] : source;
    }

    // These labels combine interface terms with names, prices or counts.
    function english(source) {
        if (Object.hasOwn(catalog, source)) return catalog[source];
        const oversizedPhoto = source.match(/^La foto "(.+)" supera el límite de 2 MB\.$/u);
        if (oversizedPhoto) return `Photo "${oversizedPhoto[1]}" exceeds the 2 MB limit.`;
        const unsupportedPhoto = source.match(/^El archivo "(.+)" no es una foto JPG, PNG o WEBP\.$/u);
        if (unsupportedPhoto) return `File "${unsupportedPhoto[1]}" is not a JPG, PNG or WEBP image.`;
        const invalidPhoto = source.match(/^La foto "(.+)" debe ser JPG, PNG o WEBP\.$/u);
        if (invalidPhoto) return `Photo "${invalidPhoto[1]}" must be JPG, PNG or WEBP.`;
        const invalidVideo = source.match(/^El video "(.+)" debe ser MP4 y pesar hasta 20 MB\.$/u);
        if (invalidVideo) return `Video "${invalidVideo[1]}" must be MP4 and no larger than 20 MB.`;
        const uploadFailure = source.match(/^No se pudo cargar la foto "(.+)"\. Revisá el archivo e intentá nuevamente\.$/u);
        if (uploadFailure) return `Could not upload photo "${uploadFailure[1]}". Check the file and try again.`;
        const badge = source.match(/^(●|▱|♧)\s*(.*)$/u);
        if (badge) return badge[1] + ' ' + english(badge[2]);
        if (source.endsWith(' / Nuevo Hogar')) return label(source.split(' / ')[0]) + ' / Nuevo Hogar';
        if (source.includes(' → ') && !source.endsWith(' →')) return source.split(' → ').map(label).join(' → ');
        const keyed = source.match(/^(.+?)( #?)(\d+)$/u);
        if (keyed && Object.hasOwn(catalog, keyed[1])) return label(keyed[1]) + keyed[2] + keyed[3];
        const published = source.match(/^Propiedad publicada desde solicitud #(\d+)$/u);
        if (published) return 'Property published from request #' + published[1];
        const offer = source.match(/^Oferta para propiedad (\d+) por (.+)$/u);
        if (offer) return 'Offer for property ' + offer[1] + ' for ' + offer[2];
        if (source.includes(' | ')) return source.split(' | ').map(label).join(' | ');
        if (source.includes(' · ')) return source.split(' · ').map(english).join(' · ');
        if (source.includes(' • ')) return source.split(' • ').map(label).join(' • ');
        const search = source.match(/^Buscar en (.+)…$/u);
        if (search) return 'Search in ' + label(search[1][0].toUpperCase() + search[1].slice(1)).toLowerCase() + '…';
        if (source.startsWith('Hola, ')) return 'Hello, ' + source.slice(6);
        if (source.startsWith('Ver ')) return 'View ' + (label(source.slice(3).replace(/ ↗$/, '')) + (source.endsWith(' ↗') ? ' ↗' : ''));
        if (source.startsWith('Motivo: ')) return 'Reason: ' + source.slice(8);
        if (source.startsWith('Teléfono: ')) return 'Phone: ' + label(source.slice(10));
        if (source.endsWith(' / mes')) return english(source.slice(0, -6)) + ' / month';
        const amount = source.match(/^(USD|ARS) (\d{1,3}(?:\.\d{3})+|\d+)(?:,(\d{2}))?$/u);
        if (amount) return amount[1] + ' ' + amount[2].replace(/\./g, ',') + (amount[3] ? '.' + amount[3] : '');
        const count = source.match(/^(\d+(?:[.,]\d+)?|—)\s+(.+)$/u);
        if (count) return count[1] + ' ' + label(count[2]);
        const age = source.match(/^Antigüedad:\s*(\d+)\s+años$/u);
        if (age) return 'Property age: ' + age[1] + ' years';
        const tag = source.match(/^(Vista|Ver foto) (\d+)$/u);
        if (tag) return label(tag[1]) + ' ' + tag[2];
        const prefix = source.match(/^(Hay) (\d+) campos para revisar$/u);
        if (prefix) return prefix[2] + ' fields to review';
        if (source.endsWith(':')) return label(source.slice(0, -1)) + ':';
        if (source.endsWith(' *')) return label(source.slice(0, -2)) + ' *';
        return source;
    }

    function render(source) {
        if (language !== 'en') return source;
        const core = source.trim();
        const result = english(core.replace(/\s+/gu, ' '));
        return result === core.replace(/\s+/gu, ' ') ? source : source.replace(core, () => result);
    }

    function update(node, key, current, write) {
        let fields = originals.get(node);
        const previous = fields?.get(key);
        const source = previous && previous.shown === current ? previous.source : current;
        const shown = render(source);
        if (shown === current && !previous) return;
        if (!fields) originals.set(node, fields = new Map());
        fields.set(key, {source, shown});
        if (shown !== current) write(shown);
    }

    function apply(node) {
        if (node.nodeType === Node.TEXT_NODE) {
            if (!node.parentElement || node.parentElement.closest(excluded) || node.parentElement.closest('textarea')) return;
            update(node, 'text', node.data, value => { node.data = value; });
            return;
        }
        if (node.nodeType !== Node.ELEMENT_NODE || node.closest(excluded)) return;
        // Some PHP forms rely on the original option text as their submitted value.
        if (node.tagName === 'OPTION' && !node.hasAttribute('value')) node.value = node.value;
        for (const key of attributes) {
            if (node.hasAttribute(key)) update(node, key, node.getAttribute(key), value => node.setAttribute(key, value));
        }
        for (const child of node.childNodes) apply(child);
    }

    function setValidity(control, message) {
        if (message) validation.set(control, message);
        else validation.delete(control);
        control.setCustomValidity(render(message));
    }

    window.WebIdioma = {setValidity, text: render};

    // Native validation bubbles otherwise follow the browser's language.
    const nativeErrors = new WeakSet();
    document.addEventListener('invalid', event => {
        const field = event.target;
        if (!field.validity || field.validity.customError) return;
        const state = field.validity;
        let message = 'Ingresá un valor válido.';
        if (state.valueMissing) message = field.tagName === 'SELECT' ? 'Elegí una opción.' : 'Este campo es obligatorio.';
        else if (state.typeMismatch || state.patternMismatch) message = 'Revisá el formato de este campo.';
        setValidity(field, message);
        nativeErrors.add(field);
    }, true);
    function clearNativeError(event) {
        if (nativeErrors.delete(event.target)) setValidity(event.target, '');
    }
    document.addEventListener('input', clearNativeError, true);
    document.addEventListener('change', clearNativeError, true);

    function refresh() {
        apply(document.documentElement);
        for (const control of document.querySelectorAll('input, select, textarea')) {
            if (validation.has(control)) setValidity(control, validation.get(control));
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const switcher = document.getElementById('ui-language-toggle');
        const syncSwitch = () => {
            const english = language === 'en';
            switcher?.classList.toggle('is-english', english);
            switcher?.setAttribute('aria-checked', String(english));
        };
        const setLanguage = nextLanguage => {
            language = nextLanguage === 'en' ? 'en' : 'es';
            document.documentElement.lang = language;
            document.cookie = 'inmo_lang=' + language + '; Path=/Inmobiliaria/; Max-Age=31536000; SameSite=Lax';
            syncSwitch();
            refresh();
            document.dispatchEvent(new Event('ui-language-change'));
        };
        syncSwitch();
        switcher?.addEventListener('click', () => setLanguage(language === 'en' ? 'es' : 'en'));
        refresh();
        // Validation hints, status messages and favorite buttons can change after load.
        new MutationObserver(records => {
            for (const record of records) {
                if (record.type === 'childList') record.addedNodes.forEach(apply);
                else apply(record.target);
            }
        }).observe(document.documentElement, {
            subtree: true, childList: true, characterData: true, attributes: true, attributeFilter: attributes
        });
        window.addEventListener('pageshow', () => {
            language = /(?:^|;\s*)inmo_lang=en(?:;|$)/.test(document.cookie) ? 'en' : 'es';
            document.documentElement.lang = language;
            syncSwitch();
            refresh();
        });
    });
})();
