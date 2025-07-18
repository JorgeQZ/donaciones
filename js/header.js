document.addEventListener('DOMContentLoaded', function () {
  const btn = document.querySelector('.movil .hamburger');
  const nav = document.querySelector('.movil .nav-movil');

  if (btn && nav) {
    btn.addEventListener('click', function () {
      nav.classList.toggle('activo');
      btn.classList.toggle('open'); // activa animación CSS
    });
  }
});