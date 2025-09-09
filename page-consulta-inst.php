<?php
/**
 * Template Name: Consulta Inst
 */
if (! current_user_can('manage_options')) {
    wp_die('Acceso solo para administradores');
    exit;
}
get_header('admin');

// URL del alta (ajústala si tu página tiene otra ruta o ID)
$alta_url = site_url('/instituciones/');

// Nonce para recargar tabla
$recarga_nonce = wp_create_nonce('recargar_tabla_instituciones');
$ajax_url      = admin_url('admin-ajax.php');
?>

<div class="container">
    <div class="title-cont">
        <div class="title">consulta de instituciones</div>
        <div class="icons">
            <div class="icon" id="add-institucion" data-url="<?php echo esc_url($alta_url); ?>">
                <img src="<?php echo esc_url(get_template_directory_uri().'/img/plus.png'); ?>"
                    alt="Agregar institución">
            </div>

            <div class="icon" id="reload-table" data-ajax="<?php echo esc_url($ajax_url); ?>"
                data-nonce="<?php echo esc_attr($recarga_nonce); ?>">
                <img src="<?php echo esc_url(get_template_directory_uri().'/img/reload.png'); ?>" alt="Recargar tabla">
            </div>

            <div class="icon" id="search-box-toggle">
                <img src="<?php echo esc_url(get_template_directory_uri().'/img/search.png'); ?>" alt="Buscar">
            </div>
            <div class="icon"></div>
        </div>
    </div>

    <!-- buscador simple -->
    <div id="search-wrapper" style="display:none; margin:8px 0;">
        <input type="text" id="search-input" placeholder="Buscar por nombre, RFC, estado..."
            style="width:100%; padding:8px;">
    </div>

    <div id="instituciones-table">
        <?php get_template_part('templates/tabla-instituciones'); ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delegación para navegar por fila
    function attachTbodyClickHandler() {
        const tbody = document.querySelector('#instituciones-table tbody');
        if (tbody && !tbody.dataset.listenerAttached) {
            tbody.addEventListener('click', function(e) {
                const isLink = e.target.closest('a');
                const isInput = ['input', 'button', 'select', 'textarea', 'label'].includes(e.target
                    .tagName.toLowerCase());
                if (isLink || isInput) return;
                const tr = e.target.closest('tr');
                if (!tr) return;
                const url = tr.dataset.url;
                if (url) window.location.href = url;
            });
            tbody.dataset.listenerAttached = 'true';
        }
    }
    attachTbodyClickHandler();

    // Observa recargas dinámicas
    const observer = new MutationObserver(attachTbodyClickHandler);
    observer.observe(document.getElementById('instituciones-table'), {
        childList: true,
        subtree: true
    });

    // Reload tabla (AJAX + nonce)
    const reloadIcon = document.getElementById('reload-table');
    reloadIcon.addEventListener('click', function() {
        const ajax = this.getAttribute('data-ajax');
        const nonce = this.getAttribute('data-nonce');
        const params = new URLSearchParams({
            action: 'recargar_tabla_instituciones',
            _ajax_nonce: nonce,
            _t: Date.now()
        });

        this.style.opacity = '0.5';

        fetch(ajax, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: params.toString()
            })
            .then(r => r.text())
            .then(html => {
                document.getElementById('instituciones-table').innerHTML = html;
            })
            .catch(() => alert('No se pudo recargar la tabla'))
            .finally(() => {
                this.style.opacity = '1';
            });
    });

    // Botón "Agregar institución"
    const addBtn = document.getElementById('add-institucion');
    addBtn.addEventListener('click', function() {
        const url = this.getAttribute('data-url');
        if (url) window.location.href = url;
    });

    // Buscar
    const toggle = document.getElementById('search-box-toggle');
    const wrap = document.getElementById('search-wrapper');
    const input = document.getElementById('search-input');

    toggle.addEventListener('click', () => {
        wrap.style.display = wrap.style.display === 'none' ? 'block' : 'none';
        if (wrap.style.display === 'block') input.focus();
    });

    input.addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        const rows = document.querySelectorAll('#instituciones-table tbody tr');
        rows.forEach(tr => {
            const text = tr.innerText.toLowerCase();
            tr.style.display = text.includes(q) ? '' : 'none';
        });
    });
});
</script>

<?php get_footer('admin'); ?>