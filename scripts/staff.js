document.addEventListener('DOMContentLoaded', function () {

    // 订单制作进度条
    window.startMaking = function(btn, oid) {
        var cell = btn.closest('.order-action-cell');
        var progressWrap = cell.querySelector('.progress-wrap');
        var progressFill = progressWrap.querySelector('.progress-fill');
        var row = cell.closest('tr');

        if (!cell || !progressWrap || !progressFill || !row) return;

        btn.disabled = true;
        btn.classList.add('is-hidden');
        progressWrap.classList.remove('progress-hidden');

        progressFill.classList.add('active');

        setTimeout(function() {
            var fd = new FormData();
            fd.append('order_id', oid);
            fd.append('complete', '1');
            fd.append('_ajax', '1');

            fetch(window.location.href, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            }).finally(function () {
                row.classList.add('is-fading-out');
                setTimeout(function() {
                    row.classList.add('is-hidden');
                    var remaining = document.querySelectorAll('#orders tr:not(.is-hidden) .btn-make:not(.is-hidden)');
                    if (remaining.length === 0) {
                        location.reload();
                    }
                }, 500);
            });
        }, 5000);
    };

    // 调价按钮AJAX
    document.querySelectorAll('.ajax-price-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var action = this.dataset.action;
            var priceEl = document.getElementById('price-' + id);
            var current = parseFloat(priceEl.textContent.replace('¥', ''));
            var delta = action === 'inc' ? 1 : -1;
            var updated = Math.max(0, current + delta);
            priceEl.textContent = '¥' + updated.toFixed(2);

            var fd = new FormData();
            fd.append('drink_id', id);
            fd.append('update_price', '1');
            fd.append('price_action', action);
            navigator.sendBeacon(window.location.href, fd);
        });
    });

});
