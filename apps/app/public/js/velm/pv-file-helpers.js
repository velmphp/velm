(function () {
    if (window.pvCsrf) return;
    window.pvCsrf = function () {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    };
    window.pvToast = function (message) {
        if (window.dispatchEvent) {
            document.dispatchEvent(new CustomEvent('velm:toast', { detail: { message } }));
        }
    };
})();
