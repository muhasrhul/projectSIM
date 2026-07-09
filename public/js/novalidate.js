// Matikan native browser validation, biarkan Filament yang handle
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("form").forEach(function (f) {
        f.setAttribute("novalidate", true);
    });
});

new MutationObserver(function () {
    document.querySelectorAll("form:not([novalidate])").forEach(function (f) {
        f.setAttribute("novalidate", true);
    });
}).observe(document.body, { childList: true, subtree: true });
