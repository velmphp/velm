(function () {
    if (window.pvCsrf) return;
    window.pvCsrf = function () {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    };
    window.pvConfirm = function (message) {
        return Promise.resolve(window.confirm(message));
    };
    window.pvAlert = function (message) {
        window.alert(message);
    };
    window.pvToast = function (message) {
        if (window.dispatchEvent) {
            document.dispatchEvent(new CustomEvent('velm:toast', { detail: { message } }));
        }
    };
})();
