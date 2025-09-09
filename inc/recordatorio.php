<?php

/** ================================
 *  RECORDATORIO: enviar por AJAX
 * ================================ */

// Permite a usuarios logueados (admin/editors o el dueño de la institución)
add_action('wp_ajax_thd_send_inst_reminder', 'thd_send_inst_reminder');

function thd_send_inst_reminder()
{
    // Seguridad básica
    if (!is_user_logged_in()) {
        wp_send_json_error(['msg' => 'Debes iniciar sesión.'], 401);
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'thd_send_reminder')) {
        wp_send_json_error(['msg' => 'Nonce inválido.'], 403);
    }

    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if (!$post_id || get_post_type($post_id) !== 'institucion') {
        wp_send_json_error(['msg' => 'Institución inválida.'], 400);
    }

    // Permisos: admin/editors o dueño (user_meta institucion_id == post_id)
    $user = wp_get_current_user();
    $is_adminish = user_can($user, 'manage_options') || user_can($user, 'edit_others_posts');
    $owns_post   = ((int) get_user_meta($user->ID, 'institucion_id', true) === $post_id);

    if (!$is_adminish && !$owns_post) {
        wp_send_json_error(['msg' => 'Sin permisos para enviar recordatorio.'], 403);
    }

    // Rate limit: evita spam (ej. 15 min)
    $throttle_key = 'thd_last_reminder_' . $post_id;
    if (get_transient($throttle_key)) {
        wp_send_json_error(['msg' => 'Ya se envió un recordatorio recientemente. Intenta más tarde.'], 429);
    }

    // 1) Determinar correo de contacto
    $to = thd_institucion_get_contact_email($post_id);
    if (!$to) {
        wp_send_json_error(['msg' => 'No se encontró un correo de contacto válido.'], 400);
    }

    // 2) Calcular faltantes
    $missing = thd_institucion_missing_fields($post_id); // array de textos
    $title   = get_the_title($post_id) ?: 'Tu institución';
    $url     = get_permalink($post_id) ?: home_url('/');
    $login_link = wp_login_url($url); // login con redirect al single

    // 3) Preparar correo
    $subject = 'Recordatorio: completa tu registro de institución';
    if (!empty($missing)) {
        $list = "- " . implode("\n- ", $missing);
        $body = "Hola,\n\nDetectamos que aún faltan algunos datos/documentos en el registro de su institución \"{$title}\".\n\n"
              . "Pendientes:\n{$list}\n\n"
              . "Accede aquí para completar tu registro:\n{$login_link}\n\n"
              . "Si ya completaste esta información, puedes ignorar este mensaje.\n\n"
              . "Saludos.";
    } else {
        // Por si todo está completo, igual enviamos un aviso amable
        $body = "Hola,\n\nEl registro de su institución \"{$title}\" ya se ve completo.\n"
              . "Si deseas revisarlo o actualizar algún dato, accede aquí:\n{$login_link}\n\nSaludos.";
    }

    // 4) Enviar y responder
    $sent = wp_mail($to, $subject, $body);
    if (!$sent) {
        wp_send_json_error(['msg' => 'No se pudo enviar el correo.'], 500);
    }

    // Throttle 15 min
    set_transient($throttle_key, 1, 15 * MINUTE_IN_SECONDS);

    wp_send_json_success(['msg' => 'Recordatorio enviado a ' . esc_html($to)]);
}

/** Obtener mejor correo de contacto de la institución */
function thd_institucion_get_contact_email($post_id)
{
    $ic = function_exists('get_field') ? (array) get_field('informacion_de_contacto', $post_id) : [];
    $cands = [];

    // Prioridad: datos_del_presidente > contacto_1 > contacto_2 > meta 'correo'
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

/** Detectar campos/documentos faltantes para la institución */
function thd_institucion_missing_fields($post_id)
{
    $faltantes = [];

    $ig = function_exists('get_field') ? (array) get_field('informacion_general', $post_id) : [];
    $ic = function_exists('get_field') ? (array) get_field('informacion_de_contacto', $post_id) : [];
    $ne = function_exists('get_field') ? (array) get_field('necesidades', $post_id) : [];
    $ar = function_exists('get_field') ? (array) get_field('archivos_requeridos', $post_id) : [];

    // Información general (según tu form)
    if (empty(trim((string)($ig['rfc'] ?? '')))) {
        $faltantes[] = 'RFC';
    }
    if (empty(trim((string)($ig['nombre_fiscal'] ?? '')))) {
        $faltantes[] = 'Nombre fiscal';
    }
    if (empty(trim((string)($ig['domicilio_fiscal'] ?? '')))) {
        $faltantes[] = 'Domicilio fiscal';
    }
    if (empty(trim((string)($ig['estado'] ?? '')))) {
        $faltantes[] = 'Estado';
    }
    if (empty(trim((string)($ig['municipio'] ?? '')))) {
        $faltantes[] = 'Municipio';
    }

    // Contacto
    if (empty(trim((string)($ic['entidad_federativa'] ?? '')))) {
        $faltantes[] = 'Entidad federativa';
    }
    if (empty(trim((string)($ic['ciudad'] ?? '')))) {
        $faltantes[] = 'Ciudad';
    }
    // Correo de contacto principal:
    $correo_pres = $ic['datos_del_presidente']['correo_contacto'] ?? '';
    if (!is_email($correo_pres)) {
        $faltantes[] = 'Correo de contacto';
    }

    // Necesidades
    if (empty(trim((string)($ne['numero_anual'] ?? '')))) {
        $faltantes[] = 'No. anual personas beneficiadas';
    }
    if (empty($ne['grupo_social'])) {
        $faltantes[] = 'Grupo social que atiende';
    }
    if (empty($ne['sector_apoyo'])) {
        $faltantes[] = 'Sector de apoyo';
    }

    // Archivos requeridos (ajusta según tus reglas reales)
    $req_files = [
        'acta_constitutiva'      => 'Acta Constitutiva (PDF)',
        'comprobante_domicilio'  => 'Comprobante de domicilio',
        'deducible'              => 'Copia de recibo deducible',
        'apoderado_legal'        => 'Identificación del apoderado legal',
        'institucion_excel'      => 'Solicitud de alta en Excel',
        'certificado_donaciones' => 'Certificado de donaciones (PDF)',
        'rfc_archivo'            => 'RFC en PDF',
    ];
    foreach ($req_files as $key => $label) {
        $v = $ar[$key] ?? '';
        // ACF puede devolver ID, array o string; validamos de forma flexible
        if (empty($v)) {
            $faltantes[] = $label;
        }
    }

    return $faltantes;
}

/** ============ RECORDATORIOS AUTOMÁTICOS (WP-Cron) ============ */

/** Programa un recordatorio en +7 días para esta institución (y reprograma si ya existía) */
function thd_schedule_institucion_reminder($post_id, $in_seconds = WEEK_IN_SECONDS)
{
    $post_id = (int) $post_id;
    if (!$post_id || get_post_type($post_id) !== 'institucion') {
        return;
    }

    // Evitar duplicados exactos
    $ts = wp_next_scheduled('thd_institucion_weekly_reminder', [$post_id]);
    if ($ts) {
        wp_unschedule_event($ts, 'thd_institucion_weekly_reminder', [$post_id]);
    }

    wp_schedule_single_event(time() + (int)$in_seconds, 'thd_institucion_weekly_reminder', [$post_id]);

    update_post_meta($post_id, '_thd_next_reminder_ts', time() + (int)$in_seconds);
    // contador
    $count = (int) get_post_meta($post_id, '_thd_reminder_count', true);
    if (!$count) {
        update_post_meta($post_id, '_thd_reminder_count', 0);
    }
}

/** Cancela cualquier recordatorio programado */
function thd_cancel_institucion_reminder($post_id)
{
    $post_id = (int) $post_id;
    while ($ts = wp_next_scheduled('thd_institucion_weekly_reminder', [$post_id])) {
        wp_unschedule_event($ts, 'thd_institucion_weekly_reminder', [$post_id]);
    }
    delete_post_meta($post_id, '_thd_next_reminder_ts');
}

/** Hook cron: revisar institución y enviar recordatorio si faltan cosas */
add_action('thd_institucion_weekly_reminder', function ($post_id) {
    $post_id = (int) $post_id;
    if (!$post_id || get_post_type($post_id) !== 'institucion') {
        return;
    }

    // Si ya está completa, cancelamos.
    if (!function_exists('thd_institucion_missing_fields')) {
        // fallback mínimo por si no pegaste el helper antes
        function thd_institucion_missing_fields($pid)
        {
            $faltan = [];
            $ig = function_exists('get_field') ? (array) get_field('informacion_general', $pid) : [];
            $ic = function_exists('get_field') ? (array) get_field('informacion_de_contacto', $pid) : [];
            $ne = function_exists('get_field') ? (array) get_field('necesidades', $pid) : [];
            $ar = function_exists('get_field') ? (array) get_field('archivos_requeridos', $pid) : [];

            if (empty($ig['rfc'])) {
                $faltan[] = 'RFC';
            }
            if (empty($ig['nombre_fiscal'])) {
                $faltan[] = 'Nombre fiscal';
            }
            if (empty($ig['domicilio_fiscal'])) {
                $faltan[] = 'Domicilio fiscal';
            }
            if (empty($ig['estado'])) {
                $faltan[] = 'Estado';
            }
            if (empty($ig['municipio'])) {
                $faltan[] = 'Municipio';
            }

            $correo = $ic['datos_del_presidente']['correo_contacto'] ?? '';
            if (!is_email($correo)) {
                $faltan[] = 'Correo de contacto';
            }

            if (empty($ne['numero_anual'])) {
                $faltan[] = 'No. anual personas beneficiadas';
            }
            if (empty($ne['grupo_social'])) {
                $faltan[] = 'Grupo social que atiende';
            }
            if (empty($ne['sector_apoyo'])) {
                $faltan[] = 'Sector de apoyo';
            }

            $req = ['acta_constitutiva','comprobante_domicilio','deducible','apoderado_legal','institucion_excel','certificado_donaciones','rfc_archivo'];
            foreach ($req as $k) {
                if (empty($ar[$k])) {
                    $faltan[] = $k;
                }
            }

            return $faltan;
        }
    }
    $missing = thd_institucion_missing_fields($post_id);

    if (empty($missing)) { // Completo
        thd_cancel_institucion_reminder($post_id);
        return;
    }

    // Limitar número de recordatorios (ej. 6 semanas máx.)
    $count = (int) get_post_meta($post_id, '_thd_reminder_count', true);
    if ($count >= 6) {
        thd_cancel_institucion_reminder($post_id);
        return;
    }

    // Correo de contacto
    if (!function_exists('thd_institucion_get_contact_email')) {
        function thd_institucion_get_contact_email($pid)
        {
            $ic = function_exists('get_field') ? (array) get_field('informacion_de_contacto', $pid) : [];
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
            $cands[] = get_post_meta($pid, 'correo', true);
            foreach ($cands as $c) {
                if ($c && is_email($c)) {
                    return $c;
                }
            }
            return '';
        }
    }
    $to = thd_institucion_get_contact_email($post_id);
    if (!$to) {
        // reprogramar para la semana próxima por si más tarde capturan el correo
        thd_schedule_institucion_reminder($post_id);
        return;
    }

    // Construir y enviar correo
    $title = get_the_title($post_id) ?: 'Tu institución';
    $url   = get_permalink($post_id) ?: home_url('/');
    $login_url = wp_login_url($url);
    $list = "- " . implode("\n- ", array_map('sanitize_text_field', $missing));

    $subject = 'Recordatorio semanal: completa tu registro de institución';
    $message = "Hola,\n\nHan pasado unos días desde que comenzaste el registro de \"{$title}\" y aún vemos datos pendientes:\n\n{$list}\n\n"
             . "Puedes completar tu registro aquí:\n{$login_url}\n\n"
             . "Si ya actualizaste la información, ignora este mensaje.\n\nSaludos.";

    @wp_mail($to, $subject, $message);

    // Marcas y reprogramación
    update_post_meta($post_id, '_thd_reminder_count', $count + 1);
    update_post_meta($post_id, '_thd_last_reminder_ts', time());

    thd_schedule_institucion_reminder($post_id); // reprograma otra semana
}, 10, 1);

/** Si borran la institución, limpia tareas programadas */
add_action('before_delete_post', function ($post_id) {
    if (get_post_type($post_id) === 'institucion') {
        thd_cancel_institucion_reminder($post_id);
    }
});




add_action('save_post_institucion', function ($post_id, $post, $update) {
    if (wp_is_post_revision($post_id)) {
        return;
    }
    if (empty(thd_institucion_missing_fields($post_id))) {
        thd_cancel_institucion_reminder($post_id);
    } else {
        // Si no hay nada programado, programa uno a una semana desde ahora
        if (!wp_next_scheduled('thd_institucion_weekly_reminder', [$post_id])) {
            thd_schedule_institucion_reminder($post_id, WEEK_IN_SECONDS);
        }
    }
}, 10, 3);



// ----- SOLO PARA PRUEBAS -----
add_action('template_redirect', function () {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Ejecuta el recordatorio YA para un post dado (ej: ?thd_test_reminder=123)
    if (isset($_GET['thd_test_reminder'])) {
        $pid = (int) $_GET['thd_test_reminder'];
        do_action('thd_institucion_weekly_reminder', $pid);
        wp_die('Recordatorio ejecutado para post ' . $pid);
    }

    // Programa recordatorio a 60s (ej: ?thd_test_schedule=123)
    if (isset($_GET['thd_test_schedule'])) {
        $pid = (int) $_GET['thd_test_schedule'];
        thd_schedule_institucion_reminder($pid, 60);
        wp_die('Recordatorio programado a 60 segundos para post ' . $pid);
    }

    // Cancela recordatorio (ej: ?thd_test_cancel=123)
    if (isset($_GET['thd_test_cancel'])) {
        $pid = (int) $_GET['thd_test_cancel'];
        thd_cancel_institucion_reminder($pid);
        wp_die('Recordatorio cancelado para post ' . $pid);
    }
});
// ----- FIN PRUEBAS -----