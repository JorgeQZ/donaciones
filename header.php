<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

    <header>
        <div class="desktop">
            <img src="<?php echo get_template_directory_uri(); ?>/imgs/logo.png" alt="Logo" class="logo">

            <?php
            wp_nav_menu(array(
                'theme_location' => 'menu_header',
                'container' => 'nav',
                'container_class' => 'main-nav',
                'menu_class' => 'menu',
            ));
            ?>
        </div>

        <div class="movil">
            <div class="sub_uno">
                <div>
                    <img src="<?php echo get_template_directory_uri(); ?>/imgs/logo.png" alt="Logo" class="logo">
                </div>

                <div>
                    <button class="hamburger" aria-label="Abrir menú">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>

            <div class="sub_dos">
                <nav class="nav-movil hidden">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'menu_header',
                        'container' => 'nav',
                        'container_class' => 'main-nav',
                        'menu_class' => 'menu',
                    ));
                    ?>
                </nav>
            </div>
        </div>
    </header>