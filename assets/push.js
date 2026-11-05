(function () {
    'use strict';
    document.addEventListener('click', function (event) {
        var button = event.target && typeof event.target.closest === 'function' ? event.target.closest('.attention-push-button') : null;
        if (!button || button.disabled) return;
        var status = button.parentElement.querySelector('.attention-push-status');
        if (!window.isSecureContext || !('Notification' in window) || !('serviceWorker' in navigator)) {
            status.textContent = 'Notifications are not available in this browser.';
            return;
        }
        if (!window.Gravitec || typeof window.Gravitec.push !== 'function') {
            status.textContent = 'Notifications are still loading. Please try again shortly.';
            return;
        }
        button.disabled = true;
        status.textContent = 'Follow your browser’s notification instructions.';
        var timer = window.setTimeout(function () {
            button.disabled = false;
            status.textContent = 'Please check your browser settings or try again.';
        }, 90000);
        window.Gravitec.push(['registerUserForPush', function () {
            window.clearTimeout(timer);
            button.disabled = false;
            status.textContent = Notification.permission === 'granted' ? 'Notifications are allowed in this browser.' : 'You can enable notifications later.';
        }, function () {
            window.clearTimeout(timer);
            button.disabled = false;
            status.textContent = 'Notifications could not be enabled. Please try again later.';
        }]);
    });
}());
