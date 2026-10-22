/* MANDATORY - Papan antrian staff: ambil pesanan tiap beberapa detik, ubah status, bunyi saat ada pesanan baru */
(function () {
  'use strict';

  var board = document.getElementById('board');
  var csrf = board.getAttribute('data-csrf');
  var sync = document.getElementById('sync');
  var snd = document.getElementById('snd');
  var seen = null;          // id pesanan yang sudah pernah tampil (null = pemuatan pertama)
  var suara = false, ctx = null;

  var esc = function (s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var rp = function (n) { return 'Rp ' + String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };

  var NEXT = { Baru: ['Diproses', 'Proses'], Diproses: ['Siap', 'Siap'], Siap: ['Selesai', 'Selesai'] };

  function card(p) {
    var items = p.items.map(function (i) {
      return '<li>' + i.q + '&times; ' + esc(i.n) + (i.o ? ' <em>(' + esc(i.o) + ')</em>' : '') + '</li>';
    }).join('');
    var btn = '';
    if (NEXT[p.status]) btn += '<button class="btn btn-red sm" data-id="' + p.id + '" data-to="' + NEXT[p.status][0] + '">' + NEXT[p.status][1] + '</button>';
    if (p.bayar === 'Belum') btn += '<a class="btn btn-line sm" href="bayar.php?id=' + p.id + '">Bayar</a>';
    else btn += '<span class="tag ok">Lunas</span>';
    if ((p.status === 'Baru' || p.status === 'Diproses' || p.status === 'Siap') && p.bayar === 'Belum')
      btn += '<button class="btn-x" data-id="' + p.id + '" data-to="Batal">Batal</button>';
    return '<article class="o-card' + (p.lama >= 10 && p.status === 'Baru' ? ' late' : '') + '">' +
      '<div class="o-top"><b>' + esc(p.meja) + '</b><span>' + esc(p.kode) + ' &middot; ' + p.lama + ' mnt</span></div>' +
      '<div class="o-nama">' + esc(p.nama) + '</div><ul>' + items + '</ul>' +
      (p.catatan ? '<p class="o-cat">&#128221; ' + esc(p.catatan) + '</p>' : '') +
      '<div class="o-foot"><strong>' + rp(p.total) + '</strong></div><div class="o-btn">' + btn + '</div></article>';
  }

  function render(list) {
    ['Baru', 'Diproses', 'Siap', 'Selesai'].forEach(function (s) {
      var rows = list.filter(function (p) { return p.status === s; });
      board.querySelector('[data-col="' + s + '"]').innerHTML = rows.length ? rows.map(card).join('') : '<p class="kosong">Kosong</p>';
      board.querySelector('[data-n="' + s + '"]').textContent = rows.length;
    });
  }

  function bunyi() {
    if (!suara) return;
    try {
      ctx = ctx || new (window.AudioContext || window.webkitAudioContext)();
      [660, 880].forEach(function (f, i) {
        var o = ctx.createOscillator(), g = ctx.createGain();
        o.frequency.value = f; o.connect(g); g.connect(ctx.destination);
        g.gain.setValueAtTime(0.2, ctx.currentTime + i * 0.18);
        g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.18 + 0.16);
        o.start(ctx.currentTime + i * 0.18); o.stop(ctx.currentTime + i * 0.18 + 0.17);
      });
    } catch (e) {}
  }

  function muat() {
    fetch('api.php?aksi=antrian', { cache: 'no-store' })
      .then(function (r) {
        if (r.status === 401) { location.href = 'login.php'; throw new Error('login'); }
        return r.json();
      })
      .then(function (d) {
        if (!d.ok) throw new Error(d.error);
        var baru = seen && d.pesanan.some(function (p) { return p.status === 'Baru' && seen.indexOf(p.id) === -1; });
        seen = d.pesanan.map(function (p) { return p.id; });
        render(d.pesanan);
        if (baru) bunyi();
        sync.textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
      })
      .catch(function (e) { if (e.message !== 'login') sync.textContent = 'Koneksi terputus, mencoba lagi\u2026'; });
  }

  board.addEventListener('click', function (e) {
    var b = e.target.closest('[data-to]');
    if (!b) return;
    if (b.getAttribute('data-to') === 'Batal' && !confirm('Batalkan pesanan ini?')) return;
    b.disabled = true;
    fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ aksi: 'status', id: +b.getAttribute('data-id'), status: b.getAttribute('data-to'), csrf: csrf })
    })
      .then(function (r) { return r.json(); })
      .then(function (d) { if (!d.ok) alert(d.error || 'Gagal mengubah status.'); muat(); })
      .catch(function () { alert('Koneksi bermasalah, coba lagi.'); muat(); });
  });

  snd.addEventListener('click', function () {
    suara = !suara;
    snd.innerHTML = suara ? '&#128266; Suara nyala' : '&#128263; Suara mati';
    if (suara) bunyi();   // sekaligus mengaktifkan audio di browser
  });

  muat();
  setInterval(muat, 7000);
})();
