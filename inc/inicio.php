<?php

// Se añade owl carousel
function enqueue_owl_from_cdn() {
    // CSS de Owl Carousel
    wp_enqueue_style('owl-carousel', 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css');
    wp_enqueue_style('owl-theme', 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css');

    // JS de Owl Carousel (requiere jQuery)
    wp_enqueue_script('owl-carousel', 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js', array('jquery'), null, true);

    // Tu script de inicialización
    wp_enqueue_script('owl-init', get_template_directory_uri() . '/js/inicio.js', array('owl-carousel'), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_owl_from_cdn');

// Se añade el js de inicio en el template
function cargar_js_inicio() {
    if (is_page_template('templates/page-inicio.php')) {
        wp_enqueue_script(
            'inicio-js',
            get_template_directory_uri() . '/js/inicio.js',
            array('jquery'), // o [] si no depende de jQuery
            null,
            true // cargar en el footer
        );
    }
}
add_action('wp_enqueue_scripts', 'cargar_js_inicio');

// Shortcode s5 de inicio
function shortcode_html() {
    ob_start();
    ?>
    <div class="inicio-shortcode">
        <div class="inputs">
            <p class="input-1">*RFC</p>
            <input type="text" placeholder="(Una vez registrado usar RFC como usuario de acceso)">
        </div>
        <div class="inputs dos">
            <p class="input-2">Contraseña</p>
            <input type="password">
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('inicio_shortcode', 'shortcode_html');