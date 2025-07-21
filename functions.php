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
