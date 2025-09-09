<?php

if (!defined('ABSPATH')) {
    exit;
}

/** Helpers genéricos */
function thd_sanitize_text_array($val)
{
    $out = [];
    foreach ((array)($val ?? []) as $v) {
        $v = is_scalar($v) ? sanitize_text_field(wp_unslash($v)) : '';
        if ($v !== '') {
            $out[] = $v;
        }
    }
    return $out;
}

function thd_media_upload_if_present($field, $post_id)
{
    if (empty($_FILES[$field]['name'])) {
        return 0;
    }

    $max = 8 * 1024 * 1024; // ~8MB
    if (!empty($_FILES[$field]['size']) && $_FILES[$field]['size'] > $max) {
        return 0;
    }

    $allowed = get_allowed_mime_types();
    // asegura estos:
    $allowed['pdf']  = 'application/pdf';
    $allowed['xlsx'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    $allowed['xls']  = 'application/vnd.ms-excel';
    $allowed['csv']  = 'text/csv';

    $type = $_FILES[$field]['type'] ?? '';
    if ($type && !in_array($type, $allowed, true)) {
        return 0;
    }

    require_once ABSPATH.'wp-admin/includes/file.php';
    require_once ABSPATH.'wp-admin/includes/media.php';
    require_once ABSPATH.'wp-admin/includes/image.php';

    $id = media_handle_upload($field, $post_id);
    return is_wp_error($id) ? 0 : (int)$id;
}


function thd_s($p, $k, $t = 'text')
{
    $v = $p[$k] ?? '';
    return $t === 'email' ? sanitize_email($v) : ($t === 'url' ? esc_url_raw($v) : sanitize_text_field($v));
}

/** Utilidades para valores únicos (grupo social) */
function values_necesidades_groups(string $grupos_social = 'grupo_social'): array
{
    global $wpdb;
    $meta_key = 'necesidades_' . $grupos_social;
    $raw = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
        $meta_key
    ));
    $limpios = [];
    foreach ($raw as $entry) {
        if (empty($entry)) {
            continue;
        }
        $maybe = maybe_unserialize($entry);
        $items = is_array($maybe) ? $maybe : explode(',', (string)$entry);
        foreach ($items as $item) {
            $orig = trim((string)$item);
            if (!preg_match('/^[\p{L}\s]+$/u', $orig)) {
                continue;
            }
            $clave = normalizar_comparacion($orig);
            $prefer = mb_convert_case(trim($orig), MB_CASE_TITLE, 'UTF-8');
            if (!isset($limpios[$clave]) || tiene_acentos($prefer)) {
                $limpios[$clave] = $prefer;
            }
        }
    }
    ksort($limpios);
    return array_values($limpios);
}
function normalizar_comparacion($t): string
{
    $t = mb_strtolower((string)$t, 'UTF-8');
    $t = preg_replace(
        ['/[áÁ]/u','/[éÉ]/u','/[íÍ]/u','/[óÓ]/u','/[úÚ]/u','/[ñÑ]/u'],
        ['a','e','i','o','u','n'],
        $t
    );
    return trim($t);
}
function tiene_acentos($t): bool
{
    return (bool)preg_match('/[áéíóúÁÉÍÓÚ]/u', (string)$t);
}

function thd_distinct_meta_values($meta_key)
{
    global $wpdb;
    $rows = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} pm
     JOIN {$wpdb->posts} p ON p.ID=pm.post_id
     WHERE pm.meta_key=%s AND p.post_type='institucion' AND p.post_status IN ('publish','draft','pending')",
        $meta_key
    ));
    $out = [];
    foreach ($rows as $v) {
        $v = trim((string)$v);
        if ($v !== '') {
            $out[$v] = $v;
        }
    }
    ksort($out);
    return array_values($out);
}

// --- Aviso bonito (toast) ---
add_action('wp_head', function () {
    if (is_admin()) {
        return;
    } ?>
<style>
.thd-notice {
    position: fixed;
    right: 20px;
    top: 20px;
    z-index: 9999;
    max-width: 420px;
    padding: 14px 16px;
    border-radius: 14px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, .12);
    display: flex;
    gap: 10px;
    align-items: flex-start;
    background: linear-gradient(180deg, #E8F8EF, #DFF5EA);
    border: 1px solid #BCEAD9;
    color: #116149;
    font: 600 14px/1.4 system-ui, -apple-system, Segoe UI, Roboto, Arial
}

.thd-notice__icon {
    flex: 0 0 auto;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: inline-grid;
    place-items: center;
    background: #22C55E;
    color: #fff;
    font-size: 14px;
    line-height: 1
}

.thd-notice__text {
    flex: 1 1 auto
}

.thd-notice__close {
    appearance: none;
    border: 0;
    background: transparent;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    color: #125f46;
    opacity: .6
}

.thd-notice__close:hover {
    opacity: 1
}

.thd-notice.is-hide {
    opacity: 0;
    transform: translateY(-4px);
    transition: opacity .25s, transform .25s
}
</style>
<?php });

function thd_render_notice_from_query(array $map = [])
{
    $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
    if ($status !== 'success') {
        return '';
    }

    $text = $map['success'] ?? '¡Listo! La institución se guardó correctamente.';
    $text = esc_html($text);

    return '
  <div class="thd-notice" role="status" aria-live="polite">
    <span class="thd-notice__icon" aria-hidden="true">✓</span>
    <div class="thd-notice__text">'.$text.'</div>
    <button class="thd-notice__close" aria-label="Cerrar aviso">×</button>
  </div>
  <script>
    (function(){
      var n=document.querySelector(".thd-notice"); if(!n) return;
      var c=n.querySelector(".thd-notice__close");
      if(c) c.addEventListener("click",function(){ n.classList.add("is-hide"); setTimeout(function(){ n.remove(); },250); });
      setTimeout(function(){ n.classList.add("is-hide"); setTimeout(function(){ n.remove(); },250); }, 5000);
    })();
  </script>';
}

// (Opcional) Shortcode: [thd_notice success="Texto personalizado"]
add_shortcode('thd_notice', function ($atts) {
    $a = shortcode_atts(['success' => '¡Listo! La institución se guardó correctamente.'], $atts);
    return thd_render_notice_from_query(['success' => $a['success']]);
});

add_action('admin_notices', function () {
    if (!is_admin() || empty($_GET['status']) || $_GET['status'] !== 'success') {
        return;
    }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen && $screen->post_type === 'institucion') {
        echo '<div class="notice notice-success is-dismissible"><p>La institución se guardó correctamente.</p></div>';
    }
});

// ¿Hay archivo válido? (ID, array ACF o URL)
function thd_tiene_archivo($v): bool
{
    if (is_numeric($v)) {
        return get_post_status((int)$v) ? true : false;
    }
    if (is_array($v)) {
        return !empty($v['ID']) || !empty($v['url']);
    }
    if (is_string($v)) {
        return filter_var($v, FILTER_VALIDATE_URL) !== false;
    }
    return false;
}

// Badge de estado (capturado|autorizado|rechazado)
function thd_badge_estado($estado): string
{
    $e = strtolower(trim((string)$estado));
    $map = ['capturado' => '#64748b','autorizado' => '#16a34a','rechazado' => '#dc2626'];
    $txt = ucfirst($e ?: 'Capturado');
    $bg = $map[$e] ?? '#64748b';
    $style = 'display:inline-block;padding:.2rem .5rem;border-radius:999px;color:#fff;font-weight:600;font-size:.85rem;background:'.$bg;
    return '<span style="'.esc_attr($style).'">'.esc_html($txt).'</span>';
}

// Buscar field_key de un subcampo dentro de un grupo ACF por name
function thd_get_subfield_key(string $group_name, string $sub_name, int $post_id): ?string
{
    if (!function_exists('get_field_objects')) {
        return null;
    }
    $objs = get_field_objects($post_id);
    if (!$objs || !isset($objs[$group_name])) {
        return null;
    }
    $grp = $objs[$group_name];
    if (empty($grp['sub_fields'])) {
        return null;
    }
    foreach ($grp['sub_fields'] as $sf) {
        if (!empty($sf['name']) && $sf['name'] === $sub_name) {
            return $sf['key'] ?? null;
        }
    }
    return null;
}