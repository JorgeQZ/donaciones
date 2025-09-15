<?php
if (!defined('ABSPATH')) {
    exit;
}

/** ====== Ajustes de seguridad para intentos ====== */
if (!defined('THD_LOGIN_MAX_FAILS')) {
    define('THD_LOGIN_MAX_FAILS', 5);
}   // intentos antes de bloquear
if (!defined('THD_LOGIN_WINDOW_MIN')) {
    define('THD_LOGIN_WINDOW_MIN', 15);
} // ventana (min) para contar fallos
if (!defined('THD_LOGIN_BLOCK_MIN')) {
    define('THD_LOGIN_BLOCK_MIN', 15);
}  // bloqueo (min)

/** ====== Helpers ====== */
if (!function_exists('thd_get_client_ip')) {
    function thd_get_client_ip()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '0.0.0.0';
    }
}
if (!function_exists('thd_normalize_rfc')) {
    function thd_normalize_rfc($rfc_raw)
    {
        $r = strtoupper(wp_strip_all_tags($rfc_raw));
        $r = preg_replace('/[^A-Z0-9Ñ&]/u', '', $r);
        return $r;
    }
}
if (!function_exists('thd_is_valid_rfc')) {
    function thd_is_valid_rfc($rfc)
    {
        $moral  = '/^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/u'; // 12
        $fisica = '/^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/u'; // 13
        return (bool)(preg_match($moral, $rfc) || preg_match($fisica, $rfc));
    }
}
if (!function_exists('thd_transient_remaining_secs')) {
    function thd_transient_remaining_secs($key)
    {
        $opt = get_option('_transient_timeout_' . $key);
        if (!$opt) {
            return 0;
        }
        $left = (int)$opt - time();
        return max(0, $left);
    }
}
if (!function_exists('thd_attempt_key_ip')) {
    function thd_attempt_key_ip()
    {
        return 'thd_login_ip_'   . md5(thd_get_client_ip());
    }
}
if (!function_exists('thd_block_key_ip')) {
    function thd_block_key_ip()
    {
        return 'thd_block_ip_'  . md5(thd_get_client_ip());
    }
}
if (!function_exists('thd_attempt_key_user')) {
    function thd_attempt_key_user($l)
    {
        return 'thd_login_user_' . md5($l);
    }
}
if (!function_exists('thd_block_key_user')) {
    function thd_block_key_user($l)
    {
        return 'thd_block_user_' . md5($l);
    }
}

if (!function_exists('thd_is_blocked')) {
    function thd_is_blocked($login)
    {
        $ip_block_key  = thd_block_key_ip();
        $usr_block_key = thd_block_key_user($login);
        $ip_blocked    = (bool) get_transient($ip_block_key);
        $usr_blocked   = (bool) get_transient($usr_block_key);
        $ttl_ip  = thd_transient_remaining_secs($ip_block_key);
        $ttl_usr = thd_transient_remaining_secs($usr_block_key);
        return array('blocked' => ($ip_blocked || $usr_blocked), 'ttl' => max($ttl_ip, $ttl_usr));
    }
}
if (!function_exists('thd_register_fail')) {
    function thd_register_fail($login)
    {
        $ip_cnt_key  = thd_attempt_key_ip();
        $usr_cnt_key = thd_attempt_key_user($login);
        $ip_cnt  = (int) get_transient($ip_cnt_key);
        $usr_cnt = (int) get_transient($usr_cnt_key);
        $ip_cnt++;
        $usr_cnt++;
        set_transient($ip_cnt_key, $ip_cnt, THD_LOGIN_WINDOW_MIN * 60);
        set_transient($usr_cnt_key, $usr_cnt, THD_LOGIN_WINDOW_MIN * 60);

        if ($ip_cnt >= THD_LOGIN_MAX_FAILS) {
            set_transient(thd_block_key_ip(), 1, THD_LOGIN_BLOCK_MIN * 60);
            delete_transient($ip_cnt_key);
        }
        if ($usr_cnt >= THD_LOGIN_MAX_FAILS) {
            set_transient(thd_block_key_user($login), 1, THD_LOGIN_BLOCK_MIN * 60);
            delete_transient($usr_cnt_key);
        }
        $left_ip  = max(0, THD_LOGIN_MAX_FAILS - $ip_cnt);
        $left_usr = max(0, THD_LOGIN_MAX_FAILS - $usr_cnt);
        return min($left_ip, $left_usr);
    }
}
if (!function_exists('thd_clear_attempts')) {
    function thd_clear_attempts($login)
    {
        delete_transient(thd_attempt_key_ip());
        delete_transient(thd_attempt_key_user($login));
        delete_transient(thd_block_key_ip());
        delete_transient(thd_block_key_user($login));
    }
}

/** ========== AJAX: Login ========== */
add_action('wp_ajax_nopriv_thd_subscriber_login', 'thd_subscriber_login_ajax');
add_action('wp_ajax_thd_subscriber_login', 'thd_subscriber_login_ajax');
if (!function_exists('thd_subscriber_login_ajax')) {
    function thd_subscriber_login_ajax()
    {
        check_ajax_referer('thd_login_nonce', 'security');

        $rfc_raw     = isset($_POST['rfc']) ? $_POST['rfc'] : '';
        $password    = isset($_POST['password']) ? $_POST['password'] : '';
        $remember    = !empty($_POST['remember']);
        $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : home_url('/');

        $rfc = thd_normalize_rfc($rfc_raw);

        $blk = thd_is_blocked($rfc);
        if ($blk['blocked']) {
            $mins = ceil($blk['ttl'] / 60);
            wp_send_json_error(array('message' => "Demasiados intentos. Intenta de nuevo en ~{$mins} min."));
        }

        if (empty($rfc) || empty($password) || !thd_is_valid_rfc($rfc)) {
            $left = thd_register_fail($rfc);
            wp_send_json_error(array('message' => 'Usuario o contraseña inválidos.', 'left' => $left));
        }

        // Buscar por login=RFC o meta 'rfc'
        $user = get_user_by('login', $rfc);
        if (!$user) {
            $users = get_users(array(
                'meta_key'   => 'rfc',
                'meta_value' => $rfc,
                'number'     => 1,
                'fields'     => 'all',
            ));
            if (!empty($users)) {
                $user = $users[0];
            }
        }

        $creds = array(
            'user_login'    => $user ? $user->user_login : $rfc,
            'user_password' => $password,
            'remember'      => $remember,
        );
        $signon = wp_signon($creds, is_ssl());
        if (is_wp_error($signon)) {
            $left = thd_register_fail($rfc);
            $msg  = 'Usuario o contraseña inválidos.';
            if ($left > 0) {
                $msg .= " Te quedan {$left} intentos.";
            }
            wp_send_json_error(array('message' => $msg, 'left' => $left));
        }

        if (!in_array('subscriber', (array) $signon->roles, true)) {
            wp_logout();
            $left = thd_register_fail($rfc);
            wp_send_json_error(array('message' => 'No autorizado para este acceso.', 'left' => $left));
        }

        thd_clear_attempts($rfc);
        $safe_redirect = wp_validate_redirect($redirect_to, home_url('/'));
        wp_send_json_success(array('message' => '¡Bienvenido!', 'redirect' => $safe_redirect));
    }
}
/** ========== AJAX: Recuperación ========== */
add_action('wp_ajax_nopriv_thd_subscriber_lostpass', 'thd_subscriber_lostpass_ajax');
add_action('wp_ajax_thd_subscriber_lostpass', 'thd_subscriber_lostpass_ajax');
if (!function_exists('thd_subscriber_lostpass_ajax')) {
    function thd_subscriber_lostpass_ajax()
    {
        check_ajax_referer('thd_login_nonce', 'security');
        $rfc_raw = isset($_POST['rfc']) ? $_POST['rfc'] : '';
        $rfc     = thd_normalize_rfc($rfc_raw);

        if ($rfc && thd_is_valid_rfc($rfc)) {
            $user = get_user_by('login', $rfc);
            if (!$user) {
                $users = get_users(array(
                    'meta_key'   => 'rfc',
                    'meta_value' => $rfc,
                    'number'     => 1,
                    'fields'     => 'all',
                ));
                if (!empty($users)) {
                    $user = $users[0];
                }
            }
            if ($user) {
                retrieve_password($user->user_login);
            }
        }
        wp_send_json_success(array(
            'message' => 'Si tu RFC está registrado, te enviamos un enlace para restablecer la contraseña.'
        ));
    }
}
/** ========== Shortcode ========== */
if (!function_exists('shortcode_html')) {
    function shortcode_html($atts = array())
    {
        $atts = shortcode_atts(array('redirect' => ''), $atts, 'inicio_shortcode');

        $redirect_to = $atts['redirect'] ? esc_url(home_url($atts['redirect'])) : esc_url(add_query_arg(array()));
        $nonce = wp_create_nonce('thd_login_nonce');
        $ajax  = admin_url('admin-ajax.php');

        ob_start(); ?>
<div class="inicio-shortcode" data-thd-login>
    <form class="thd-login-form" method="post" action="<?php echo esc_url($ajax); ?>" novalidate>
        <input type="hidden" name="action" value="thd_subscriber_login">
        <input type="hidden" name="security" value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

        <div class="inputs">
            <label class="input-1" for="thd-rfc">*RFC</label>
            <input id="thd-rfc" name="rfc" type="text" placeholder="(Usa tu RFC como usuario)" inputmode="latin"
                autocomplete="username" required maxlength="13" pattern="^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$"
                oninput="this.value=this.value.toUpperCase()">
        </div>

        <div class="inputs dos">
            <label class="input-2" for="thd-pass">Contraseña</label>
            <input id="thd-pass" name="password" type="password" autocomplete="current-password" required>
            <div class="thd-inline thd-showpass">
                <label><input type="checkbox" id="thd-showpass"> Mostrar contraseña</label>
            </div>
        </div>

        <label style="display:flex;gap:.4rem;align-items:center;margin:.5rem 0;">
            <input type="checkbox" name="remember" value="1"> Recordarme
        </label>

        <button type="submit" class="thd-btn">Ingresar</button>

        <p style="margin-top:.75rem;">
            <a href="#" id="thd-forgot-link">¿Olvidaste tu contraseña?</a>
        </p>

        <div class="thd-msg" aria-live="polite" style="margin-top:10px;"></div>
    </form>

    <form class="thd-lostpass-form" method="post" action="<?php echo esc_url($ajax); ?>" style="display:none;">
        <input type="hidden" name="action" value="thd_subscriber_lostpass">
        <input type="hidden" name="security" value="<?php echo esc_attr($nonce); ?>">
        <div class="inputs">
            <label for="thd-rfc-lost">Tu RFC</label>
            <input id="thd-rfc-lost" name="rfc" type="text" placeholder="RFC con homoclave" inputmode="latin" required
                maxlength="13" pattern="^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$"
                oninput="this.value=this.value.toUpperCase()">
        </div>
        <button type="submit" class="thd-btn">Enviar enlace de restablecimiento</button>
        <p style="margin-top:.5rem;">
            <a href="#" id="thd-cancel-lost">Volver al inicio de sesión</a>
        </p>
        <div class="thd-msg-lost" aria-live="polite" style="margin-top:10px;"></div>
    </form>
</div>

<script>
(function() {
    const wrap = document.currentScript.previousElementSibling?.closest('[data-thd-login]') || document
        .querySelector('[data-thd-login]');
    if (!wrap) return;

    const ajaxUrl = <?php echo wp_json_encode($ajax); ?>;
    const loginForm = wrap.querySelector('.thd-login-form');
    const lostForm = wrap.querySelector('.thd-lostpass-form');
    const msg = wrap.querySelector('.thd-msg');
    const msgLost = wrap.querySelector('.thd-msg-lost');
    const showPass = wrap.querySelector('#thd-showpass');
    const passInput = wrap.querySelector('#thd-pass');

    if (showPass && passInput) {
        showPass.addEventListener('change', () => {
            passInput.type = showPass.checked ? 'text' : 'password';
        });
    }

    wrap.querySelector('#thd-forgot-link')?.addEventListener('click', (e) => {
        e.preventDefault();
        loginForm.style.display = 'none';
        lostForm.style.display = 'block';
        msg.textContent = '';
    });
    wrap.querySelector('#thd-cancel-lost')?.addEventListener('click', (e) => {
        e.preventDefault();
        lostForm.style.display = 'none';
        loginForm.style.display = 'block';
        msgLost.textContent = '';
    });

    async function postForm(formEl) {
        const data = new FormData(formEl);
        const res = await fetch(ajaxUrl, {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        });
        const json = await res.json().catch(() => ({}));
        return json;
    }

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        msg.textContent = 'Validando…';
        try {
            const json = await postForm(loginForm);
            if (json && json.success) {
                msg.textContent = 'Accediendo…';
                const url = (json.data && json.data.redirect) ? json.data.redirect : window.location
                    .href;
                window.location.href = url;
            } else {
                msg.textContent = (json && json.data && json.data.message) ? json.data.message :
                    'Error al iniciar sesión.';
            }
        } catch (err) {
            msg.textContent = 'Error de red. Intenta de nuevo.';
        }
    });

    lostForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        msgLost.textContent = 'Procesando…';
        try {
            const json = await postForm(lostForm);
            if (json && json.success) {
                msgLost.textContent = (json.data && json.data.message) ? json.data.message :
                    'Si tu RFC existe, te llegará un correo.';
            } else {
                msgLost.textContent = 'No pudimos procesar tu solicitud. Inténtalo más tarde.';
            }
        } catch (err) {
            msgLost.textContent = 'Error de red. Intenta de nuevo.';
        }
    });
})();
</script>

<style>
.inicio-shortcode .inputs {
    margin-bottom: .75rem;
}

.inicio-shortcode input[type="text"],
.inicio-shortcode input[type="password"] {
    width: 100%;
    padding: .6rem .8rem;
    border: 1px solid #ccc;
    border-radius: .5rem;
}

.inicio-shortcode .thd-btn {
    display: inline-block;
    padding: .6rem 1rem;
    border-radius: .5rem;
    border: 0;
    cursor: pointer;
    background: #ef6c00;
    color: #fff;
    font-weight: 600;
}

.inicio-shortcode .thd-msg,
.inicio-shortcode .thd-msg-lost {
    color: #d32f2f;
    font-weight: 600;
}
</style>
<?php
        return ob_get_clean();
    }
}
add_shortcode('inicio_shortcode', 'shortcode_html');
