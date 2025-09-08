module.exports = {
  proxy: "localhost:8888/donaciones", // URL de tu WP en MAMP
  files: [
    "**/*.php",   // Vigila cambios en PHP
    "**/*.css",   // Vigila CSS
    "**/*.js",    // Vigila JS
    "**/*.scss"   // Vigila SCSS
  ],
  open: true,          // Abre el navegador automáticamente
  notify: false,       // Oculta notificaciones
  port: 3001,          // Cambia si 3000 está ocupado
  ghostMode: false,
  reloadDebounce: 500  // Evita reload múltiple rápido
};