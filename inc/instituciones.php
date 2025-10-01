<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/cpt-columns.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/form-handler.php';

/**
 * Text domain (ajústalo a tu tema/plugin si usas otro)
 */
const THD_TXTDOM = 'donaciones';

/**
 * CPT: instituciones
 */
function thd_registrar_cpt_instituciones()
{
    $labels = array(
        'name'                  => __('Instituciones', THD_TXTDOM),
        'singular_name'         => __('Institución', THD_TXTDOM),
        'menu_name'             => __('Instituciones', THD_TXTDOM),
        'name_admin_bar'        => __('Institución', THD_TXTDOM),
        'add_new'               => __('Agregar nueva', THD_TXTDOM),
        'add_new_item'          => __('Agregar nueva institución', THD_TXTDOM),
        'new_item'              => __('Nueva institución', THD_TXTDOM),
        'edit_item'             => __('Editar institución', THD_TXTDOM),
        'view_item'             => __('Ver institución', THD_TXTDOM),
        'all_items'             => __('Todas las instituciones', THD_TXTDOM),
        'search_items'          => __('Buscar instituciones', THD_TXTDOM),
        'not_found'             => __('No se encontraron instituciones', THD_TXTDOM),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-building',
        'supports'           => array('title'),
        'has_archive'        => false,
        'publicly_queryable' => true,
        'show_in_rest'       => true, // Gutenberg/REST
        'rewrite'            => array('slug' => 'institucion'),
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
    );

    register_post_type('institucion', $args);
}
add_action('init', 'thd_registrar_cpt_instituciones');

/**
 * ACF: registrar campos localmente (solo si ACF está activo)
 * Nota: mueve/ajusta este grupo si ya tienes uno maestro en JSON o UI.
 */
function thd_registrar_acf_instituciones()
{
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_presentacion_institucional',
        'title' => __('Presentación Institucional', THD_TXTDOM),
        'fields' => array(
            array(
                'key' => 'field_adjuntar_fotografias',
                'label' => __('Adjuntar Fotografías', THD_TXTDOM),
                'name' => 'adjuntar_fotografias',
                'type' => 'repeater',
                'instructions' => __('Puedes subir varias imágenes. Máximo 6.', THD_TXTDOM),
                'min' => 1,
                'max' => 6,
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_fotografia_1',
                        'label' => __('Fotografía', THD_TXTDOM),
                        'name' => 'fotografia',
                        'type' => 'image',
                        'return_format' => 'array',
                        'preview_size' => 'medium',
                        'library' => 'all'
                    )
                )
            )
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'institucion'
                )
            )
        )
    ));
}
add_action('acf/init', 'thd_registrar_acf_instituciones');

/**
 * Columnas personalizadas del listado
 */
add_filter('manage_institucion_posts_columns', function ($columns) {
    $new = array();
    $new['cb']              = $columns['cb'];
    $new['title']           = __('Nombre Fiscal', THD_TXTDOM);
    $new['rfc']             = __('RFC', THD_TXTDOM);
    $new['sede']            = __('Sede', THD_TXTDOM);
    $new['estado']          = __('Estado', THD_TXTDOM);
    $new['municipio']       = __('Municipio', THD_TXTDOM);
    $new['director']        = __('Director', THD_TXTDOM);
    $new['correo_contacto'] = __('Correo', THD_TXTDOM);
    $new['telefono']        = __('Teléfono', THD_TXTDOM);
    $new['date']            = $columns['date'];
    return $new;
});


if (!function_exists('acf_add_local_field_group')) {
    return;
}

add_action('acf/init', function () {
    acf_add_local_field_group([
      'key' => 'group_presentacion_institucional',
      'title' => 'Presentación Institucional',
      'fields' => [[
        'key' => 'field_adjuntar_fotografias','label' => 'Adjuntar Fotografías',
        'name' => 'adjuntar_fotografias','type' => 'repeater','instructions' => 'Máx. 6',
        'min' => 1,'max' => 6,'layout' => 'block',
        'sub_fields' => [[
          'key' => 'field_fotografia_1','label' => 'Fotografía','name' => 'fotografia',
          'type' => 'image','return_format' => 'array','preview_size' => 'medium','library' => 'all'
        ]]
      ]],
      'location' => [[['param' => 'post_type','operator' => '==','value' => 'institucion']]]
    ]);
});


if (!function_exists('thd_fecha_archivo')) {
    function thd_fecha_archivo($valor)
    {
        // Si es array con ID (return_format = array)
        if (is_array($valor) && !empty($valor['ID'])) {
            $att = get_post((int)$valor['ID']);
            if ($att) {
                $ts = mysql2date('U', $att->post_date, false);
                return date_i18n(get_option('date_format'), $ts);
            }
        }
        // Si es URL (return_format = url), intentamos mapear al adjunto
        if (is_string($valor) && $valor !== '') {
            $att_id = attachment_url_to_postid($valor);
            if ($att_id) {
                return get_the_date(get_option('date_format'), $att_id);
            }
        }
        return '';
    }
}
if (!function_exists('thd_tiene_archivo')) {
    function thd_tiene_archivo($valor)
    {
        if (empty($valor)) {
            return false;
        }
        if (is_array($valor)) {
            if (!empty($valor['url']) || !empty($valor['ID'])) {
                return true;
            }
            if (isset($valor[0]) && is_array($valor[0]) && !empty($valor[0]['url'])) {
                return true;
            }
            return false;
        }
        if (is_string($valor)) {
            return trim($valor) !== '';
        }
        if (is_numeric($valor)) {
            return (int)$valor > 0;
        }
        return !empty($valor);
    }
}


// ————————————————————————————————————————
// Helpers de presentación (archivos, imagen, badges, fecha)
// ————————————————————————————————————————
if (!function_exists('mostrar_archivo_existente')) {
    /**
     * Muestra link al archivo y (opcionalmente) su estado.
     * $grupo = array de 'archivos_requeridos' o 'presentacion_institucional'
     */
    function mostrar_archivo_existente($campo, $label, $grupo, $mostrar_estado = true)
    {
        if (empty($grupo[$campo])) {
            return;
        }

        $archivo = $grupo[$campo];
        $url = '';

        // Detecta si es array (objeto ACF de tipo archivo) o solo una URL
        if (is_array($archivo) && isset($archivo['url'])) {
            $url = $archivo['url'];
        } elseif (is_string($archivo)) {
            $url = $archivo;
        }

        if ($url) {
            echo '<p style="padding: 0 20px;"><a href="' . esc_url($url) . '" target="_blank">📎 Ver ' . esc_html($label) . '</a></p>';
        }

        // Estado del archivo (si aplica y si el usuario tiene permiso de ver)
        if ($mostrar_estado) {
            // Soporta: estado_del_{campo}, estado_{campo}, y caso especial RFC
            $posibles = [];
            if ($campo === 'rfc_archivo') {
                $posibles[] = 'estado_del_rfc';
            }
            $posibles[] = 'estado_del_' . $campo;
            $posibles[] = 'estado_' . $campo;

            $estado_val = null;
            foreach ($posibles as $k) {
                if (isset($grupo[$k]) && $grupo[$k] !== '') {
                    $estado_val = $grupo[$k];
                    break;
                }
            }
            if ($estado_val === null || $estado_val === '') {
                $estado_val = 'Capturado';
            }

            $estado = mb_strtolower((string)$estado_val, 'UTF-8');

            if ($estado === 'capturado') {
                $color = '#f0ad4e';
            } elseif ($estado === 'autorizado') {
                $color = '#5cb85c';
            } elseif ($estado === 'rechazado') {
                $color = '#d9534f';
            } else {
                $color = '#999';
            }

            echo '<p style="padding: 0 20px;"><strong>Estado:</strong> <span style="color:' . esc_attr($color) . '; font-weight:bold;">' . esc_html(ucfirst($estado)) . '</span></p>';
        }
    }
}

// ————————————————————————————————————————
// Handler de cambio de estado — SOLO subcampo por field_key
// con fallback a meta y mapeo de choices
// ————————————————————————————————————————
if (!function_exists('thd_get_subfield_key')) {
    function thd_get_subfield_key($group_name, $sub_name, $post_id)
    {
        $group = function_exists('get_field_object') ? get_field_object($group_name, $post_id) : null;
        if (!$group || empty($group['sub_fields'])) {
            return null;
        }
        foreach ($group['sub_fields'] as $sf) {
            if (!empty($sf['name']) && $sf['name'] === $sub_name) {
                return $sf['key']; // field_...
            }
        }
        return null;
    }
}

/**
 * Metabox: Fotografías de la Institución
 */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'institucion_fotos_box',
        'Fotografías de la Institución',
        'thd_institucion_fotos_metabox_cb',
        'institucion',
        'normal',
        'high'
    );
});

/**
 * Render del metabox
 */
function thd_institucion_fotos_metabox_cb($post)
{
    $ids = (array) get_post_meta($post->ID, '_institucion_fotos', true);
    if (empty($ids)) {
        echo '<p>No hay fotografías cargadas.</p>';
        return;
    }

    $file_paths = [];
    echo '<div style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-start;">';
    foreach ($ids as $aid) {
        $aid = (int) $aid;
        if ($aid <= 0) continue;

        echo '<div style="text-align:center;">';
        echo wp_get_attachment_image($aid, 'thumbnail', false, [
            'style' => 'display:block;width:200px;height:auto;border:1px solid #e5e5e5;border-radius:4px;'
        ]) ?: '<div style="width:96px;height:200px;background:#eee;border:1px solid #e5e5e5;border-radius:4px;"></div>';
        echo '</div>';

        $path = get_attached_file($aid);
        if ($path && file_exists($path)) {
            $file_paths[] = $path;
        }
    }
    echo '</div>';

    if (empty($file_paths)) {
        return;
    }

    if (!class_exists('ZipArchive')) {
        echo '<p style="margin-top:10px;color:#a00;">ZipArchive no está disponible en el servidor.</p>';
        return;
    }

    $uploads = wp_upload_dir();
    @unlink(trailingslashit($uploads['basedir']) . 'fotos_institucion_' . $post_id . '.zip');
    $zip_filename = 'fotos_institucion_' . $post->ID . '.zip';
    $zip_path     = trailingslashit($uploads['basedir']) . $zip_filename;
    $zip_url      = trailingslashit($uploads['baseurl']) . $zip_filename;

    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        foreach ($file_paths as $path) {
            $aid = attachment_url_to_postid(wp_get_attachment_url(array_search($path, $file_paths)));
            $zip->addFile($path, basename($path));
        }
        $zip->close();

        echo '<p><a class="button button-primary" href="' . esc_url($zip_url) . '" download>Descargar</a></p>';
    } else {
        echo '<p style="margin-top:10px;color:#a00;">No se pudo crear el ZIP.</p>';
    }
}

// Metabox dentro del grupo ACF "presentacion_institucional"
add_action('acf/input/admin_footer', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'institucion') return;
    ?>
    <style>
      .acf-field[data-name="fotos_institucion"] .thumbs {
        display:flex; flex-wrap:wrap; gap:8px; align-items:flex-start; flex-direction: column;
      }
      .acf-field[data-name="fotos_institucion"] .thumbs .item {
        text-align:center;
      }
      .acf-field[data-name="fotos_institucion"] .thumbs img {
        display:block; width:96px; height:auto;
        border:1px solid #e5e5e5; border-radius:4px;
      }
      .acf-field[data-name="fotos_institucion"] .button {
        margin-top:6px;
      }
    </style>
    <script>
    jQuery(function ($) {
      var $groupField = $('.acf-field[data-name="presentacion_institucional"]');
      if (!$groupField.length) return;

      var $box = $('#institucion_fotos_box');
      if (!$box.length) return;

      var $content = $box.find('> .inside').children().detach();
      if (!$content.length) return;

      var $target = $groupField.find('> .acf-input > .acf-fields');
      if (!$target.length) {
        $target = $groupField.find('> .acf-input');
      }

      var $wrap = $(
        '<div class="acf-field" data-name="fotos_institucion" data-type="message">' +
          '<div class="acf-label"><label>Fotografías</label></div>' +
          '<div class="acf-input"><div class="thumbs"></div></div>' +
        '</div>'
      );

      $wrap.find('.thumbs').append($content).find('> div').addClass('item');
      $target.append($wrap);

      $box.hide();
    });
    </script>
    <?php
});
