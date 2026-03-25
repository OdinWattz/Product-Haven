/**
 * Product Haven — Sequential Orders tab JS
 *
 * Live preview + reset confirmation for the Order Numbers tab.
 */
document.addEventListener('DOMContentLoaded', function () {

    // ── Live preview ─────────────────────────────────────────────
    var prefix  = document.getElementById('ph-so-prefix');
    var suffix  = document.getElementById('ph-so-suffix');
    var start   = document.getElementById('ph-so-start');
    var padding = document.getElementById('ph-so-padding');
    var preview = document.getElementById('ph-so-preview');

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

    // ── Reset confirmation ─────────────────────────────────────────
    var resetForm = document.getElementById('ph-so-reset-form');
    if (resetForm) {
        resetForm.addEventListener('submit', function (e) {
            if (!confirm('Are you sure? The counter will be reset to the start number.')) {
                e.preventDefault();
            }
        });
    }

});
