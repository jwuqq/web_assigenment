// ============================
// MilkTea Shop — Scripts
// ============================

document.addEventListener('DOMContentLoaded', function () {

    // === Tab Switching ===
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = this.getAttribute('data-tab');
            // Update buttons
            tabBtns.forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            // Update panels
            document.querySelectorAll('.tab-panel').forEach(function (p) {
                p.classList.remove('active');
            });
            document.getElementById('tab-' + target).classList.add('active');
            // Clear messages
            document.querySelectorAll('.msg').forEach(function (m) { m.style.display = 'none'; });
        });
    });

    // === Form Validation: Register ===
    const regForm = document.querySelector('#tab-register');
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            const pw = document.getElementById('reg-password');
            const cf = document.getElementById('reg-confirm');
            const errors = [];

            if (pw.value.length < 6) {
                errors.push('密码至少 6 位');
            }
            if (pw.value !== cf.value) {
                errors.push('两次密码输入不一致');
            }
            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join('\n'));
            }
        });
    }

    // === Form Validation: Forgot Password ===
    const fgForm = document.querySelector('#tab-forgot');
    if (fgForm) {
        fgForm.addEventListener('submit', function (e) {
            const pw = document.getElementById('fg-newpass');
            const cf = document.getElementById('fg-confirm');
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
