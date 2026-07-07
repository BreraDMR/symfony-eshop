/*
 * Progressive enhancement for the cart page.
 *
 * The cart is a plain server-rendered form: changing a quantity and pressing
 * "Update" POSTs to the server, which recomputes and re-renders. That keeps
 * working with JavaScript disabled.
 *
 * When JS is available we enhance it: the same endpoints answer with JSON
 * (Accept: application/json) instead of a redirect, so we can update the line
 * subtotal, the running total and the header count in place — no reload, and
 * no "Update" button needed. The server stays the single source of truth for
 * prices and stock; we only display what it returns.
 */
(function () {
    'use strict';

    var totalEl = document.querySelector('[data-cart-total]');
    var countEls = document.querySelectorAll('[data-cart-count]');

    function applySummary(data) {
        if (totalEl && data.total) {
            totalEl.textContent = data.total;
        }
        countEls.forEach(function (el) {
            el.textContent = data.itemCount;
        });
    }

    function postForm(form) {
        return fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new FormData(form)
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Cart request failed with ' + response.status);
            }
            return response.json();
        });
    }

    document.querySelectorAll('form.js-cart-update').forEach(function (form) {
        var input = form.querySelector('input[name="quantity"]');
        var button = form.querySelector('.js-cart-update-submit');
        var row = form.closest('tr');
        var subtotalEl = row ? row.querySelector('[data-cart-subtotal]') : null;
        var timer = null;

        if (!input) {
            return;
        }

        // Drive the update from the field itself, so the button is redundant.
        form.addEventListener('submit', function (event) {
            event.preventDefault();
        });

        input.addEventListener('input', function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                postForm(form).then(function (data) {
                    if (data.quantity > 0) {
                        input.value = data.quantity; // snap to the stock-clamped value
                        if (subtotalEl && data.lineSubtotal) {
                            subtotalEl.textContent = data.lineSubtotal;
                        }
                    } else if (row) {
                        row.remove();
                    }
                    applySummary(data);
                    if (data.empty) {
                        window.location.reload();
                    }
                }).catch(function () {
                    form.submit(); // fall back to the plain server round-trip
                });
            }, 300);
        });

        // Only hide the button once the field is wired, so a failed script load
        // still leaves a usable form.
        if (button) {
            button.hidden = true;
        }
    });

    document.querySelectorAll('form.js-cart-remove').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            postForm(form).then(function (data) {
                var row = form.closest('tr');
                if (row) {
                    row.remove();
                }
                applySummary(data);
                if (data.empty) {
                    window.location.reload();
                }
            }).catch(function () {
                form.submit();
            });
        });
    });
})();
