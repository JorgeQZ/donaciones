<?php

if (!defined('ABSPATH')) {
    exit;
}

/** Handler formulario (POST) */
function thd_handle_institucion_form()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit_institucion'])) {
        return;
    }
    if (!isset($_POST['institucion_nonce']) || !wp_verify_nonce($_POST['institucion_nonce'], 'registrar_institucion')) {
        wp_die('Nonce inválido.');
    }
    if (!is_user_logged_in() || !current_user_can('edit_posts')) {
        wp_die('Permisos insuficientes.');
    }

    $p = wp_unslash($_POST);

    // Datos principales
    $rfc = thd_s($p, 'rfc');
    $nombre_fiscal = thd_s($p, 'nombre_fiscal');
    $domicilio_fiscal = thd_s($p, 'domicilio_fiscal');
    $tipo_institucion = thd_s($p, 'tipo_institucion');
    $estado = thd_s($p, 'estado');
    $municipio = thd_s($p, 'municipio');

    // Contacto
    $entidad_federativa = thd_s($p, 'entidad_federativa');
    $ciudad = thd_s($p, 'ciudad');
    $sede = thd_s($p, 'sede');
    $subsidiaria = thd_s($p, 'institucion_subsidiaria');
    $nombre_director = thd_s($p, 'nombre_del_presidente');
    $correo_contacto = thd_s($p, 'correo_contacto', 'email');
    $telefono = thd_s($p, 'telefono');
    $persona_contacto_1 = thd_s($p, 'persona_contacto_1');
    $correo_contacto_1 = thd_s($p, 'correo_contacto_1', 'email');
    $telefono_1 = thd_s($p, 'telefono_1');
    $persona_contacto_2 = thd_s($p, 'persona_contacto_2');
    $correo_contacto_2 = thd_s($p, 'correo_contacto_2', 'email');
    $telefono_2 = thd_s($p, 'telefono_2');

    // Redes + tiendas
    $web = thd_s($p, 'web', 'url');
    $facebook = thd_s($p, 'facebook');
    $instagram = thd_s($p, 'instagram');
    $tiktok = thd_s($p, 'tiktok');
    $tienda_adicional = thd_s($p, 'tienda_adicional');
    $direccion_tienda = thd_s($p, 'direccion_tienda_adicional');

    // Necesidades
    $necesidad = thd_s($p, 'necesidad');
    $numero_anual = thd_s($p, 'numero_anual');
    $grupo_social = thd_sanitize_text_array($p['grupo_social'] ?? []);
    $sector_apoyo = thd_sanitize_text_array($p['sector_apoyo'] ?? []);
    $tipo_labor = thd_s($p, 'tipo_labor');

    // Crear/actualizar post
    $institucion_id = intval($p['institucion_id'] ?? 0);
    $post_data = ['post_type' => 'institucion','post_title' => $nombre_fiscal,'post_status' => 'publish'];
    $post_id = ($institucion_id > 0 && get_post_type($institucion_id) === 'institucion')
      ? wp_update_post($post_data + ['ID' => $institucion_id], true)
      : wp_insert_post($post_data, true);

    if (is_wp_error($post_id) || !$post_id) {
        wp_die('Error al registrar la institución.');
    }

    // ACF: Información General
    update_field('informacion_general', [
      'rfc' => $rfc,'nombre_fiscal' => $nombre_fiscal,'domicilio_fiscal' => $domicilio_fiscal,
      'tipo_institucion' => $tipo_institucion,'estado' => $estado,'municipio' => $municipio
    ], $post_id);

    // ACF: Información de Contacto
    update_field('informacion_de_contacto', [
      'entidad_federativa' => $entidad_federativa,'ciudad' => $ciudad,'sede' => $sede,
      'institucion_subsidiaria' => $subsidiaria,
      'tienda_adicional' => $tienda_adicional,'direccion_tienda_adicional' => $direccion_tienda,
      'datos_del_presidente' => [
        'nombre_del_presidente' => $nombre_director,
        'correo_contacto' => $correo_contacto,
        'telefono' => $telefono,
      ],
      'grupo_persona_contacto_uno' => [
        'persona_contacto_1' => $persona_contacto_1,
        'correo_contacto_1' => $correo_contacto_1,
        'telefono_1' => $telefono_1,
      ],
      'grupo_persona_contacto_2' => [
        'persona_contacto_2' => $persona_contacto_2,
        'correo_contacto_2' => $correo_contacto_2,
        'telefono_2' => $telefono_2,
      ],
      'redes_sociales' => [
        'web' => $web,'facebook' => $facebook,'instagram' => $instagram,'tiktok' => $tiktok
      ],
    ], $post_id);

    // ACF: Necesidades
    update_field('necesidades', [
      'necesidad' => $necesidad,'numero_anual' => $numero_anual,
      'grupo_social' => $grupo_social,'sector_apoyo' => $sector_apoyo,'tipo_labor' => $tipo_labor
    ], $post_id);

    // Subidas (presentación institucional)
    $presentacion = [];
    foreach (['logo_de_la_institucion','carta_solicitud','fotografias'] as $fld) {
        $aid = thd_media_upload_if_present($fld, $post_id);
        if ($aid) {
            $presentacion[$fld] = $aid;
        }
    }
    if ($presentacion) {
        update_field('presentacion_institucional', $presentacion, $post_id);
    }

    // Subidas (archivos requeridos)
    $requeridos = [];
    foreach ([
      'acta_constitutiva','comprobante_domicilio','deducible','apoderado_legal',
      'institucion_excel','certificado_donaciones','rfc_archivo'
    ] as $fld) {
        $aid = thd_media_upload_if_present($fld, $post_id);
        if ($aid) {
            $requeridos[$fld] = $aid;
        }
    }
    if ($requeridos) {
        update_field('archivos_requeridos', $requeridos, $post_id);
    }

    // Redirect
    $target = wp_get_referer() ?: get_permalink($post_id);
    wp_safe_redirect(add_query_arg('status', 'success', $target));
    exit;
}

// Hooks para manejar POST directo y vía admin-post
add_action('init', 'thd_handle_institucion_form'); // si el formulario hace POST a la misma URL
add_action('admin_post_nopriv_registrar_institucion', 'thd_handle_institucion_form');
add_action('admin_post_registrar_institucion', 'thd_handle_institucion_form');
add_action('admin_post_cambiar_estado_archivo', 'thd_cambiar_estado_archivo');

function thd_cambiar_estado_archivo()
{
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        wp_die('Permisos insuficientes.');
    }

    $institucion_id = intval($_POST['institucion_id'] ?? 0);
    if (!$institucion_id || get_post_type($institucion_id) !== 'institucion') {
        wp_die('Solicitud inválida.');
    }
    if (!isset($_POST['estado_nonce']) || !wp_verify_nonce($_POST['estado_nonce'], 'cambiar_estado_archivo_'.$institucion_id)) {
        wp_die('Nonce inválido.');
    }

    $archivo_key     = sanitize_key($_POST['archivo_key'] ?? '');
    $nuevo_estado_in = strtolower(trim((string)($_POST['nuevo_estado'] ?? '')));
    $valid = array('capturado','autorizado','rechazado');
    if (!$archivo_key || !in_array($nuevo_estado_in, $valid, true)) {
        wp_die('Solicitud inválida.');
    }

    // Posibles names de subcampo
    $sub_names = array();
    if ($archivo_key === 'rfc_archivo') {
        $sub_names[] = 'estado_del_rfc';
    }
    $sub_names[] = 'estado_'.$archivo_key;
    $sub_names[] = 'estado_del_'.$archivo_key;

    // Intentar encontrar field_key del subcampo (ACF)
    $sub_key = null;
    $found_name = null;
    if (function_exists('thd_get_subfield_key')) {
        foreach ($sub_names as $sn) {
            $k = thd_get_subfield_key('archivos_requeridos', $sn, $institucion_id);
            if ($k) {
                $sub_key = $k;
                $found_name = $sn;
                break;
            }
        }
    }
    if (!$found_name) {
        $found_name = $sub_names[0];
    }
    $meta_key = 'archivos_requeridos_'.$found_name;

    // Normalizar valor según choices (si ACF define choices)
    $target_value = $nuevo_estado_in;
    if ($sub_key && function_exists('get_field_object')) {
        $fo = get_field_object($sub_key, $institucion_id);
        if (!empty($fo['choices']) && is_array($fo['choices'])) {
            foreach ($fo['choices'] as $val => $label) {
                if (strtolower((string)$val) === $nuevo_estado_in || strtolower((string)$label) === $nuevo_estado_in) {
                    $target_value = $val;
                    break;
                }
            }
        }
    }

    // === GUARDAR EN ACF (si hay field_key) ===
    $saved = null;
    if ($sub_key && function_exists('update_field')) {
        $saved = update_field($sub_key, $target_value, $institucion_id);
    }

    // === GUARDAR EN TODAS LAS CLAVES META SINÓNIMAS (fallback + coherencia inmediata) ===
    $all_names = array_values(array_unique($sub_names)); // e.g. ['estado_'.$archivo_key, 'estado_del_'.$archivo_key, 'estado_del_rfc']
    foreach ($all_names as $name) {
        update_post_meta($institucion_id, 'archivos_requeridos_' . $name, $target_value);
    }

    if ($saved === false || $saved === null) {
        update_post_meta($institucion_id, $meta_key, $target_value);
    }


    // 🔥 LIMPIEZA DE CACHÉ POR POST
    if (function_exists('clean_post_cache')) {
        clean_post_cache($institucion_id);
    }
    wp_cache_delete($institucion_id, 'post_meta');

    // Limpieza de cachés internas de ACF (si está disponible)
    if (function_exists('acf_flush_value_cache')) {
        acf_flush_value_cache($institucion_id); // desde ACF 5.11+
    }


    if (function_exists('acf_get_store')) {
        // Por si alguna versión no tiene el flush anterior
        acf_get_store('values')->reset();
        acf_get_store('meta')->reset();
        acf_get_store('fields')->reset();
    }

    // Evita que caches intermedias guarden la respuesta del redirect
    nocache_headers();

    // 🚀 Redirect con "cache-buster" único
    $redirect = add_query_arg(array(
      'status' => 'success',
      'msg'    => 'Estado actualizado',
      'r'      => wp_generate_uuid4(),  // cache-buster
    ), get_permalink($institucion_id));

    wp_safe_redirect($redirect);
    exit;
}

function thd_estado_archivo($post_id, $campo)
{
    $keys = array();
    if ($campo === 'rfc_archivo') {
        $keys[] = 'archivos_requeridos_estado_del_rfc';
    }
    $keys[] = 'archivos_requeridos_estado_'.$campo;
    $keys[] = 'archivos_requeridos_estado_del_'.$campo;

    foreach ($keys as $k) {
        $v = get_post_meta($post_id, $k, true);
        if ($v !== '' && $v !== null) {
            return $v;
        }
    }
    return 'capturado';
}

// AJAX: cambiar estado de un archivo (sin recargar)
add_action('wp_ajax_thd_cambiar_estado_archivo', 'thd_ajax_cambiar_estado_archivo');
function thd_ajax_cambiar_estado_archivo()
{
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        wp_send_json_error(['msg' => 'Permisos insuficientes'], 403);
    }

    $institucion_id = intval($_POST['institucion_id'] ?? 0);
    $archivo_key    = sanitize_key($_POST['archivo_key'] ?? '');
    $nuevo_estado   = strtolower(trim((string)($_POST['nuevo_estado'] ?? '')));
    $nonce          = sanitize_text_field($_POST['nonce'] ?? '');

    if (!$institucion_id || get_post_type($institucion_id) !== 'institucion') {
        wp_send_json_error(['msg' => 'Post inválido'], 400);
    }
    if (!wp_verify_nonce($nonce, 'thd_estado_'.$institucion_id)) {
        wp_send_json_error(['msg' => 'Nonce inválido'], 400);
    }

    $valid = ['capturado','autorizado','rechazado'];
    if (!$archivo_key || !in_array($nuevo_estado, $valid, true)) {
        wp_send_json_error(['msg' => 'Datos inválidos'], 400);
    }

    // Posibles names de subcampo
    $sub_names = [];
    if ($archivo_key === 'rfc_archivo') {
        $sub_names[] = 'estado_del_rfc';
    }
    $sub_names[] = 'estado_'.$archivo_key;
    $sub_names[] = 'estado_del_'.$archivo_key;

    // Buscar field_key del subcampo ACF (si existe)
    $sub_key = null;
    $found_name = null;
    if (function_exists('thd_get_subfield_key')) {
        foreach ($sub_names as $sn) {
            $k = thd_get_subfield_key('archivos_requeridos', $sn, $institucion_id);
            if ($k) {
                $sub_key = $k;
                $found_name = $sn;
                break;
            }
        }
    }
    if (!$found_name) {
        $found_name = $sub_names[0];
    }
    $meta_key = 'archivos_requeridos_'.$found_name;

    // Normalizar valor por choices del field (si aplica)
    $target_value = $nuevo_estado;
    if ($sub_key && function_exists('get_field_object')) {
        $fo = get_field_object($sub_key, $institucion_id);
        if (!empty($fo['choices']) && is_array($fo['choices'])) {
            foreach ($fo['choices'] as $val => $label) {
                if (strtolower((string)$val) === $nuevo_estado || strtolower((string)$label) === $nuevo_estado) {
                    $target_value = $val;
                    break;
                }
            }
        }
    }

    // Guardar
    $saved = null;
    if ($sub_key && function_exists('update_field')) {
        $saved = update_field($sub_key, $target_value, $institucion_id);
    }
    if ($saved === false || $saved === null) {
        update_post_meta($institucion_id, $meta_key, $target_value);
    }

    // Limpiar caches
    if (function_exists('clean_post_cache')) {
        clean_post_cache($institucion_id);
    }
    wp_cache_delete($institucion_id, 'post_meta');
    if (function_exists('acf_flush_value_cache')) {
        acf_flush_value_cache($institucion_id);
    }

    // Responder con el badge listo para pintar
    $badge_html = function_exists('thd_badge_estado') ? thd_badge_estado($target_value) : ucfirst($nuevo_estado);
    wp_send_json_success(['estado' => $target_value, 'badge' => $badge_html]);
}