/* MANDATORY - Halaman pelanggan: menu, keranjang, dan pengiriman pesanan */
(function () {
  'use strict';

  var D = JSON.parse(document.getElementById('data').textContent);
  var KEY = 'mandatory_cart_' + D.meja;
  var $ = function (id) { return document.getElementById(id); };
  var rp = function (n) { return 'Rp ' + String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
  var esc = function (s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };

  /* --- data menu --- */
  var P = {};
  D.katalog.forEach(function (k) { k.produk.forEach(function (p) { P[p.id] = p; }); });
  var pilih = {};   // pilihan Hot/Iced yang sedang aktif per produk

  /* --- keranjang (disimpan di browser agar tidak hilang saat refresh) --- */
  var cart = {};
  try { cart = JSON.parse(localStorage.getItem(KEY)) || {}; } catch (e) { cart = {}; }
  Object.keys(cart).forEach(function (k) {   // buang/perbarui item yang sudah berubah di menu
    var c = cart[k], p = P[c.id];
    var o = p && p.opsi.filter(function (x) { return x.k === c.opsi; })[0];
    if (!p || p.habis || !o) { delete cart[k]; return; }
    c.nama = p.nama; c.harga = o.h;
  });
  function save() { try { localStorage.setItem(KEY, JSON.stringify(cart)); } catch (e) {} }

  function totals() {
    var n = 0, t = 0;
    Object.keys(cart).forEach(function (k) { n += cart[k].qty; t += cart[k].qty * cart[k].harga; });
    return { n: n, t: t };
  }

  /* --- tampilan menu --- */
  function opsiAktif(p) {
    var k = pilih[p.id] !== undefined ? pilih[p.id] : p.opsi[0].k;
    return p.opsi.filter(function (o) { return o.k === k; })[0] || p.opsi[0];
  }

  function itemHTML(p) {
    var o = opsiAktif(p);
    var chips = p.opsi.length > 1 ? '<div class="chips">' + p.opsi.map(function (x) {
      return '<button type="button" class="chip' + (x.k === o.k ? ' on' : '') + '" data-chip="' + p.id + '" data-k="' + esc(x.k) + '">' + esc(x.k) + '</button>';
    }).join('') + '</div>' : (o.k ? '<span class="only">' + esc(o.k) + ' only</span>' : '');
    return '<article class="item' + (p.habis ? ' off' : '') + '" data-id="' + p.id + '" data-s="' + esc((p.nama + ' ' + p.desk).toLowerCase()) + '">' +
      '<div class="item-info"><h3>' + esc(p.nama) + (p.tag ? '<em>' + esc(p.tag) + '</em>' : '') + '<span class="badge" data-badge="' + p.id + '"></span></h3>' +
      (p.desk ? '<p>' + esc(p.desk) + '</p>' : '') + chips + '</div>' +
      '<div class="item-act"><strong data-price="' + p.id + '">' + rp(o.h) + '</strong>' +
      (p.habis ? '<span class="habis">Habis</span>' : '<button type="button" class="add" data-add="' + p.id + '">Tambah</button>') + '</div></article>';
  }

  $('tabs').innerHTML = D.katalog.map(function (k) { return '<a href="#kat' + k.id + '">' + esc(k.nama) + '</a>'; }).join('');
  $('menu').innerHTML = D.katalog.map(function (k) {
    return '<section class="kat" id="kat' + k.id + '"><h2>' + esc(k.nama) + '</h2>' + k.produk.map(itemHTML).join('') + '</section>';
  }).join('');

  function badges() {
    var jml = {};
    Object.keys(cart).forEach(function (k) { jml[cart[k].id] = (jml[cart[k].id] || 0) + cart[k].qty; });
    document.querySelectorAll('[data-badge]').forEach(function (b) {
      var n = jml[b.getAttribute('data-badge')];
      b.textContent = n ? '\u00d7' + n : '';
    });
  }

  function bar() {
    var s = totals();
    $('cartbar').hidden = s.n === 0;
    $('cbN').textContent = s.n + ' item';
    $('cbT').textContent = rp(s.t);
    badges();
  }

  function lines() {
    var ks = Object.keys(cart), s = totals();
    $('lines').innerHTML = ks.map(function (k) {
      var c = cart[k];
      return '<div class="line"><div><b>' + esc(c.nama) + '</b>' + (c.opsi ? ' <em>(' + esc(c.opsi) + ')</em>' : '') + '<br><small>' + rp(c.harga) + '</small></div>' +
        '<div class="qty"><button type="button" data-q="-1" data-k="' + esc(k) + '" aria-label="Kurangi">&minus;</button><span>' + c.qty +
        '</span><button type="button" data-q="1" data-k="' + esc(k) + '" aria-label="Tambah">+</button></div></div>';
    }).join('') + '<div class="line total"><span>Total</span><strong>' + rp(s.t) + '</strong></div>';
    $('kirim').textContent = 'Kirim pesanan \u00b7 ' + rp(s.t);
  }

  function ubah(key, d) {
    if (!cart[key]) return;
    cart[key].qty += d;
    if (cart[key].qty < 1) delete cart[key];
    else if (cart[key].qty > 20) cart[key].qty = 20;
    save(); bar();
    if (!Object.keys(cart).length) tutup(); else lines();
  }

  /* --- interaksi menu --- */
  $('menu').addEventListener('click', function (e) {
    var chip = e.target.closest('[data-chip]');
    if (chip) {
      var id = chip.getAttribute('data-chip');
      pilih[id] = chip.getAttribute('data-k');
      var art = chip.closest('.item');
      art.querySelectorAll('.chip').forEach(function (c) { c.classList.toggle('on', c === chip); });
      art.querySelector('[data-price]').textContent = rp(opsiAktif(P[id]).h);
      return;
    }
    var add = e.target.closest('[data-add]');
    if (add) {
      var p = P[add.getAttribute('data-add')], o = opsiAktif(p), key = p.id + '|' + o.k;
      if (!cart[key]) cart[key] = { id: p.id, nama: p.nama, opsi: o.k, harga: o.h, qty: 0 };
      if (cart[key].qty < 20) cart[key].qty++;
      save(); bar();
      add.classList.add('pop');
      setTimeout(function () { add.classList.remove('pop'); }, 300);
    }
  });

  $('cari').addEventListener('input', function () {
    var q = this.value.trim().toLowerCase(), ada = false;
    document.querySelectorAll('.kat').forEach(function (sec) {
      var n = 0;
      sec.querySelectorAll('.item').forEach(function (it) {
        var cocok = !q || it.getAttribute('data-s').indexOf(q) > -1;
        it.hidden = !cocok;
        if (cocok) n++;
      });
      sec.hidden = n === 0;
      if (n) ada = true;
    });
    $('kosong').hidden = ada;
  });

  /* --- keranjang (sheet) --- */
  function buka() { lines(); $('sheet').hidden = false; document.body.classList.add('lock'); }
  function tutup() { $('sheet').hidden = true; document.body.classList.remove('lock'); }
  $('cartbar').addEventListener('click', buka);
  $('sheet').addEventListener('click', function (e) {
    if (e.target.hasAttribute('data-close')) { tutup(); return; }
    var q = e.target.closest('[data-q]');
    if (q) ubah(q.getAttribute('data-k'), +q.getAttribute('data-q'));
  });

  /* --- kirim pesanan --- */
  try { $('nama').value = localStorage.getItem('mandatory_nama') || ''; } catch (e) {}
  $('form').addEventListener('submit', function (e) {
    e.preventDefault();
    var items = Object.keys(cart).map(function (k) { return { id: cart[k].id, opsi: cart[k].opsi, qty: cart[k].qty }; });
    if (!items.length) return;
    var btn = $('kirim'), err = $('err');
    btn.disabled = true; err.hidden = true;
    try { localStorage.setItem('mandatory_nama', $('nama').value); } catch (x) {}
    fetch('kirim.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ meja: D.meja, nama: $('nama').value, catatan: $('catatan').value, website: $('hp').value, items: items })
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (!res.ok || !res.j.ok) throw new Error(res.j.error || 'Gagal mengirim pesanan.');
        cart = {}; save();
        try { localStorage.setItem('mandatory_last_' + D.meja, res.j.kode); } catch (x) {}
        location.href = 'bayar_qris.php?kode=' + encodeURIComponent(res.j.kode);
      })
      .catch(function (x) {
        err.textContent = x.name === 'SyntaxError' ? 'Sistem sedang bermasalah, silakan hubungi kasir.' : (x.message || 'Koneksi bermasalah, coba lagi.');
        err.hidden = false; btn.disabled = false;
      });
  });

  /* --- link ke pesanan terakhir --- */
  try {
    var last = localStorage.getItem('mandatory_last_' + D.meja);
    if (last) { $('last').href = 'status.php?kode=' + encodeURIComponent(last); $('last').hidden = false; }
  } catch (e) {}

  bar();
})();
