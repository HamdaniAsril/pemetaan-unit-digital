<script>
document.querySelectorAll('[data-lihat-sandi]').forEach(b => b.addEventListener('click', () => {
  const i = document.getElementById(b.dataset.lihatSandi), lihat = i.type === 'password';
  i.type = lihat ? 'text' : 'password';
  b.setAttribute('aria-pressed', lihat);
  b.setAttribute('aria-label', lihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
  b.querySelector('use').setAttribute('href', lihat ? '#i-mata-tutup' : '#i-mata');
}));
</script>
