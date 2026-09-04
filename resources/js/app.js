import './bootstrap';

import Alpine from 'alpinejs';
import { Chart } from 'chart.js/auto';
import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorkerPath from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

// `?url` da una ruta raíz-relativa ("/node_modules/..."). En dev, Vite sirve los assets
// en un origen distinto al de Laravel (puertos separados), así que resolverla contra
// `window.location` (lo que hace pdf.js internamente) apunta al servidor equivocado y el
// worker nunca carga. Resolverla contra `import.meta.url` (el origen real de este módulo,
// sea Vite en dev o el asset ya compilado en producción) la deja siempre correcta.
pdfjsLib.GlobalWorkerOptions.workerSrc = new URL(pdfjsWorkerPath, import.meta.url).href;

window.Alpine = Alpine;
window.Chart = Chart;
window.pdfjsLib = pdfjsLib;

/**
 * x-money="expresion" — input de dinero con separador de miles MIENTRAS SE
 * ESCRIBE (formato es-CO), no solo al perder el foco. `expresion` es
 * cualquier ruta Alpine asignable (`valor`, `item.unit_price`,
 * `conceptos.form.commissions`, `line.values[p]`...) — el input visible
 * pasa a texto y muestra el valor formateado; si tenía `name`, ese atributo
 * se traslada a un input oculto hermano cuyo valor se mantiene sincronizado
 * reactivamente con la expresión (así el formulario sigue enviando el
 * número limpio, nunca el texto con puntos). El cursor se conserva a la
 * misma distancia del final del texto al escribir, para no saltar al
 * insertar/borrar un separador de miles en medio del número.
 */
Alpine.directive('money', (el, { expression }, { evaluate, effect, cleanup }) => {
    if (el.tagName !== 'INPUT' || !expression) return;

    const originalName = el.getAttribute('name');
    el.type = 'text';
    el.setAttribute('inputmode', 'decimal');
    el.classList.add('tabular-nums');

    let hidden = null;
    if (originalName) {
        el.removeAttribute('name');
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = originalName;
        el.insertAdjacentElement('afterend', hidden);
        cleanup(() => hidden.remove());
    }

    const format = (num) => {
        if (num === null || num === undefined || num === '') return '';
        const n = Number(num);
        return isNaN(n) ? '' : n.toLocaleString('es-CO', { maximumFractionDigits: 2 });
    };
    const parse = (str) => {
        if (str === null || str === undefined) return null;
        const cleaned = String(str).trim().replace(/\./g, '').replace(',', '.').replace(/[^0-9.\-]/g, '');
        if (cleaned === '' || cleaned === '-') return null;
        const n = parseFloat(cleaned);
        return isNaN(n) ? null : n;
    };
    const setExpr = (val) => evaluate(`${expression} = ${val === null ? 0 : val}`);

    effect(() => {
        const current = evaluate(expression);
        if (hidden) hidden.value = current ?? '';
        if (document.activeElement !== el) el.value = format(current);
    });

    el.addEventListener('input', () => {
        const distanceFromEnd = el.value.length - el.selectionStart;
        const num = parse(el.value);
        setExpr(num);
        const formatted = format(num);
        el.value = formatted;
        const pos = Math.max(0, formatted.length - distanceFromEnd);
        el.setSelectionRange(pos, pos);
    });

    el.addEventListener('blur', () => {
        el.value = format(evaluate(expression));
    });

    // Selecciona todo el contenido al enfocar — sin esto, hacer clic sobre
    // un valor ya digitado (o el "0" por defecto) e insertar dígitos los
    // intercala con el número existente en vez de reemplazarlo.
    el.addEventListener('focus', () => el.select());
});

Alpine.start();
