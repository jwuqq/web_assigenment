document.addEventListener('DOMContentLoaded', function () {

    // 订单制作进度条
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

        // AJAX complete after 5s — no refresh
        setTimeout(function() {
            var fd = new FormData();
            fd.append('order_id', oid);
            fd.append('complete', '1');
            navigator.sendBeacon(window.location.href, fd);
            // Fade out the row
            var row = cell.closest('tr');
            row.style.transition = 'opacity 0.5s';
            row.style.opacity = '0';
            setTimeout(function() { row.style.display = 'none'; }, 500);

            // If no more visible orders, refresh
            var visibleRows = document.querySelectorAll('.order-action-cell:not([style*="display: none"])');
            // Check after row hidden
            setTimeout(function() {
                var remaining = document.querySelectorAll('tr:not([style*="display: none"]) .btn-make:not([style*="display: none"])');
                if (remaining.length === 0) {
                    location.reload();
                }
            }, 600);
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

});
