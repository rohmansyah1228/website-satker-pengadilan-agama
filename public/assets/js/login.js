/* Tampilkan/sembunyikan password */
document.getElementById('togglePw').addEventListener('click', function () {
    const i = document.getElementById('current-password'); const show = i.type === 'password';
    i.type = show ? 'text' : 'password'; this.setAttribute('aria-pressed', show);
    this.querySelector('.visually-hidden').textContent = show ? 'Sembunyikan password' : 'Tampilkan password';
});
