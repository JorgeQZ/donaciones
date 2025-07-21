<?php

/**
 * Template Name: Consulta Inst
 */

 if(!current_user_can( 'administrator')){
    wp_die( 'Acceso solo para administradores');
    exit;
 }
get_header( 'admin' ); ?>

<div class="container">
   <div class="title-cont">
      <div class="title">consulta de instituciones</div>
      <div class="icons">
        <div class="icon" id="add-institucion">
            <img src="<?php echo get_template_directory_uri().'/img/plus.png'; ?>" alt="Agregar institución">
         </div>

        <div class="icon" id="reload-table">
            <img src="<?php echo get_template_directory_uri().'/img/reload.png'; ?>" alt="Recargar tabla">
         </div>
         <div class="icon" id="search-box">
           <img src="<?php echo get_template_directory_uri().'/img/search.png'; ?>" alt="Recargar tabla">
         </div>
         <div class="icon"></div>
      </div>
   </div>

   <div id="instituciones-table">
      <?php get_template_part('templates/tabla-instituciones'); ?>
   </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
   function attachTbodyClickHandler() {
      const tbody = document.querySelector('tbody');
      if (tbody && !tbody.dataset.listenerAttached) {
        tbody.addEventListener('click', function (e) {
          const clickedRow = e.target.closest('tr');
          if (!clickedRow || e.target.tagName.toLowerCase() === 'input') return;
          const url = clickedRow.dataset.url;
          if (url) {
            window.location.href = url;
          }
        });
        tbody.dataset.listenerAttached = 'true'; // evita duplicar listeners
      }
    }

    // Ejecutar inmediatamente en el render inicial
    attachTbodyClickHandler();

    // Observar cambios dinámicos (como recargas AJAX)
    const observer = new MutationObserver(() => {
      attachTbodyClickHandler(); // Reintenta en cada cambio en el DOM
    });

    observer.observe(document.body, { childList: true, subtree: true });

    /**
     * Reload de la tabla de instituciones
     */
    const reloadIcon = document.getElementById('reload-table');

  reloadIcon.addEventListener('click', function() {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=recargar_tabla_instituciones')
      .then(response => response.text())
      .then(data => {
        document.getElementById('instituciones-table').innerHTML = data;
        console.log('Tabla recargada');
      });
  });

  /**
   * Redirigir al formulario de nueva institución
   */
  const addBtn = document.getElementById('add-institucion');
  addBtn.addEventListener('click', function () {
    const currentPath = window.location.pathname;
    const basePath = currentPath.split('/').slice(0, 3).join('/') + '/instituciones/';
    window.location.href = window.location.origin + basePath;
  });
});
</script>

<?php get_footer( 'admin' ); ?>