(function () {
    "use strict";

    /* ---- Konfirmasi: <form data-konfirmasi="..."> atau <button data-konfirmasi="..."> ---- */
    document.addEventListener("submit", function (e) {
        var pesan = e.target.getAttribute("data-konfirmasi") ||
            (e.submitter && e.submitter.getAttribute("data-konfirmasi"));
        if (pesan && !window.confirm(pesan)) {
            e.preventDefault();
        }
    }, true);

    /* ---- Toast kecil ---- */
    var toastTimer;
    function toast(teks, error) {
        var el = document.querySelector(".toast");
        if (!el) {
            el = document.createElement("div");
            el.className = "toast";
            el.setAttribute("role", "status");
            document.body.appendChild(el);
        }
        el.textContent = teks;
        el.classList.toggle("error", !!error);
        el.style.display = "block";
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { el.style.display = "none"; }, 2200);
    }

    /* ---- Tambah ke keranjang tanpa reload (jatuh ke submit biasa jika JS mati/gagal) ---- */
    document.addEventListener("submit", function (e) {
        var form = e.target;
        if (e.defaultPrevented || !form.classList.contains("form-keranjang-ajax")) return;
        if (!form.hasAttribute("data-ajax")) return; // hanya form di halaman menu/kasir yang ber-atribut ini
        e.preventDefault();

        var data = new FormData(form);
        fetch(form.action, {
            method: "POST",
            body: data,
            headers: { "X-Requested-With": "fetch", "Accept": "application/json" },
            credentials: "same-origin"
        })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (typeof j.jumlah === "number") {
                    document.querySelectorAll("[data-cart-count]").forEach(function (n) { n.textContent = j.jumlah; });
                }
                if (j.ok && j.muat_ulang) { window.location.reload(); return; }
                toast(j.pesan || (j.ok ? "Ditambahkan" : "Gagal"), !j.ok);
            })
            .catch(function () { form.removeAttribute("data-ajax"); form.submit(); });
    });

    /* ---- Selengkapnya / Tutup pada grid lapak ---- */
    document.querySelectorAll("[data-lebih-toggle]").forEach(function (btn) {
        var grid = document.querySelector(btn.getAttribute("data-lebih-toggle"));
        if (!grid) return;
        btn.addEventListener("click", function () {
            var buka = grid.classList.toggle("tampil-semua");
            btn.textContent = buka ? "Tutup" : "Selengkapnya...";
        });
    });

    /* ---- Stepper stok: <div class="stepper"><button data-step="-1">…<input type=number>…<button data-step="1"> ---- */
    document.querySelectorAll(".stepper").forEach(function (box) {
        var input = box.querySelector("input");
        box.querySelectorAll("[data-step]").forEach(function (b) {
            b.addEventListener("click", function () {
                var v = parseInt(input.value, 10) || 0;
                v += parseInt(b.getAttribute("data-step"), 10);
                input.value = Math.max(0, Math.min(100000, v));
            });
        });
    });

    /* ---- Hitung mundur pembayaran: <span data-sisa="detik"> ---- */
    var hm = document.querySelector("[data-sisa]");
    if (hm) {
        var selesaiPada = Date.now() + parseInt(hm.getAttribute("data-sisa"), 10) * 1000;
        var tick = function () {
            var sisa = Math.max(0, Math.round((selesaiPada - Date.now()) / 1000));
            var m = String(Math.floor(sisa / 60)).padStart(2, "0");
            var d = String(sisa % 60).padStart(2, "0");
            hm.textContent = m + " Menit " + d + " Detik";
            if (sisa <= 0) { window.location.reload(); return; }
            setTimeout(tick, 500);
        };
        tick();
    }

    /* ---- Muat ulang otomatis: <body data-refresh="detik"> atau elemen [data-refresh] ---- */
    var rf = document.querySelector("[data-refresh]");
    if (rf) {
        setTimeout(function () { window.location.reload(); }, parseInt(rf.getAttribute("data-refresh"), 10) * 1000);
    }
})();
