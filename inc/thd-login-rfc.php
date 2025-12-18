<?php
if (!defined('ABSPATH')) exit;

if (!defined('THD_LOGIN_MAX_FAILS')) define('THD_LOGIN_MAX_FAILS', 5);
if (!defined('THD_LOGIN_WINDOW_MIN')) define('THD_LOGIN_WINDOW_MIN', 15);
if (!defined('THD_LOGIN_BLOCK_MIN')) define('THD_LOGIN_BLOCK_MIN', 15);

if (!function_exists('thd_get_client_ip')) {
    function thd_get_client_ip() {
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '0.0.0.0';
    }
}

if (!function_exists('thd_transient_remaining_secs')) {
    function thd_transient_remaining_secs($key) {
        $opt = get_option('_transient_timeout_' . $key);
        if (!$opt) return 0;
        $left = (int)$opt - time();
        return max(0, $left);
    }
}

if (!function_exists('thd_secure_key')) {
    function thd_secure_key($purpose, $value) {
        $data = strtolower(trim((string)$purpose . ':' . (string)$value));
        return hash_hmac('sha256', $data, wp_salt('auth'));
    }
}

if (!function_exists('thd_normalize_rfc')) {
    function thd_normalize_rfc($rfc_raw) {
        $r = strtoupper(wp_strip_all_tags((string)$rfc_raw));
        $r = preg_replace('/[^A-Z0-9Ñ&]/u', '', $r);
        return $r;
    }
}

if (!function_exists('thd_is_valid_rfc')) {
    function thd_is_valid_rfc($rfc) {
        $moral  = '/^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/u';
        $fisica = '/^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/u';
        return (bool)(preg_match($moral, $rfc) || preg_match($fisica, $rfc));
    }
}

if (!function_exists('thd_has_only_allowed_chars')) {
    function thd_has_only_allowed_chars($raw, $type) {
        $raw = (string)$raw;
        if (preg_match('/\s/u', $raw)) return false;
        if ($type === 'email') return (bool)preg_match('/^[A-Za-z0-9@._-]+$/', $raw);
        return (bool)preg_match('/^[A-Za-z0-9Ñ&]+$/u', $raw);
    }
}

if (!function_exists('thd_detect_login_type')) {
    function thd_detect_login_type($raw) {
        $v = trim(wp_strip_all_tags((string)$raw));
        if ($v === '') return false;

        if (strpos($v, '@') !== false) {
            if (!thd_has_only_allowed_chars($v, 'email')) return false;
            $email = strtolower($v);
            if (!is_email($email)) return false;
            return array('type' => 'email', 'value' => $email);
        }

        $rfc_raw = strtoupper($v);
        if (!thd_has_only_allowed_chars($rfc_raw, 'rfc')) return false;
        $rfc = thd_normalize_rfc($rfc_raw);
        if (!$rfc || !thd_is_valid_rfc($rfc)) return false;

        return array('type' => 'rfc', 'value' => $rfc);
    }
}

if (!function_exists('thd_get_user_by_identifier')) {
    function thd_get_user_by_identifier($type, $value) {
        if ($type === 'email') return get_user_by('email', $value);

        $user = get_user_by('login', $value);
        if ($user) return $user;

        $users = get_users(array(
            'meta_key'   => 'rfc',
            'meta_value' => $value,
            'number'     => 1,
            'fields'     => 'all',
        ));

        return !empty($users) ? $users[0] : false;
    }
}

if (!function_exists('thd_attempt_key_ip')) {
    function thd_attempt_key_ip() {
        return 'thd_login_ip_' . thd_secure_key('attempt_ip', thd_get_client_ip());
    }
}
if (!function_exists('thd_block_key_ip')) {
    function thd_block_key_ip() {
        return 'thd_block_ip_' . thd_secure_key('block_ip', thd_get_client_ip());
    }
}
if (!function_exists('thd_attempt_key_user')) {
    function thd_attempt_key_user($login_key) {
        return 'thd_login_user_' . thd_secure_key('attempt_user', $login_key);
    }
}
if (!function_exists('thd_block_key_user')) {
    function thd_block_key_user($login_key) {
        return 'thd_block_user_' . thd_secure_key('block_user', $login_key);
    }
}
if (!function_exists('thd_invalid_login_key')) {
    function thd_invalid_login_key() {
        return 'invalid';
    }
}

if (!function_exists('thd_is_blocked')) {
    function thd_is_blocked($login_key) {
        $ip_block_key  = thd_block_key_ip();
        $usr_block_key = thd_block_key_user($login_key);
        $ip_blocked    = (bool)get_transient($ip_block_key);
        $usr_blocked   = (bool)get_transient($usr_block_key);
        $ttl_ip        = thd_transient_remaining_secs($ip_block_key);
        $ttl_usr       = thd_transient_remaining_secs($usr_block_key);
        return array('blocked' => ($ip_blocked || $usr_blocked), 'ttl' => max($ttl_ip, $ttl_usr));
    }
}

if (!function_exists('thd_register_fail')) {
    function thd_register_fail($login_key) {
        $ip_cnt_key  = thd_attempt_key_ip();
        $usr_cnt_key = thd_attempt_key_user($login_key);

        $ip_cnt  = (int)get_transient($ip_cnt_key);
        $usr_cnt = (int)get_transient($usr_cnt_key);

        $ip_cnt++;
        $usr_cnt++;

        set_transient($ip_cnt_key, $ip_cnt, THD_LOGIN_WINDOW_MIN * 60);
        set_transient($usr_cnt_key, $usr_cnt, THD_LOGIN_WINDOW_MIN * 60);

        if ($ip_cnt >= THD_LOGIN_MAX_FAILS) {
            set_transient(thd_block_key_ip(), 1, THD_LOGIN_BLOCK_MIN * 60);
            delete_transient($ip_cnt_key);
        }
        if ($usr_cnt >= THD_LOGIN_MAX_FAILS) {
            set_transient(thd_block_key_user($login_key), 1, THD_LOGIN_BLOCK_MIN * 60);
            delete_transient($usr_cnt_key);
        }

        $left_ip  = max(0, THD_LOGIN_MAX_FAILS - $ip_cnt);
        $left_usr = max(0, THD_LOGIN_MAX_FAILS - $usr_cnt);

        return min($left_ip, $left_usr);
    }
}

if (!function_exists('thd_clear_attempts')) {
    function thd_clear_attempts($login_key) {
        delete_transient(thd_attempt_key_ip());
        delete_transient(thd_attempt_key_user($login_key));
        delete_transient(thd_block_key_ip());
        delete_transient(thd_block_key_user($login_key));
    }
}

add_action('wp_ajax_nopriv_thd_subscriber_login', 'thd_subscriber_login_ajax');
add_action('wp_ajax_thd_subscriber_login', 'thd_subscriber_login_ajax');

if (!function_exists('thd_subscriber_login_ajax')) {
    function thd_subscriber_login_ajax() {
        check_ajax_referer('thd_login_nonce', 'security');

        $raw_login   = isset($_POST['rfc']) ? (string)$_POST['rfc'] : '';
        $password    = isset($_POST['password']) ? (string)$_POST['password'] : '';
        $remember    = !empty($_POST['remember']);

        $login = thd_detect_login_type($raw_login);
        $login_key = $login ? ($login['type'] . ':' . $login['value']) : ('invalid:' . thd_invalid_login_key());

        $blk = thd_is_blocked($login_key);
        if ($blk['blocked']) {
            $mins = ceil($blk['ttl'] / 60);
            wp_send_json_error(array('message' => "Demasiados intentos. Intenta de nuevo en ~{$mins} min."));
        }

        if (!$login || $password === '') {
            $left = thd_register_fail($login_key);
            $msg  = 'Usuario o contraseña inválidos.';
            if ($left > 0) $msg .= " Te quedan {$left} intentos.";
            wp_send_json_error(array('message' => $msg, 'left' => $left));
        }

        $user = thd_get_user_by_identifier($login['type'], $login['value']);

        $creds = array(
            'user_login'    => $user ? $user->user_login : $login['value'],
            'user_password' => $password,
            'remember'      => $remember,
        );

        $signon = wp_signon($creds, is_ssl());
        if (is_wp_error($signon)) {
            $left = thd_register_fail($login_key);
            $msg  = 'Usuario o contraseña inválidos.';
            if ($left > 0) $msg .= " Te quedan {$left} intentos.";
            wp_send_json_error(array('message' => $msg, 'left' => $left));
        }

        $roles = (array)$signon->roles;
        if (!array_intersect(array('subscriber', 'administrator'), $roles)) {
            wp_logout();
            $left = thd_register_fail($login_key);
            wp_send_json_error(array('message' => 'No autorizado para este acceso.', 'left' => $left));
        }

        thd_clear_attempts($login_key);

        $consulta_page = get_page_by_path('consulta-de-instituciones');
        $consulta_url  = $consulta_page ? get_permalink($consulta_page) : home_url('/consulta-de-instituciones/');
        $safe_redirect = wp_validate_redirect($consulta_url, $consulta_url);

        wp_send_json_success(array('message' => '¡Bienvenido!', 'redirect' => $safe_redirect));
    }
}

add_action('wp_ajax_nopriv_thd_subscriber_lostpass', 'thd_subscriber_lostpass_ajax');
add_action('wp_ajax_thd_subscriber_lostpass', 'thd_subscriber_lostpass_ajax');

if (!function_exists('thd_subscriber_lostpass_ajax')) {
    function thd_subscriber_lostpass_ajax() {
        check_ajax_referer('thd_login_nonce', 'security');

        $raw_login = isset($_POST['rfc']) ? (string)$_POST['rfc'] : '';
        $login     = thd_detect_login_type($raw_login);

        if ($login) {
            $user = thd_get_user_by_identifier($login['type'], $login['value']);
            if ($user) retrieve_password($user->user_login);
        }

        wp_send_json_success(array(
            'message' => 'Si tu RFC o correo está registrado, te enviamos un enlace para restablecer la contraseña.'
        ));
    }
}

if (!function_exists('shortcode_html')) {
    function shortcode_html($atts = array()) {
        $atts = shortcode_atts(array('redirect' => ''), $atts, 'inicio_shortcode');

        $redirect_to = $atts['redirect'] ? esc_url(home_url($atts['redirect'])) : esc_url(add_query_arg(array()));
        $nonce       = wp_create_nonce('thd_login_nonce');
        $ajax        = admin_url('admin-ajax.php');

        $consulta_page = get_page_by_path('consulta-de-instituciones');
        $consulta_url  = $consulta_page ? get_permalink($consulta_page) : home_url('/consulta-de-instituciones/');

        ob_start();

        if (is_user_logged_in()) : ?>
            <div class="inicio-shortcode" data-thd-login>
                <div class="thd-logged-box">
                    <p style="margin-bottom:.75rem;">Ya iniciaste sesión.</p>
                    <a class="thd-btn" href="<?php echo esc_url($consulta_url); ?>">Ver mis instituciones</a>
                </div>
            </div>
        <?php else : ?>
            <div class="inicio-shortcode" data-thd-login>
                <form class="thd-login-form" method="post" autocomplete="off" action="<?php echo esc_url($ajax); ?>" novalidate>
                    <input type="hidden" name="action" value="thd_subscriber_login">
                    <input type="hidden" name="security" value="<?php echo esc_attr($nonce); ?>">
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

                    <div class="inputs">
                        <label class="input-1" for="thd-rfc">*RFC o correo</label>
                        <input id="thd-rfc" name="rfc" type="text" placeholder="RFC o correo electrónico" inputmode="latin" required maxlength="80" pattern="^[A-Za-z0-9@._-]+$" autocomplete="username" autocorrect="off" autocapitalize="off" spellcheck="false">
                        <small class="thd-input-error" aria-live="polite" style="display:none;margin-top:6px;"></small>
                    </div>

                    <div class="inputs dos">
                        <label class="input-2" for="thd-pass">Contraseña</label>

                        <div class="thd-passwrap">
                            <input id="thd-pass" name="password" type="password" required autocomplete="current-password">
                            <button type="button" class="thd-eye" aria-label="Mostrar contraseña" aria-controls="thd-pass" aria-pressed="false">
                                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                    <path d="M12 5c-5 0-9 4.5-10 7 1 2.5 5 7 10 7s9-4.5 10-7c-1-2.5-5-7-10-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-2.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="rem-lost">
                        <label style="display:flex;gap:.4rem;align-items:center;margin:.5rem 0;">
                            <input type="checkbox" name="remember" value="1"> Recordarme
                        </label>
                        <a href="#" id="thd-forgot-link">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="thd-btn">Ingresar</button>
                    <div class="thd-msg" aria-live="polite" style="margin-top:10px;"></div>
                </form>

                <form class="thd-lostpass-form" method="post" action="<?php echo esc_url($ajax); ?>" style="display:none;" novalidate>
                    <input type="hidden" name="action" value="thd_subscriber_lostpass">
                    <input type="hidden" name="security" value="<?php echo esc_attr($nonce); ?>">

                    <div class="inputs">
                        <label for="thd-rfc-lost">RFC o correo</label>
                        <input id="thd-rfc-lost" name="rfc" type="text" placeholder="RFC o correo electrónico" inputmode="latin" required maxlength="80" pattern="^[A-Za-z0-9@._-]+$" autocomplete="username" autocorrect="off" autocapitalize="off" spellcheck="false">
                        <small class="thd-input-error" aria-live="polite" style="display:none;margin-top:6px;"></small>
                    </div>

                    <button type="submit" class="thd-btn">Enviar enlace de restablecimiento</button>
                    <p style="margin-top:.5rem;">
                        <a href="#" id="thd-cancel-lost">Volver al inicio de sesión</a>
                    </p>

                    <div class="thd-msg-lost" aria-live="polite" style="margin-top:10px;"></div>
                </form>
            </div>
        <?php endif; ?>

        <script>
        (function() {
            const wrap = document.currentScript.previousElementSibling?.closest('[data-thd-login]') || document.querySelector('[data-thd-login]');
            if (!wrap) return;

            const ajaxUrl = <?php echo wp_json_encode($ajax); ?>;

            const loginForm = wrap.querySelector('.thd-login-form');
            const lostForm  = wrap.querySelector('.thd-lostpass-form');

            const msg     = wrap.querySelector('.thd-msg');
            const msgLost = wrap.querySelector('.thd-msg-lost');

            const passInput = wrap.querySelector('#thd-pass');
            const eyeBtn    = wrap.querySelector('.thd-eye');

            function showError(inputEl, text) {
                const errEl = inputEl?.closest('.inputs')?.querySelector('.thd-input-error') || inputEl?.nextElementSibling;
                if (errEl) {
                    errEl.textContent = text || '';
                    errEl.style.display = text ? 'block' : 'none';
                }
                if (inputEl) inputEl.classList.toggle('has-error', !!text);
            }

            function clearError(inputEl) {
                showError(inputEl, '');
            }

            function sanitizeAndValidate(inputEl) {
                if (!inputEl) return false;

                const original = inputEl.value || '';
                const sanitized = original.replace(/[^A-Za-z0-9Ñ&@._-]/g, '');

                if (sanitized !== original) {
                    inputEl.value = sanitized;
                    showError(inputEl, 'Solo se permiten letras, números y los caracteres - _ @ .');
                    return false;
                }

                const val = (inputEl.value || '').trim();
                if (!val) {
                    showError(inputEl, 'Este campo es obligatorio.');
                    return false;
                }

                const isEmail = val.includes('@');

                if (isEmail) {
                    inputEl.value = val.toLowerCase();
                    if (!/^[^@]+@[^@]+\.[^@]+$/.test(inputEl.value)) {
                        showError(inputEl, 'Ingresa un correo electrónico válido (ej. usuario@dominio.com).');
                        return false;
                    }
                    clearError(inputEl);
                    return true;
                }

                inputEl.value = val.toUpperCase();

                if (inputEl.value.length < 12) {
                    showError(inputEl, 'El RFC debe tener al menos 12 caracteres.');
                    return false;
                }
                if (inputEl.value.length > 13) {
                    showError(inputEl, 'El RFC no debe exceder 13 caracteres.');
                    return false;
                }

                clearError(inputEl);
                return true;
            }

            function bindIdentifier(inputEl) {
                if (!inputEl) return;
                inputEl.addEventListener('input', () => sanitizeAndValidate(inputEl));
                inputEl.addEventListener('blur', () => sanitizeAndValidate(inputEl));
            }

            bindIdentifier(wrap.querySelector('#thd-rfc'));
            bindIdentifier(wrap.querySelector('#thd-rfc-lost'));

            if (eyeBtn && passInput) {
                const svgEye = `<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 5c-5 0-9 4.5-10 7 1 2.5 5 7 10 7s9-4.5 10-7c-1-2.5-5-7-10-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-2.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/></svg>`;
                const svgEyeOff = `<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M2 3.3 3.3 2 22 20.7 20.7 22l-3.2-3.2C15.6 19.5 13.9 20 12 20 7 20 3 15.5 2 13c.5-1.2 1.7-2.9 3.3-4.4L2 3.3zm7.8 7.8a2.5 2.5 0 0 0 3.1 3.1l-3.1-3.1zM12 5c5 0 9 4.5 10 7-.3.7-1 1.8-2 2.9l-1.4-1.4C19.5 12.5 16.3 9 12 9c-.7 0-1.4.1-2 .3L8.4 7.7C9.5 5.9 10.7 5 12 5z"/></svg>`;
                function setPasswordVisibility(show) {
                    passInput.type = show ? 'text' : 'password';
                    eyeBtn.setAttribute('aria-pressed', show ? 'true' : 'false');
                    eyeBtn.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
                    eyeBtn.innerHTML = show ? svgEyeOff : svgEye;
                }
                setPasswordVisibility(false);
                eyeBtn.addEventListener('click', () => setPasswordVisibility(passInput.type === 'password'));
            }

            if (loginForm) {
                wrap.querySelector('#thd-forgot-link')?.addEventListener('click', (e) => {
                    e.preventDefault();
                    loginForm.style.display = 'none';
                    if (lostForm) lostForm.style.display = 'block';
                    if (msg) msg.textContent = '';
                });
            }

            if (lostForm) {
                wrap.querySelector('#thd-cancel-lost')?.addEventListener('click', (e) => {
                    e.preventDefault();
                    lostForm.style.display = 'none';
                    if (loginForm) loginForm.style.display = 'block';
                    if (msgLost) msgLost.textContent = '';
                });
            }

            async function postForm(formEl) {
                const data = new FormData(formEl);
                const res = await fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' });
                const json = await res.json().catch(() => ({}));
                return json;
            }

            if (loginForm) {
                loginForm.addEventListener('submit', async (e) => {
                    e.preventDefault();

                    const idInput = loginForm.querySelector('#thd-rfc');
                    const okId = sanitizeAndValidate(idInput);

                    if (!okId) return;

                    if (msg) msg.textContent = 'Validando…';

                    try {
                        const json = await postForm(loginForm);
                        if (json && json.success) {
                            if (msg) msg.textContent = 'Accediendo…';
                            const url = (json.data && json.data.redirect) ? json.data.redirect : window.location.href;
                            window.location.href = url;
                        } else {
                            const m = (json && json.data && json.data.message) ? json.data.message : 'Error al iniciar sesión.';
                            if (msg) msg.textContent = m;
                        }
                    } catch (err) {
                        if (msg) msg.textContent = 'Error de red. Intenta de nuevo.';
                    }
                });
            }

            if (lostForm) {
                lostForm.addEventListener('submit', async (e) => {
                    e.preventDefault();

                    const idInput = lostForm.querySelector('#thd-rfc-lost');
                    const okId = sanitizeAndValidate(idInput);

                    if (!okId) return;

                    if (msgLost) msgLost.textContent = 'Procesando…';

                    try {
                        const json = await postForm(lostForm);
                        if (json && json.success) {
                            const m = (json.data && json.data.message) ? json.data.message : 'Si existe, te llegará un correo.';
                            if (msgLost) msgLost.textContent = m;
                        } else {
                            if (msgLost) msgLost.textContent = 'No pudimos procesar tu solicitud. Inténtalo más tarde.';
                        }
                    } catch (err) {
                        if (msgLost) msgLost.textContent = 'Error de red. Intenta de nuevo.';
                    }
                });
            }
        })();
        </script>

        <?php
        return ob_get_clean();
    }
}
add_shortcode('inicio_shortcode', 'shortcode_html');
