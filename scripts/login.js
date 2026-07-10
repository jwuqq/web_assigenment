document.addEventListener('DOMContentLoaded', function () {

    // === Logo icon: first hover triggers permanent swing ===
    document.querySelectorAll('.logo-icon').forEach(function (el) {
        el.addEventListener('mouseenter', function () {
            this.classList.add('swinging');
        }, { once: true });
    });

    // === Role Selection ===
    var roleSelect = document.getElementById('role-select');
    var customerPanel = document.getElementById('customer-panel');
    var staffPanel = document.getElementById('staff-panel');

    document.querySelectorAll('.role-card').forEach(function (card) {
        card.addEventListener('click', function () {
            var role = this.getAttribute('data-role');
            if (roleSelect) roleSelect.style.display = 'none';
            if (role === 'customer' && customerPanel) {
                customerPanel.classList.add('active');
            } else if (role === 'staff' && staffPanel) {
                staffPanel.classList.add('active');
            }
        });
    });

    // Back to role selection
    document.querySelectorAll('.back-link').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (customerPanel) customerPanel.classList.remove('active');
            if (staffPanel) staffPanel.classList.remove('active');
            if (roleSelect) {
                roleSelect.style.display = 'block';
                roleSelect.style.animation = 'none';
                roleSelect.offsetHeight;
                roleSelect.style.animation = 'fadeInUp 0.6s ease';
            }
        });
    });

    // === Tab Switching ===
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = this.getAttribute('data-tab');
            tabBtns.forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            document.querySelectorAll('.tab-panel').forEach(function (p) {
                p.classList.remove('active');
            });
            var panel = document.getElementById('tab-' + target);
            if (panel) panel.classList.add('active');
            // Clear messages
            document.querySelectorAll('.msg').forEach(function (m) { m.style.display = 'none'; });
        });
    });

    // === Password Toggle (show/hide) ===
    document.querySelectorAll('.pw-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(this.getAttribute('data-target'));
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = '🙈';
            } else {
                input.type = 'password';
                this.textContent = '👁️';
            }
        });
    });

    // === Password Strength Meter ===
    var regPassword = document.getElementById('reg-password');
    var strengthBar = document.getElementById('pw-strength-bar');
    var strengthText = document.getElementById('pw-strength-text');

    if (regPassword) {
        regPassword.addEventListener('input', function () {
            var val = this.value;
            var score = 0;
            if (val.length >= 6) score++;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[^a-zA-Z0-9]/.test(val)) score++;

            var level = '', cls = '';
            if (val.length === 0) {
                level = ''; cls = '';
            } else if (score <= 1) {
                level = '弱'; cls = 'weak';
            } else if (score === 2) {
                level = '中'; cls = 'medium';
            } else {
                level = '强'; cls = 'strong';
            }

            if (strengthBar) {
                strengthBar.className = 'pw-strength ' + cls;
            }
            if (strengthText) {
                strengthText.textContent = level ? '密码强度：' + level : '';
                strengthText.className = 'pw-strength-text ' + cls;
            }
        });
    }

    // === Confirm Password Real-time Match ===
    var regConfirm = document.getElementById('reg-confirm');
    var confirmHint = document.getElementById('confirm-hint');

    if (regConfirm && regPassword) {
        function checkMatch() {
            var pw = regPassword.value;
            var cf = regConfirm.value;
            if (!confirmHint) return;
            if (cf.length === 0) {
                confirmHint.className = 'field-hint';
                confirmHint.textContent = '';
            } else if (pw === cf) {
                confirmHint.className = 'field-hint show ok';
                confirmHint.textContent = '✅ 密码一致';
            } else {
                confirmHint.className = 'field-hint show err';
                confirmHint.textContent = '❌ 密码不一致';
            }
        }
        regPassword.addEventListener('input', checkMatch);
        regConfirm.addEventListener('input', checkMatch);
    }

    // === Forgot Password — Confirm Match ===
    var fgNewpass = document.getElementById('fg-newpass');
    var fgConfirm = document.getElementById('fg-confirm');
    var fgConfirmHint = document.getElementById('fg-confirm-hint');

    if (fgNewpass && fgConfirm) {
        function checkFgMatch() {
            var pw = fgNewpass.value;
            var cf = fgConfirm.value;
            if (!fgConfirmHint) return;
            if (cf.length === 0) {
                fgConfirmHint.className = 'field-hint';
                fgConfirmHint.textContent = '';
            } else if (pw === cf) {
                fgConfirmHint.className = 'field-hint show ok';
                fgConfirmHint.textContent = '✅ 密码一致';
            } else {
                fgConfirmHint.className = 'field-hint show err';
                fgConfirmHint.textContent = '❌ 密码不一致';
            }
        }
        fgNewpass.addEventListener('input', checkFgMatch);
        fgConfirm.addEventListener('input', checkFgMatch);
    }


    // === Form Validation: Register (fallback) ===
    var regForm = document.querySelector('#tab-register');
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            var pw = document.getElementById('reg-password');
            var cf = document.getElementById('reg-confirm');
            var errors = [];
            if (pw.value.length < 6) errors.push('密码至少 6 位');
            if (pw.value !== cf.value) errors.push('两次密码输入不一致');
            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join('\n'));
            }
        });
    }

    // === Form Validation: Forgot Password (fallback) ===
    var fgForm = document.querySelector('#tab-forgot');
    if (fgForm) {
        fgForm.addEventListener('submit', function (e) {
            var pw = document.getElementById('fg-newpass');
            var cf = document.getElementById('fg-confirm');
            if (pw && cf && pw.value !== cf.value) {
                e.preventDefault();
                alert('两次密码输入不一致');
            }
            if (pw && pw.value.length < 6) {
                e.preventDefault();
                alert('密码至少 6 位');
            }
        });
    }


});
