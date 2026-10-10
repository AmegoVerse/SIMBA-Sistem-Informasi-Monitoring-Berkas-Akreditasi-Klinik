document.addEventListener('click', function (e) {
  var t = e.target.closest('[data-konfirmasi]');
  if (t && !confirm(t.getAttribute('data-konfirmasi'))) e.preventDefault();
});
document.addEventListener('change', function (e) {
  var s = e.target.getAttribute && e.target.getAttribute('data-semua');
  if (s) document.querySelectorAll(s).forEach(function (c) { c.checked = e.target.checked; });
});
document.addEventListener('click', function (e) {
  if (e.target.closest('[data-cetak]')) { e.preventDefault(); window.print(); }
});
document.addEventListener('input', function (e) {
  var t = e.target, x;
  if (!t.getAttribute) return;
  if ((x = t.getAttribute('data-hex'))) { var o = document.querySelector(x); if (o) o.value = t.value; }
  if ((x = t.getAttribute('data-warna')) && /^#[0-9a-fA-F]{6}$/.test(t.value)) { var c = document.querySelector(x); if (c) c.value = t.value; }
  var v = t.getAttribute('data-var');
  if (v && /^#[0-9a-fA-F]{6}$/.test(t.value)) document.documentElement.style.setProperty(v, t.value);
});
