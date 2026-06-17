/* FluentCart Germanized – Pflicht-Checkboxen in den (Vue-gerenderten) Checkout injizieren
 * + Bestellung blockieren, bis alle Pflicht-Checkboxen angehakt sind.
 */
(function () {
    'use strict';

    var cfg = window.fcgCheckout || null;
    if (!cfg || !cfg.checkboxes || !cfg.checkboxes.length) {
        return;
    }

    var BTN = '#fluent_cart_order_btn';
    var WRAP = '.fct_place_order_btn_wrap';
    var FORM = 'form.fct_checkout, form.fluent-cart-checkout-page-checkout-form';
    var injected = false;

    function buildBlock() {
        var box = document.createElement('div');
        box.className = 'fcg-checkout-legal';
        box.setAttribute('data-fcg-legal', '');
        cfg.checkboxes.forEach(function (cb) {
            var id = 'fcg-cb-' + cb.id;
            var row = document.createElement('label');
            row.className = 'fcg-cb-row';
            row.setAttribute('for', id);
            var input = document.createElement('input');
            input.type = 'checkbox';
            input.id = id;
            input.className = 'fcg-cb';
            input.setAttribute('data-fcg-required', cb.required ? '1' : '0');
            var span = document.createElement('span');
            span.innerHTML = cb.label; // Label darf Links enthalten
            row.appendChild(input);
            row.appendChild(span);
            box.appendChild(row);
        });
        var err = document.createElement('div');
        err.className = 'fcg-cb-error';
        err.setAttribute('aria-live', 'polite');
        err.style.display = 'none';
        err.textContent = cfg.errorText || 'Bitte bestätigen Sie die Pflichtangaben.';
        box.appendChild(err);
        return box;
    }

    function allChecked() {
        var boxes = document.querySelectorAll('.fcg-checkout-legal .fcg-cb[data-fcg-required="1"]');
        for (var i = 0; i < boxes.length; i++) {
            if (!boxes[i].checked) { return false; }
        }
        return true;
    }

    function showError(show) {
        var err = document.querySelector('.fcg-checkout-legal .fcg-cb-error');
        if (err) { err.style.display = show ? 'block' : 'none'; }
        var box = document.querySelector('.fcg-checkout-legal');
        if (box) { box.classList.toggle('fcg-invalid', !!show); }
    }

    function guard(e) {
        if (!injected) { return; }
        if (!allChecked()) {
            e.preventDefault();
            e.stopImmediatePropagation();
            showError(true);
            var box = document.querySelector('.fcg-checkout-legal');
            if (box && box.scrollIntoView) { box.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
            return false;
        }
        showError(false);
    }

    function inject() {
        if (injected) { return; }
        var btn = document.querySelector(BTN);
        if (!btn) { return; }
        var wrap = document.querySelector(WRAP) || btn.parentElement;
        if (!wrap || !wrap.parentElement) { return; }

        var block = buildBlock();
        wrap.parentElement.insertBefore(block, wrap);
        injected = true;

        // Capture-Phase: vor FluentCart-Handlern blocken.
        btn.addEventListener('click', guard, true);
        var form = document.querySelector(FORM);
        if (form) { form.addEventListener('submit', guard, true); }

        // Fehler ausblenden, sobald angehakt
        block.addEventListener('change', function () {
            if (allChecked()) { showError(false); }
        });
    }

    // Vue rendert asynchron – beobachten bis Button da ist.
    var tries = 0;
    var iv = setInterval(function () {
        tries++;
        inject();
        if (injected || tries > 60) { clearInterval(iv); }
    }, 500);

    if (window.MutationObserver) {
        var mo = new MutationObserver(function () { inject(); });
        mo.observe(document.body, { childList: true, subtree: true });
    }
})();
