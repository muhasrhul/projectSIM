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

// Blok angka dan simbol pada field Nama Lengkap
document.addEventListener("DOMContentLoaded", function () {
    function applyNameFilter() {
        document.querySelectorAll("input[id*='name']").forEach(function (input) {
            if (input.dataset.nameFilter) return;
            input.dataset.nameFilter = true;
            input.addEventListener("keypress", function (e) {
                var char = String.fromCharCode(e.which);
                if (!/^[a-zA-Z\s'.\-]$/.test(char)) {
                    e.preventDefault();
                }
            });
            input.addEventListener("input", function () {
                var cleaned = this.value.replace(/[^a-zA-Z\s'.\-]/g, "");
                if (this.value !== cleaned) this.value = cleaned;
            });
        });
    }

    applyNameFilter();
    new MutationObserver(applyNameFilter).observe(document.body, { childList: true, subtree: true });
});
