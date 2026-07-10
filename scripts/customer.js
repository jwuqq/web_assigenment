document.addEventListener('DOMContentLoaded', function () {

    // 饮品搜索过滤
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

    // 购物车逻辑
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


});
