<?php

if (!defined('ABSPATH')) {
    exit;
}

/** CPT */
add_action('init', function () {
    register_post_type('institucion', [
      'labels' => [
        'name' => 'Instituciones',
        'singular_name' => 'Institución',
        'menu_name' => 'Instituciones',
      ],
      'public' => true, 'show_ui' => true, 'menu_position' => 20,
      'menu_icon' => 'dashicons-building',
      'supports' => ['title'],
      'has_archive' => false, 'publicly_queryable' => true, 'show_in_rest' => true,
      'rewrite' => ['slug' => 'institucion']
    ]);
});

/** Columnas admin */
add_filter('manage_institucion_posts_columns', function ($c) {
    return [
      'cb' => $c['cb'], 'title' => 'Nombre Fiscal', 'rfc' => 'RFC',
      'sede' => 'Sede', 'estado' => 'Estado', 'municipio' => 'Municipio',
      'director' => 'Director', 'correo_contacto' => 'Correo',
      'telefono' => 'Teléfono', 'date' => $c['date']
    ];
});

add_action('manage_institucion_posts_custom_column', function ($col, $id) {
    $ig = get_field('informacion_general', $id) ?: [];
    $ic = get_field('informacion_de_contacto', $id) ?: [];
    $dash = '—';

    $map = [
      'rfc'       => $ig['rfc'] ?? $dash,
      'sede'      => $ic['sede'] ?? $dash,
      'estado'    => $ig['estado'] ?? $dash,
      'municipio' => $ig['municipio'] ?? $dash,
      'director'  => $ic['datos_del_presidente']['nombre_del_presidente'] ?? $dash,
      'telefono'  => $ic['datos_del_presidente']['telefono'] ?? $dash,
    ];

    if ($col === 'correo_contacto') {
        $mail = $ic['datos_del_presidente']['correo_contacto'] ?? '';
        echo $mail ? '<a href="mailto:' . esc_attr($mail) . '">' . esc_html($mail) . '</a>' : $dash;
    } elseif (isset($map[$col])) {
        echo esc_html($map[$col]);
    }
}, 10, 2);

// Columnas ordenables
add_filter('manage_edit-institucion_sortable_columns', function ($cols) {
    $cols['rfc'] = 'rfc';
    $cols['estado'] = 'estado';
    $cols['municipio'] = 'municipio';
    $cols['sede'] = 'sede';
    $cols['director'] = 'director';
    return $cols;
});

// Aplicar orden por meta_key según columna
add_action('pre_get_posts', function ($q) {
    if (!is_admin() || !$q->is_main_query() || $q->get('post_type') !== 'institucion') {
        return;
    }
    $o = $q->get('orderby');
    $map = [
      'rfc'       => 'informacion_general_rfc',
      'estado'    => 'informacion_general_estado',
      'municipio' => 'informacion_general_municipio',
      'sede'      => 'informacion_de_contacto_sede',
      'director'  => 'informacion_de_contacto_datos_del_presidente_nombre_del_presidente',
    ];
    if (isset($map[$o])) {
        $q->set('meta_key', $map[$o]);
        $q->set('orderby', 'meta_value');
    }
});
// Dropdowns de filtro en admin
add_action('restrict_manage_posts', function () {
    global $typenow;
    if ($typenow !== 'institucion') {
        return;
    }
    $estado_actual = $_GET['f_estado'] ?? '';
    $mun_actual    = $_GET['f_municipio'] ?? '';
    $estados = thd_distinct_meta_values('informacion_general_estado');
    $munis  = thd_distinct_meta_values('informacion_general_municipio');

    echo '<select name="f_estado"><option value="">Todos los estados</option>';
    foreach ($estados as $e) {
        echo '<option '.selected($estado_actual, $e, false).' value="'.esc_attr($e).'">'.esc_html($e).'</option>';
    }
    echo '</select>';

    echo '<select name="f_municipio"><option value="">Todos los municipios</option>';
    foreach ($munis as $m) {
        echo '<option '.selected($mun_actual, $m, false).' value="'.esc_attr($m).'">'.esc_html($m).'</option>';
    }
    echo '</select>';
});

// Aplicar filtros
add_action('pre_get_posts', function ($q) {
    if (!is_admin() || !$q->is_main_query() || $q->get('post_type') !== 'institucion') {
        return;
    }
    $meta = [];
    if (!empty($_GET['f_estado'])) {
        $meta[] = ['key' => 'informacion_general_estado',   'value' => sanitize_text_field(wp_unslash($_GET['f_estado']))];
    }
    if (!empty($_GET['f_municipio'])) {
        $meta[] = ['key' => 'informacion_general_municipio','value' => sanitize_text_field(wp_unslash($_GET['f_municipio']))];
    }
    if ($meta) {
        $q->set('meta_query', count($meta) > 1 ? array_merge(['relation' => 'AND'], $meta) : $meta);
    }
});