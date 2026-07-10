document.addEventListener('DOMContentLoaded', function () {

    // === Form Submit — Loading State ===
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            if (form.classList.contains('js-order-form') && form.dataset.confirmed !== '1') {
                return;
            }
            if (form.id === 'cart-checkout-form' && form.dataset.confirmed !== '1') {
                return;
            }
            var btn = this.querySelector('button[type="submit"]');
            if (btn) {
                var originalText = btn.textContent;
                btn.style.pointerEvents = 'none';
                btn.style.opacity = '0.7';
                btn.innerHTML = '<span class="spinner"></span>处理中…';
                // Re-enable after 5s in case of network issues (page will reload on success)
                setTimeout(function () {
                    btn.style.pointerEvents = 'auto';
                    btn.style.opacity = '1';
                    btn.textContent = originalText;
                }, 5000);
            }
        });
    });

    // === Auto-fade messages after 4 seconds ===
    var msgs = document.querySelectorAll('.msg');
    msgs.forEach(function (msg) {
        setTimeout(function () {
            msg.classList.add('fade-out');
            setTimeout(function () { msg.style.display = 'none'; }, 500);
        }, 4000);
    });

    // === Mobile navigation ===
    var navToggle = document.querySelector('.nav-toggle');
    var navLinks = document.querySelector('.nav-links');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function () {
            var isOpen = navLinks.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        navLinks.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                navLinks.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    // === Smooth in-page navigation ===
    document.querySelectorAll('.nav-links a[href^="#"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var target = document.querySelector(link.getAttribute('href'));
            if (!target) return;

            e.preventDefault();
            var header = document.querySelector('header');
            var offset = header ? header.offsetHeight + 16 : 16;
            var top = target.getBoundingClientRect().top + window.pageYOffset - offset;

            window.scrollTo({ top: top, behavior: 'smooth' });
            target.classList.remove('section-highlight');
            window.setTimeout(function () {
                target.classList.add('section-highlight');
            }, 350);
            window.history.replaceState(null, '', link.getAttribute('href'));
        });
    });


});
