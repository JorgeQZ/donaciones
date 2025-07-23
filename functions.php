<?php
require_once 'inc/instituciones.php';
require_once 'inc/inicio.php';

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('general', get_template_directory_uri() . '/css/general.css', [], null);

    // Si es la plantilla admin, quítalo y encola el admin
    if (is_page_template('page-consulta-inst.php') || is_singular('institucion')) {
        wp_dequeue_style('general');
        wp_deregister_style('general');

        wp_enqueue_style('general-admin', get_template_directory_uri() . '/css/general-admin.css', [], null);
    }

    if (is_singular('institucion')) {
        wp_enqueue_style('single-inst', get_template_directory_uri() . '/css/single-inst.css', [], null);

    }

    $template = basename(get_page_template());

    if ($template === 'page-instituciones.php') {

        wp_dequeue_style('general');
        wp_deregister_style('general');

        wp_enqueue_style('general-admin', get_template_directory_uri() . '/css/general-admin.css', [], null);
        wp_enqueue_style('single-inst', get_template_directory_uri() . '/css/single-inst.css', [], null);


    }

    $template = basename(get_page_template());
    if ($template === 'page-instituciones.php' || is_singular('institucion')) {
        wp_enqueue_style('instituciones', get_template_directory_uri() . '/css/instituciones.css', ['general']);
    }

    if ($template === 'page-inicio.php') {
        wp_enqueue_style('inicio', get_template_directory_uri() . '/css/inicio.css', ['general']);
    }

    if (is_page('aliados')) {
        wp_enqueue_style('aliados', get_template_directory_uri() . '/css/aliados.css', ['general']);
    }

    if (is_page('fundacion')) {
        wp_enqueue_style('fundacion', get_template_directory_uri() . '/css/fundacion.css', ['general']);
    }

    if (is_page('informe-anual')) {
        wp_enqueue_style('informe-anual', get_template_directory_uri() . '/css/informe.css', ['general']);
    }

    if (is_page('responsabilidad-social')) {
        wp_enqueue_style('responsabilidad-social', get_template_directory_uri() . '/css/responsabilidad.css', ['general']);
    }

    if (is_page('resultados')) {
        wp_enqueue_style('resultados', get_template_directory_uri() . '/css/resultados.css', ['general']);
    }
});

add_action('wp_enqueue_scripts', function () {
    if (is_page('fundacion')) {
        wp_enqueue_script(
            'js-fundacion',
            get_template_directory_uri() . '/js/fundacion.js',
            [],
            null,
            true
        );
    }

    if (is_page('responsabilidad-social')) {
        wp_enqueue_script(
            'js-responsabilidad',
            get_template_directory_uri() . '/js/responsabilidad.js',
            [],
            null,
            true
        );
    }
});

function registro_menus()
{
    register_nav_menus(array(
        'menu_header' => 'Menú Header',
    ));
}
add_action('init', 'registro_menus');

function cargar_js_header()
{
    wp_enqueue_script(
        'header-script',
        get_template_directory_uri() . '/js/header.js',
        array(),
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'cargar_js_header');


/**
 * Recargar tabla de instituciones mediante AJAX
 */
add_action('wp_ajax_recargar_tabla_instituciones', 'recargar_tabla_instituciones');
add_action('wp_ajax_nopriv_recargar_tabla_instituciones', 'recargar_tabla_instituciones');

function recargar_tabla_instituciones()
{
    get_template_part('templates/tabla-instituciones');
    wp_die();
}


/**
 *  Agregar script para cargar municipios dinámicamente por grupo de campos ACF
 */// AJAX handler para obtener el valor guardado de un campo ACF
add_action('wp_ajax_get_acf_select_value', 'get_acf_select_value');
function get_acf_select_value()
{
    if (!current_user_can('edit_posts'))
        wp_send_json_error();

    $post_id = intval($_GET['post_id']);
    $field_name = sanitize_text_field($_GET['field_name']);

    if (!$post_id || !$field_name) {
        wp_send_json_error('Missing parameters');
    }

    $value = get_field($field_name, $post_id);

    wp_send_json_success($value);
}

// Script JS para cargar selects dinámicamente en el admin
add_action('acf/input/admin_footer', 'acf_ajax_select_estado_municipio_script');
function acf_ajax_select_estado_municipio_script()
{
    $ajax_url = admin_url('admin-ajax.php');
    $post_id = get_the_ID();
    ?>
    <script>
        (function ($) {
            const estados = [
                "Aguascalientes", "Baja California", "Baja California Sur", "Campeche",
                "Chiapas", "Chihuahua", "Ciudad de México", "Coahuila", "Colima",
                "Durango", "Estado de México", "Guanajuato", "Guerrero", "Hidalgo",
                "Jalisco", "Michoacán", "Morelos", "Nayarit", "Nuevo León", "Oaxaca",
                "Puebla", "Querétaro", "Quintana Roo", "San Luis Potosí", "Sinaloa",
                "Sonora", "Tabasco", "Tamaulipas", "Tlaxcala", "Veracruz",
                "Yucatán", "Zacatecas"
            ];

            const jsonUrl = '<?php echo get_template_directory_uri(); ?>/js/municipios-estado.json';
            const ajaxUrl = '<?php echo $ajax_url; ?>';
            const postID = <?php echo $post_id; ?>;

            function poblarSelect($select, opciones, valorSeleccionado = null) {
                $select.empty().append('<option value="">Selecciona una opción</option>');
                opciones.forEach(e => {
                    const selected = (e === valorSeleccionado) ? ' selected' : '';
                    $select.append(`<option value="${e}"${selected}>${e}</option>`);
                });
            }

            function obtenerValorACF(field_name, callback) {
                $.get(ajaxUrl, {
                    action: 'get_acf_select_value',
                    post_id: postID,
                    field_name: field_name
                }, function (response) {
                    if (response.success) {
                        callback(response.data);
                    } else {
                        callback(null);
                    }
                });
            }

            $(document).ready(function () {
                $.getJSON(jsonUrl, function (data) {
                    const pares = [
                        { grupo: 'informacion_general', campoEstado: 'estado', campoMunicipio: 'municipio' },
                        { grupo: 'informacion_de_contacto', campoEstado: 'entidad_federativa', campoMunicipio: 'ciudad' }
                    ];

                    pares.forEach(par => {
                        const $estadoField = $(`.acf-field[data-name="${par.campoEstado}"]`);
                        const $estadoSelect = $estadoField.find('select');
                        const $grupo = $estadoField.closest('.acf-fields');
                        const $municipioSelect = $grupo.find(`.acf-field[data-name="${par.campoMunicipio}"] select`);

                        const fieldEstado = `${par.grupo}_${par.campoEstado}`;
                        const fieldMunicipio = `${par.grupo}_${par.campoMunicipio}`;

                        obtenerValorACF(fieldEstado, function (estadoGuardado) {
                            poblarSelect($estadoSelect, estados, estadoGuardado);

                            if (estadoGuardado) {
                                const municipios = data[estadoGuardado] || [];
                                obtenerValorACF(fieldMunicipio, function (municipioGuardado) {
                                    poblarSelect($municipioSelect, municipios, municipioGuardado);
                                });
                            }

                            $estadoSelect.on('change', function () {
                                const nuevoEstado = $(this).val();
                                const municipios = data[nuevoEstado] || [];
                                poblarSelect($municipioSelect, municipios, null);
                            });
                        });
                    });
                });
            });
        })(jQuery);
    </script>
    <?php
}
