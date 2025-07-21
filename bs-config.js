// bs-config.js
module.exports = {
  proxy: "http://localhost:8888/donaciones",
  files: ["*.php", "css/*.css", "js/*.js", "**/*.php"],
  injectChanges: true,
  open: true,
  notify: false
};
