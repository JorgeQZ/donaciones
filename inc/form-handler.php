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
        'correo_contacto_1'  => $correo_contacto_1,
        'telefono_1'         => $telefono_1,
      ],
      'grupo_persona_contacto_2' => [
        'persona_contacto_2' => $persona_contacto_2,
        'correo_contacto_2'  => $correo_contacto_2,
        'telefono_2'         => $telefono_2,
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

    /* =========================
     * USUARIO (login = RFC)
     * ========================= */
    $rfc_norm = strtoupper(preg_replace('/[^A-Z0-9Ñ&]/u', '', (string)$rfc));
    if (strlen($rfc_norm) < 10) {
        update_post_meta($post_id, '_institucion_user_error', 'RFC vacío o inválido.');
    } else {
        // 1) Password del form o autogenerada
        $pass1 = isset($p['inst_password']) ? (string)$p['inst_password'] : '';
        $pass2 = isset($p['inst_password_confirm']) ? (string)$p['inst_password_confirm'] : '';
        if (($pass1 !== '' || $pass2 !== '') && $pass1 !== $pass2) {
            update_post_meta($post_id, '_institucion_user_error', 'Las contraseñas no coinciden (se generó una nueva).');
            $pass1 = '';
        }
        $final_password = ($pass1 !== '') ? $pass1 : wp_generate_password(12, true, true);

        // 2) Email único para la cuenta WP + email de notificación
        $cands = array_filter([
            is_email($correo_contacto) ? $correo_contacto : '',
            is_email($correo_contacto_1) ? $correo_contacto_1 : '',
            is_email($correo_contacto_2) ? $correo_contacto_2 : '',
        ]);
        $notify_email = $cands ? reset($cands) : '';

        $domain = parse_url(home_url(), PHP_URL_HOST) ?: 'example.com';
        if (strpos($domain, '.') === false) {
            $domain = 'example.com';
        }
        $wp_email = ($notify_email && !email_exists($notify_email)) ? $notify_email : strtolower($rfc_norm).'@'.$domain;
        if (email_exists($wp_email)) {
            $wp_email = strtolower($rfc_norm) . '+' . time() . '@' . $domain;
        }

        // 3) ¿Existe ya un user con ese RFC?
        $existing = get_user_by('login', $rfc_norm);
        if ($existing) {
            $existing_link = (int) get_user_meta($existing->ID, 'institucion_id', true);
            if ($existing_link && $existing_link !== (int)$post_id) {
                update_post_meta($post_id, '_institucion_user_error', 'El RFC ya está usado por otra institución.');
            } else {
                // Vincular y asignar como autor
                update_post_meta($post_id, '_institucion_user_id', $existing->ID);
                update_user_meta($existing->ID, 'institucion_id', $post_id);
                update_user_meta($existing->ID, 'rfc', $rfc_norm);
                wp_update_post(['ID' => $post_id, 'post_author' => $existing->ID]);

                // Si hay pass en el form, resetear
                if ($pass1 !== '') {
                    wp_set_password($final_password, $existing->ID);
                    if ($notify_email) {
                        @wp_mail(
                            $notify_email,
                            'Acceso actualizado',
                            "Hola,\n\nTu acceso:\nUsuario (RFC): {$rfc_norm}\nContraseña: {$final_password}\nAcceso: ".wp_login_url()."\n"
                        );
                    }
                    set_transient('thd_inst_pw_'.$post_id, ['rfc' => $rfc_norm,'password' => $final_password], 10 * MINUTE_IN_SECONDS);
                }
            }
        } else {
            // 4) Crear usuario nuevo
            $display_name = $nombre_fiscal ?: $rfc_norm;
            error_log('USER_CREATE RFC='.$rfc_norm.' | email='.$wp_email);

            $user_id = wp_insert_user([
                'user_login'   => $rfc_norm,
                'user_pass'    => $final_password,
                'user_email'   => $wp_email,
                'display_name' => $display_name,
                'nickname'     => $display_name,
                'role'         => 'subscriber',
            ]);

            if (is_wp_error($user_id)) {
                update_post_meta($post_id, '_institucion_user_error', $user_id->get_error_message());
            } else {
                // Vincular y autor
                update_post_meta($post_id, '_institucion_user_id', $user_id);
                update_user_meta($user_id, 'institucion_id', $post_id);
                update_user_meta($user_id, 'rfc', $rfc_norm);
                wp_update_post(['ID' => $post_id, 'post_author' => $user_id]);

                // Redundancia útil
                update_post_meta($post_id, 'rfc', $rfc_norm);
                if ($notify_email) {
                    update_post_meta($post_id, 'correo', sanitize_email($notify_email));
                }

                // Notificar + mostrar 1 vez
                if ($notify_email) {
                    @wp_mail(
                        $notify_email,
                        'Tus credenciales de acceso',
                        "Hola,\n\nSe creó tu acceso:\nUsuario (RFC): {$rfc_norm}\nContraseña: {$final_password}\nAcceso: ".wp_login_url()."\n"
                    );
                }
                set_transient('thd_inst_pw_'.$post_id, ['rfc' => $rfc_norm,'password' => $final_password], 10 * MINUTE_IN_SECONDS);
            }
        }
    }

    // Redirect directo al single de la institución recién creada
    $permalink = get_permalink($post_id);
    $permalink = add_query_arg('status', 'success', $permalink);
    // Programar recordatorio automático en 7 días
    if (empty(thd_institucion_missing_fields($post_id))) {
        thd_cancel_institucion_reminder($post_id);
    } else {
        thd_schedule_institucion_reminder($post_id);
    }


    if ($permalink) {
        wp_safe_redirect($permalink);
        exit;
    }

    wp_safe_redirect(get_post_type_archive_link('institucion') ?: home_url('/'));
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


    $uid = get_current_user_id();
    if ($uid) {
        $tkey = "thd_estados_bypass_{$uid}_{$institucion_id}";
        $map = get_transient($tkey);
        if (!is_array($map)) {
            $map = array();
        }
        $map[$archivo_key] = $target_value;   // ej. 'acta_constitutiva' => 'autorizado'
        set_transient($tkey, $map, 60);       // TTL 60s
    }

    // Redirect
    nocache_headers();
    $redirect = add_query_arg(array('status' => 'success','msg' => 'Estado actualizado','r' => wp_generate_uuid4()), get_permalink($institucion_id));
    wp_safe_redirect($redirect);
    exit;
}


function thd_estado_archivo($post_id, $campo)
{
    // 1) Bypass por usuario (si existe)
    $uid = get_current_user_id();
    if ($uid) {
        $tkey = "thd_estados_bypass_{$uid}_{$post_id}";
        $map = get_transient($tkey);
        if (is_array($map) && array_key_exists($campo, $map)) {
            return $map[$campo]; // 'autorizado' | 'rechazado' | 'capturado'
        }
    }
    // 2) Meta fresco (todas las variantes)
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


    // 🔽🔽🔽 PÉGALO AQUÍ (bypass por usuario) 🔽🔽🔽
    $uid = get_current_user_id();
    if ($uid) {
        $tkey = "thd_estados_bypass_{$uid}_{$institucion_id}";
        $map = get_transient($tkey);
        if (!is_array($map)) {
            $map = array();
        }
        $map[$archivo_key] = $target_value;
        set_transient($tkey, $map, 60);
    }
    // 🔼🔼🔼 FIN BYPASS 🔼🔼🔼

    // Responder con el badge listo para pintar
    $badge_html = function_exists('thd_badge_estado') ? thd_badge_estado($target_value) : ucfirst($nuevo_estado);
    wp_send_json_success(['estado' => $target_value, 'badge' => $badge_html]);
}


// Admin puede actualizar la contraseña del usuario ligado a la institución
add_action('wp_ajax_thd_admin_reset_inst_password', 'thd_admin_reset_inst_password');
function thd_admin_reset_inst_password()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['msg' => 'Debes iniciar sesión.'], 401);
    }
    // Cambia el permiso si quieres permitir a editores:
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['msg' => 'Sin permisos.'], 403);
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'thd_admin_reset_inst_password')) {
        wp_send_json_error(['msg' => 'Nonce inválido.'], 403);
    }

    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if (!$post_id || get_post_type($post_id) !== 'institucion') {
        wp_send_json_error(['msg' => 'Institución inválida.'], 400);
    }

    // Ubicar usuario ligado
    $user_id = (int) get_post_meta($post_id, '_institucion_user_id', true);
    if (!$user_id) {
        $u = get_users([
            'number'    => 1,
            'meta_key'  => 'institucion_id',
            'meta_value' => $post_id,
            'fields'    => 'ID',
        ]);
        $user_id = !empty($u) ? (int) $u[0] : 0;
    }
    if (!$user_id) {
        wp_send_json_error(['msg' => 'No hay usuario ligado a esta institución.'], 404);
    }

    // Contraseña nueva (del form) o autogenerada
    $p1 = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $p2 = isset($_POST['password_confirm']) ? (string) $_POST['password_confirm'] : '';
    if (($p1 !== '' || $p2 !== '') && $p1 !== $p2) {
        wp_send_json_error(['msg' => 'Las contraseñas no coinciden.'], 422);
    }
    $new_pass = ($p1 !== '') ? $p1 : wp_generate_password(12, true, true);

    // Cambiar password
    wp_set_password($new_pass, $user_id);

    // Preparar datos para notificación y/o “flash” en pantalla
    $rfc = get_user_meta($user_id, 'rfc', true);
    if (!$rfc) {
        $rfc = get_post_meta($post_id, 'rfc', true);
    }

    // (Opcional) enviar correo al contacto
    if (!function_exists('thd_institucion_get_contact_email')) {
        function thd_institucion_get_contact_email($post_id)
        {
            $ic = function_exists('get_field') ? (array) get_field('informacion_de_contacto', $post_id) : [];
            $cands = [];
            if (!empty($ic['datos_del_presidente']['correo_contacto'])) {
                $cands[] = $ic['datos_del_presidente']['correo_contacto'];
            }
            if (!empty($ic['grupo_persona_contacto_uno']['correo_contacto_1'])) {
                $cands[] = $ic['grupo_persona_contacto_uno']['correo_contacto_1'];
            }
            if (!empty($ic['grupo_persona_contacto_2']['correo_contacto_2'])) {
                $cands[] = $ic['grupo_persona_contacto_2']['correo_contacto_2'];
            }
            $cands[] = get_post_meta($post_id, 'correo', true);
            foreach ($cands as $c) {
                if ($c && is_email($c)) {
                    return $c;
                }
            }
            return '';
        }
    }
    $to = thd_institucion_get_contact_email($post_id);
    if ($to) {
        @wp_mail(
            $to,
            'Tu contraseña fue actualizada',
            "Hola,\n\nTu acceso fue actualizado:\nUsuario (RFC): {$rfc}\nContraseña: {$new_pass}\nAcceso: ".wp_login_url(get_permalink($post_id))."\n"
        );
    }

    // (Opcional) dejar transient para mostrar en el single una sola vez
    set_transient('thd_inst_pw_'.$post_id, ['rfc' => $rfc, 'password' => $new_pass], 10 * MINUTE_IN_SECONDS);

    wp_send_json_success([
        'msg'      => 'Contraseña actualizada correctamente.',
        'password' => $new_pass, // se devuelve porque solo el admin puede llamar este endpoint
        'rfc'      => $rfc,
    ]);
}