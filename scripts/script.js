document.addEventListener('DOMContentLoaded', function () {

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

    // === Customer menu search ===
    var drinkSearch = document.getElementById('drink-search');
    var drinkCards = document.querySelectorAll('.drink-card[data-name]');
    var drinkEmpty = document.getElementById('drink-empty');
    if (drinkSearch && drinkCards.length) {
        drinkSearch.addEventListener('input', function () {
            var keyword = drinkSearch.value.trim().toLowerCase();
            var visibleCount = 0;
            drinkCards.forEach(function (card) {
                var name = (card.getAttribute('data-name') || '').toLowerCase();
                var matched = name.indexOf(keyword) !== -1;
                card.classList.toggle('is-hidden', !matched);
                if (matched) visibleCount++;
            });
            if (drinkEmpty) {
                drinkEmpty.hidden = visibleCount !== 0;
            }
        });
    }

    // === Shopping cart ===
    var orderModal = document.getElementById('order-modal');
    var orderModalText = document.getElementById('order-modal-text');
    var cartList = document.getElementById('cart-list');
    var cartTotal = document.getElementById('cart-total');
    var cartPayload = document.getElementById('cart-payload');
    var cartForm = document.getElementById('cart-checkout-form');
    var checkoutBtn = document.querySelector('.btn-checkout');
    var cartClear = document.getElementById('cart-clear');
    var cart = {};

    function closeOrderModal() {
        if (!orderModal) return;
        orderModal.hidden = true;
    }

    function cartItems() {
        return Object.keys(cart).map(function (id) {
            return cart[id];
        });
    }

    function renderCart() {
        var items = cartItems();
        var total = 0;

        if (!cartList || !cartTotal || !cartPayload) return;

        if (items.length === 0) {
            cartList.innerHTML = '<p class="empty-state">购物车为空，先去选择饮品吧。</p>';
            cartTotal.textContent = '¥0.00';
            cartPayload.value = '[]';
            if (checkoutBtn) checkoutBtn.disabled = true;
            return;
        }

        cartList.innerHTML = '';
        items.forEach(function (item) {
            total += item.price * item.qty;

            var row = document.createElement('div');
            row.className = 'cart-row';
            row.innerHTML =
                '<div>' +
                    '<div class="cart-row-title"></div>' +
                    '<div class="cart-row-meta"></div>' +
                '</div>' +
                '<div class="cart-row-actions">' +
                    '<div class="qty-stepper">' +
                        '<button type="button" class="qty-btn cart-minus" aria-label="减少数量">−</button>' +
                        '<input type="number" class="qty-input cart-qty" min="1" max="10">' +
                        '<button type="button" class="qty-btn cart-plus" aria-label="增加数量">+</button>' +
                    '</div>' +
                    '<button type="button" class="cart-remove">移除</button>' +
                '</div>';

            row.querySelector('.cart-row-title').textContent = item.name;
            row.querySelector('.cart-row-meta').textContent = '¥' + item.price.toFixed(2) + ' × ' + item.qty + ' = ¥' + (item.price * item.qty).toFixed(2);
            row.querySelector('.cart-qty').value = item.qty;

            row.querySelector('.cart-minus').addEventListener('click', function () {
                item.qty = Math.max(1, item.qty - 1);
                renderCart();
            });
            row.querySelector('.cart-plus').addEventListener('click', function () {
                item.qty = Math.min(10, item.qty + 1);
                renderCart();
            });
            row.querySelector('.cart-qty').addEventListener('input', function () {
                item.qty = Math.max(1, Math.min(10, parseInt(this.value || '1', 10)));
                renderCart();
            });
            row.querySelector('.cart-remove').addEventListener('click', function () {
                delete cart[item.id];
                renderCart();
            });

            cartList.appendChild(row);
        });

        cartTotal.textContent = '¥' + total.toFixed(2);
        cartPayload.value = JSON.stringify(items.map(function (item) {
            return { id: item.id, qty: item.qty };
        }));
        if (checkoutBtn) checkoutBtn.disabled = false;
    }

    document.querySelectorAll('.qty-stepper').forEach(function (stepper) {
        var input = stepper.querySelector('.qty-input');
        var minus = stepper.querySelector('.qty-minus');
        var plus = stepper.querySelector('.qty-plus');

        if (!input) return;

        if (minus) {
            minus.addEventListener('click', function () {
                input.value = Math.max(1, parseInt(input.value || '1', 10) - 1);
            });
        }
        if (plus) {
            plus.addEventListener('click', function () {
                input.value = Math.min(10, parseInt(input.value || '1', 10) + 1);
            });
        }
        input.addEventListener('input', function () {
            input.value = Math.max(1, Math.min(10, parseInt(input.value || '1', 10)));
        });
    });

    document.querySelectorAll('.btn-add-cart').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var card = btn.closest('.drink-card');
            var input = card ? card.querySelector('.qty-input') : null;
            var id = btn.getAttribute('data-id');
            var qty = input ? Math.max(1, Math.min(10, parseInt(input.value || '1', 10))) : 1;

            if (!id) return;
            if (!cart[id]) {
                cart[id] = {
                    id: parseInt(id, 10),
                    name: btn.getAttribute('data-name') || '',
                    price: parseFloat(btn.getAttribute('data-price') || '0'),
                    qty: 0
                };
            }

            cart[id].qty = Math.min(10, cart[id].qty + qty);
            renderCart();
            btn.textContent = '已加入';
            setTimeout(function () {
                btn.textContent = '加入购物车';
            }, 900);

            var cartSection = document.getElementById('cart');
            if (cartSection) {
                cartSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    });

    if (cartClear) {
        cartClear.addEventListener('click', function () {
            cart = {};
            renderCart();
        });
    }

    if (orderModal) {
        orderModal.querySelectorAll('.modal-close, .modal-cancel').forEach(function (btn) {
            btn.addEventListener('click', closeOrderModal);
        });
        orderModal.addEventListener('click', function (e) {
            if (e.target === orderModal) {
                closeOrderModal();
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !orderModal.hidden) {
                closeOrderModal();
            }
        });
        var confirmBtn = orderModal.querySelector('.modal-confirm');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                if (cartForm) {
                    cartForm.dataset.confirmed = '1';
                    cartForm.submit();
                }
            });
        }
    }

    if (cartForm) {
        cartForm.addEventListener('submit', function (e) {
            var items = cartItems();
            if (items.length === 0) {
                e.preventDefault();
                return;
            }
            if (cartForm.dataset.confirmed === '1') {
                return;
            }
            if (!orderModal || !orderModalText) {
                return;
            }
            e.preventDefault();
            var total = items.reduce(function (sum, item) {
                return sum + item.price * item.qty;
            }, 0);
            orderModalText.textContent = '确认提交 ' + items.length + ' 款饮品，合计 ¥' + total.toFixed(2) + '？';
            orderModal.hidden = false;
        });
    }

    renderCart();

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

    // === Staff: Order Making Progress Bar ===
    window.startMaking = function(btn, oid) {
        var cell = btn.closest('.order-action-cell');
        var form = btn.closest('.make-form');
        var progressWrap = cell.querySelector('.progress-wrap');
        var progressFill = progressWrap.querySelector('.progress-fill');

        // Hide button, show progress
        btn.style.display = 'none';
        progressWrap.style.display = 'block';

        // Start 5s animation
        progressFill.classList.add('active');

        // Auto submit after 5s
        setTimeout(function() {
            form.submit();
        }, 5000);
    };

});
