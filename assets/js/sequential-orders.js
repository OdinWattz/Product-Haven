/**
 * Product Haven — Sequential Orders tab JS
 *
 * Live preview + reset bevestiging voor de Bestelnummers tab.
 */
document.addEventListener('DOMContentLoaded', function () {

    // ── Live preview ─────────────────────────────────────────────
    var prefix  = document.getElementById('op-so-prefix');
    var suffix  = document.getElementById('op-so-suffix');
    var start   = document.getElementById('op-so-start');
    var padding = document.getElementById('op-so-padding');
    var preview = document.getElementById('op-so-preview');

    function updatePreview() {
        if (!preview) return;
        var p   = prefix  ? prefix.value  : '';
        var s   = suffix  ? suffix.value  : '';
        var num = start   ? (parseInt(start.value,   10) || 1) : 1;
        var pad = padding ? (parseInt(padding.value, 10) || 1) : 1;
        preview.textContent = p + String(num).padStart(pad, '0') + s;
    }

    [prefix, suffix, start, padding].forEach(function (el) {
        if (el) el.addEventListener('input', updatePreview);
    });

    // ── Reset bevestiging ─────────────────────────────────────────
    var resetForm = document.getElementById('op-so-reset-form');
    if (resetForm) {
        resetForm.addEventListener('submit', function (e) {
            if (!confirm('Weet je het zeker? De teller wordt gereset naar het startnummer.')) {
                e.preventDefault();
            }
        });
    }

});
