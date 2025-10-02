<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <header>
        <div class="desktop">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <img src="<?php echo esc_url(get_template_directory_uri()); ?>/imgs/logo.png" alt="Logo" class="logo">
            </a>
            <div class="contenedor">
                <div class="contenedor-usuario">
                    <p class="usuario">Hola, Javier</p>
                    <svg class="arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 9l6 6 6-6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="dropdown">
                    <a href="<?php echo esc_url( wp_logout_url( home_url('/') ) ); ?>">
                        <p>Cerrar sesión</p>
                    </a>
                </div>
            </div>
        </div>
    </header>