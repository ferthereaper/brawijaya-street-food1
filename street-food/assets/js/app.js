/* Brawijaya Street Food - JavaScript biasa (tanpa library) */
(function () {
    "use strict";

    var rp = function (n) { return "Rp" + Number(n).toLocaleString("id-ID"); };

    function load(key) {
        try { return JSON.parse(sessionStorage.getItem(key)) || []; } catch (e) { return []; }
    }
    function save(key, list) { sessionStorage.setItem(key, JSON.stringify(list)); }
    function sum(list, fn) { return list.reduce(function (a, i) { return a + fn(i); }, 0); }

    /* Jumlah item di ikon/nav keranjang */
    function refreshCounts() {
        document.querySelectorAll("[data-cart-count]").forEach(function (el) {
            el.textContent = sum(load(el.dataset.cartCount), function (i) { return i.qty; });
        });
    }

    /* Tampilkan isi keranjang (checkout & kasir) */
    function renderCart(box) {
        var key = box.dataset.cart;
        var list = load(key);
        var body = box.querySelector("[data-cart-body]");
        var empty = box.querySelector("[data-cart-empty]");
        var table = box.querySelector("[data-cart-table]");

        body.innerHTML = "";
        list.forEach(function (item, i) {
            var tr = document.createElement("tr");
            tr.innerHTML =
                '<td>' + item.nama + '<span class="cart-price">' + rp(item.harga) + "</span></td>" +
                '<td><div class="qty">' +
                '<button type="button" data-qty="-1" data-i="' + i + '" aria-label="Kurangi">−</button>' +
                "<span>" + item.qty + "</span>" +
                '<button type="button" data-qty="1" data-i="' + i + '" aria-label="Tambah">+</button>' +
                "</div></td>" +
                "<td>" + rp(item.harga * item.qty) + "</td>";
            body.appendChild(tr);
        });

        var total = sum(list, function (i) { return i.harga * i.qty; });
        box.querySelectorAll("[data-cart-total]").forEach(function (el) { el.textContent = rp(total); });
        if (empty) { empty.hidden = list.length > 0; }
        if (table) { table.hidden = list.length === 0; }
        box.querySelectorAll("[data-needs-items]").forEach(function (el) {
            el.classList.toggle("is-disabled", list.length === 0);
            if (el.tagName === "BUTTON") { el.disabled = list.length === 0; }
            else { el.setAttribute("aria-disabled", String(list.length === 0)); el.style.pointerEvents = list.length ? "" : "none"; el.style.opacity = list.length ? "" : "0.45"; }
        });
        refreshCounts();
    }

    function renderAll() {
        document.querySelectorAll("[data-cart]").forEach(renderCart);
        refreshCounts();
    }

    document.addEventListener("click", function (e) {
        /* Tambah menu */
        var add = e.target.closest("[data-add]");
        if (add) {
            var key = add.dataset.key || "cart";
            var list = load(key);
            var found = list.filter(function (i) { return i.id === add.dataset.add; })[0];
            if (found) { found.qty += 1; }
            else { list.push({ id: add.dataset.add, nama: add.dataset.nama, harga: Number(add.dataset.harga), qty: 1 }); }
            save(key, list);
            add.classList.add("btn-success");
            setTimeout(function () { add.classList.remove("btn-success"); }, 400);
            renderAll();
            return;
        }

        /* Ubah jumlah */
        var qty = e.target.closest("[data-qty]");
        if (qty) {
            var box = qty.closest("[data-cart]");
            var items = load(box.dataset.cart);
            var idx = Number(qty.dataset.i);
            items[idx].qty += Number(qty.dataset.qty);
            if (items[idx].qty <= 0) { items.splice(idx, 1); }
            save(box.dataset.cart, items);
            renderAll();
            return;
        }

        /* Kosongkan / selesai */
        var clear = e.target.closest("[data-clear]");
        if (clear) {
            var msg = clear.dataset.done;
            sessionStorage.removeItem(clear.dataset.clear);
            if (msg) { alert(msg); }
            renderAll();
            return;
        }

        /* Tampilkan lebih banyak lapak */
        var more = e.target.closest("[data-more]");
        if (more) {
            var extra = document.querySelectorAll("[data-extra]");
            var open = more.dataset.state === "open";
            extra.forEach(function (el) { el.hidden = open; });
            more.dataset.state = open ? "closed" : "open";
            more.textContent = open ? "Tampilkan Lebih Banyak" : "Tutup";
            return;
        }

        /* Status pesanan: Baru -> Diproses -> Selesai */
        var order = e.target.closest("[data-order]");
        if (order) {
            var card = order.closest(".order-card");
            var badge = card.querySelector(".badge");
            var state = card.dataset.state;
            if (state === "baru") {
                card.dataset.state = "proses";
                badge.textContent = "Diproses"; badge.className = "badge badge-warning";
                order.textContent = "Tandai Selesai";
            } else if (state === "proses") {
                card.dataset.state = "selesai";
                badge.textContent = "Selesai"; badge.className = "badge badge-success";
                order.remove();
            }
        }
    });

    /* Pencarian cepat */
    document.querySelectorAll("[data-filter-input]").forEach(function (input) {
        input.addEventListener("input", function () {
            var q = input.value.toLowerCase();
            var moreBtn = document.querySelector("[data-more]");
            var expanded = moreBtn && moreBtn.dataset.state === "open";
            document.querySelectorAll("[data-filter-item]").forEach(function (item) {
                if (q === "") { item.hidden = item.hasAttribute("data-extra") && !expanded; }
                else { item.hidden = item.textContent.toLowerCase().indexOf(q) === -1; }
            });
            var none = document.querySelector("[data-filter-empty]");
            if (none) { none.hidden = document.querySelectorAll("[data-filter-item]:not([hidden])").length > 0; }
        });
    });

    /* Halaman pembayaran: total dan hitung mundur 5 menit */
    var totalEl = document.querySelector("[data-qris-total]");
    if (totalEl) {
        totalEl.textContent = rp(sum(load("cart"), function (i) { return i.harga * i.qty; }));
    }
    var timer = document.querySelector("[data-timer]");
    if (timer) {
        var left = 300;
        var tick = function () {
            var m = String(Math.floor(left / 60)).padStart(2, "0");
            var s = String(left % 60).padStart(2, "0");
            timer.textContent = m + ":" + s;
            if (left <= 0) {
                clearInterval(handle);
                timer.textContent = "Waktu habis";
                timer.classList.add("expired");
                return;
            }
            left -= 1;
        };
        var handle = setInterval(tick, 1000);
        tick();
    }

    renderAll();
})();
