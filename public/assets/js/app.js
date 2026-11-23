// KD Limited — small progressive enhancements. No inline scripts (CSP: script-src 'self').
(function () {
    'use strict';

    // Ask before submitting destructive forms: <form data-confirm="Are you sure?">
    document.addEventListener('submit', function (event) {
        var form = event.target;
        var message = form.getAttribute && form.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });

    // Product gallery: clicking a thumbnail shows it as the main image.
    var main = document.getElementById('mainImage');
    if (main) {
        document.querySelectorAll('[data-main-image]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                main.src = btn.getAttribute('data-main-image');
                document.querySelectorAll('[data-main-image]').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
            });
        });
    }
})();
