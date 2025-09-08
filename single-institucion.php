<?php
get_header('admin');

// ————————————————————————————————————————
// Validación de post actual
// ————————————————————————————————————————
$institucion_id = get_the_ID();
$institucion = null;

if ($institucion_id) {
    $institucion = get_post($institucion_id);
    if (!$institucion || $institucion->post_type !== 'institucion') {
        $institucion = null; // No válido
    }
}

// ————————————————————————————————————————
// Catálogo de estados
// ————————————————————————————————————————
$estados = [
    "Aguascalientes","Baja California","Baja California Sur","Campeche","Chiapas","Chihuahua",
    "Ciudad de México","Coahuila","Colima","Durango","Estado de México","Guanajuato","Guerrero",
    "Hidalgo","Jalisco","Michoacán","Morelos","Nayarit","Nuevo León","Oaxaca","Puebla","Querétaro",
    "Quintana Roo","San Luis Potosí","Sinaloa","Sonora","Tabasco","Tamaulipas","Tlaxcala","Veracruz",
    "Yucatán","Zacatecas"
];

// ————————————————————————————————————————
// Carga campos (ACF o meta)
// ————————————————————————————————————————
$info_general = $institucion ? get_field('informacion_general', $institucion_id) : [];
$info_contacto = $institucion ? get_field('informacion_de_contacto', $institucion_id) : [];
$necesidades = $institucion ? get_field('necesidades', $institucion_id) : [];
$presentacion_institucional = $institucion ? get_field('presentacion_institucional', $institucion_id) : [];
$archivos_requeridos = $institucion ? get_field('archivos_requeridos', $institucion_id) : [];

// ————————————————————————————————————————
// Permisos
// ————————————————————————————————————————
$puede_ver_estados = current_user_can('edit_posts');     // contributor+ ven estados
$puede_admin       = current_user_can('manage_options'); // admin autoriza/rechaza

// ————————————————————————————————————————
// Aliases / helpers de datos
// ————————————————————————————————————————
$IG_estado    = $info_general['estado'] ?? '';
$IG_municipio = $info_general['municipio'] ?? '';

$IC_entidad = $info_contacto['entidad_federativa'] ?? '';
$IC_ciudad  = $info_contacto['ciudad'] ?? '';
$tipo_institucion = $info_general['tipo_institucion'] ?? '';

$tipos_institucion = ['asociacion civil','fundacion','iap','otro'];

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
            echo '<p><a href="' . esc_url($url) . '" target="_blank">📎 Ver ' . esc_html($label) . '</a></p>';
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
            $color = match ($estado) {
                'capturado'  => '#f0ad4e',
                'autorizado' => '#5cb85c',
                'rechazado'  => '#d9534f',
                default      => '#999'
            };
            echo '<p><strong>Estado:</strong> <span style="color:' . esc_attr($color) . '; font-weight:bold;">' . esc_html(ucfirst($estado)) . '</span></p>';
        }
    }
}

if (!function_exists('mostrar_imagen_acf')) {
    function mostrar_imagen_acf($campo, $grupo, $label = '', $tamano = 'medium')
    {
        if (empty($grupo[$campo])) {
            return;
        }
        $imagen = $grupo[$campo];

        if (is_array($imagen) && isset($imagen['url'])) {
            $url = $imagen['sizes'][$tamano] ?? $imagen['url'];
            echo '<div class="imagen-acf">';
            if ($label) {
                echo '<p><strong>' . esc_html($label) . '</strong></p>';
            }
            echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($imagen['alt'] ?? '') . '" style="max-width:100%; height:auto;">';
            echo '</div>';
        } elseif (is_array($imagen) && isset($imagen[0])) {
            if ($label) {
                echo '<p><strong>' . esc_html($label) . '</strong></p>';
            }
            echo '<div class="galeria-acf" style="display: flex; flex-wrap: wrap; gap: 1rem;">';
            foreach ($imagen as $img) {
                $url = $img['sizes'][$tamano] ?? $img['url'];
                echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($img['alt'] ?? '') . '" style="max-width:150px; height:auto;">';
            }
            echo '</div>';
        }
    }
}

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

if (!function_exists('thd_badge_estado')) {
    function thd_badge_estado($estado)
    {
        $estado = strtolower((string)$estado);
        $color  = match ($estado) {
            'capturado'  => '#f0ad4e',
            'autorizado' => '#5cb85c',
            'rechazado'  => '#d9534f',
            default      => '#999',
        };
        return '<span style="display:inline-block;padding:.2rem .5rem;border-radius:.5rem;background:'
            . esc_attr($color) . '20;color:' . esc_attr($color) . ';font-weight:600">'
            . esc_html(ucfirst($estado)) . '</span>';
    }
}

// ————————————————————————————————————————
// Logo (presentación institucional)
// ————————————————————————————————————————
$logo = $presentacion_institucional['logo_de_la_institucion'] ?? null;
$logo_url = '';
if (is_array($logo) && isset($logo['url'])) {
    $logo_url = $logo['sizes']['medium'] ?? $logo['url'];
}

// ————————————————————————————————————————
// Handler de cambio de estado — SOLO subcampo por field_key
// (Evita borrar archivos del group)
// ————————————————————————————————————————
// === Helpers ===
if (!function_exists('thd_get_subfield_key')) {
    function thd_get_subfield_key($group_name, $sub_name, $post_id)
    {
        $group = get_field_object($group_name, $post_id);
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


$mensaje_estado = '';
if (
    isset($_POST['cambiar_estado_archivo'], $_POST['institucion_id']) &&
    (int)$_POST['institucion_id'] === (int)$institucion_id &&
    $puede_admin &&
    check_admin_referer('cambiar_estado_archivo_'.$institucion_id, 'estado_nonce')
) {
    $archivo_key     = sanitize_key($_POST['archivo_key'] ?? '');
    $nuevo_estado_in = strtolower(trim((string)($_POST['nuevo_estado'] ?? '')));

    // Normaliza entrada
    $map_norm = ['capturado' => 'capturado','autorizado' => 'autorizado','rechazado' => 'rechazado'];
    if (!$archivo_key || !isset($map_norm[$nuevo_estado_in])) {
        $mensaje_estado = 'Solicitud inválida.';
    } else {
        // Posibles nombres de subcampo ACF (name)
        $sub_names = [];
        if ($archivo_key === 'rfc_archivo') {
            $sub_names[] = 'estado_del_rfc';
        }
        $sub_names[] = 'estado_'.$archivo_key;      // nuestro preferido
        $sub_names[] = 'estado_del_'.$archivo_key;  // compat

        // 1) Encuentra key del subcampo (si existe)
        $sub_key = null;
        $found_name = null;
        foreach ($sub_names as $sn) {
            $k = thd_get_subfield_key('archivos_requeridos', $sn, $institucion_id);
            if ($k) {
                $sub_key = $k;
                $found_name = $sn;
                break;
            }
        }
        // 2) Determina meta key plano (storage real de ACF Group)
        if (!$found_name) {
            $found_name = $sub_names[0];
        }
        $meta_key = 'archivos_requeridos_'.$found_name;

        // 3) Determinar el value CORRECTO según choices
        $target_value = $nuevo_estado_in; // por defecto usamos minúsculas
        $choices = [];
        if ($sub_key) {
            $fo = get_field_object($sub_key, $institucion_id); // incluye choices si es select
            if (!empty($fo['choices']) && is_array($fo['choices'])) {
                $choices = $fo['choices']; // ['autorizado'=>'Autorizado', ...] o al revés
                // match por value o label en minúsculas
                foreach ($choices as $val => $label) {
                    if (strtolower((string)$val) === $nuevo_estado_in || strtolower((string)$label) === $nuevo_estado_in) {
                        $target_value = $val; // ACF guarda el VALUE
                        break;
                    }
                }
            } else {
                // si no hay choices, intenta capitalizar (por si guardan label)
                if (in_array($nuevo_estado_in, ['capturado','autorizado','rechazado'], true)) {
                    $target_value = $nuevo_estado_in; // mantenemos value minúscula
                }
            }
        }

        // 4) Guardar (intentamos por field_key; si no, por meta_key)
        $saved = null;
        if ($sub_key) {
            $saved = update_field($sub_key, $target_value, $institucion_id);
        }
        if ($saved === false || $saved === null) {
            // Fallback: escribe meta directamente
            update_post_meta($institucion_id, $meta_key, $target_value);
        }

        // 5) Verificar lectura (por key y por meta)
        $leido = null;
        if ($sub_key) {
            $leido = get_field($sub_key, $institucion_id);
            if (is_array($leido) && isset($leido['value'])) {
                $leido = $leido['value'];
            }
        }
        if ($leido === null || $leido === '') {
            $leido = get_post_meta($institucion_id, $meta_key, true);
        }

        if (strtolower((string)$leido) === strtolower((string)$target_value)) {
            $mensaje_estado = 'Estado actualizado a '.ucfirst($nuevo_estado_in).'.';
            // Refresca grupo para render
            $archivos_requeridos = get_field('archivos_requeridos', $institucion_id) ?: [];
        } else {
            $mensaje_estado = 'No se pudo actualizar el estado.';
        }
    }
}
?>

<div class="container">
    <div class="formulario">
        <div class="title-cont">
            <p class="title">Registro de Institución</p>
        </div>

        <p class="sub-title">
            Los campos marcados con asterisco (*) son obligatorios
            <span>El RFC registrado será su usuario de acceso</span>
        </p>

        <form action="" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('registrar_institucion', 'institucion_nonce'); ?>
            <?php if (!empty($institucion_id)): ?>
            <input type="hidden" name="institucion_id" value="<?php echo esc_attr($institucion_id); ?>">
            <?php endif; ?>

            <div class="title-cont">
                <p class="title">Información General</p>
            </div>

            <div class="row">
                <div class="input-cont">
                    <label>RFC*</label>
                    <input type="text" name="rfc" value="<?php echo esc_attr($info_general['rfc'] ?? ''); ?>" required>
                </div>

                <div class="input-cont">
                    <label>Tipo de Institución</label>
                    <select name="tipo_institucion">
                        <option value="">Seleccione una opción</option>
                        <?php foreach ($tipos_institucion as $tipo): ?>
                        <option value="<?php echo esc_attr($tipo); ?>" <?php selected($tipo_institucion, $tipo); ?>>
                            <?php echo esc_html(ucfirst($tipo)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-cont">
                    <label>Nombre Fiscal*</label>
                    <input type="text" name="nombre_fiscal"
                        value="<?php echo esc_attr($info_general['nombre_fiscal'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="input-cont half-w">
                    <label>Domicilio Fiscal</label>
                    <input type="text" name="domicilio_fiscal"
                        value="<?php echo esc_attr($info_general['domicilio_fiscal'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="input-cont">
                    <label>Estado</label>
                    <select name="estado" id="estado-select">
                        <option value="">Seleccione un estado</option>
                        <?php foreach ($estados as $estado): ?>
                        <option value="<?php echo esc_attr($estado); ?>" <?php selected($IG_estado, $estado); ?>>
                            <?php echo esc_html($estado); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-cont">
                    <label>Municipio</label>
                    <select name="municipio" id="municipio-select">
                        <option value="">Seleccione un estado</option>
                    </select>
                </div>
            </div>

            <div class="title-cont">
                <p class="title">Información de Contacto</p>
            </div>

            <div class="row">
                <div class="input-cont">
                    <label>Institución Subsidiaria</label>
                    <select name="institucion_subsidiaria">
                        <option value="">Seleccione una opción</option>
                        <option value="si">Sí</option>
                        <option value="no">No</option>
                    </select>
                </div>

                <div class="input-cont">
                    <label>Entidad Federativa*</label>
                    <select name="entidad_federativa" id="entidad-select" required>
                        <option value="">Seleccione una opción</option>
                        <?php foreach ($estados as $estado): ?>
                        <option value="<?php echo esc_attr($estado); ?>" <?php selected($IC_entidad, $estado); ?>>
                            <?php echo esc_html($estado); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-cont">
                    <label>Ciudad*</label>
                    <select name="ciudad" id="ciudad-select" required>
                        <option value="">Seleccione un estado</option>
                    </select>
                </div>

                <div class="input-cont">
                    <label>Sede</label>
                    <input type="text" name="sede">
                </div>
            </div>

            <div class="row">
                <div class="input-cont half-w">
                    <label>Nombre del Presidente/Director de la Institución</label>
                    <input type="text" name="nombre_del_presidente"
                        value="<?php echo esc_attr($info_contacto['datos_del_presidente']['nombre_del_presidente'] ?? ''); ?>">
                </div>

                <div class="input-cont">
                    <label>Correo Contacto</label>
                    <input type="text" name="correo_contacto"
                        value="<?php echo esc_attr($info_contacto['datos_del_presidente']['correo_contacto'] ?? ''); ?>">
                </div>

                <div class="input-cont">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono"
                        value="<?php echo esc_attr($info_contacto['datos_del_presidente']['telefono'] ?? ''); ?>">
                </div>
            </div>

            <div class="row">
                <div class="input-cont half-w">
                    <label>Persona de Contacto 1</label>
                    <input type="text" name="persona_contacto_1"
                        value="<?php echo esc_attr($info_contacto['grupo_persona_contacto_uno']['persona_contacto_1'] ?? ''); ?>">
                </div>

                <div class="input-cont">
                    <label>Correo Contacto 1</label>
                    <input type="email" name="correo_contacto_1"
                        value="<?php echo esc_attr($info_contacto['grupo_persona_contacto_uno']['correo_contacto_1'] ?? ''); ?>">
                </div>

                <div class="input-cont">
                    <label>Teléfono 1</label>
                    <input type="tel" name="telefono_1"
                        value="<?php echo esc_attr($info_contacto['grupo_persona_contacto_uno']['telefono_1'] ?? ''); ?>">
                </div>
            </div>

            <div class="row">
                <div class="input-cont half-w">
                    <label>Persona de Contacto 2</label>
                    <input type="text" name="persona_contacto_2"
                        value="<?php echo esc_attr($info_contacto['grupo_persona_contacto_2']['persona_contacto_2'] ?? ''); ?>">
                </div>

                <div class="input-cont">
                    <label>Correo Contacto 2</label>
                    <input type="email" name="correo_contacto_2"
                        value="<?php echo esc_attr($info_contacto['grupo_persona_contacto_2']['correo_contacto_2'] ?? ''); ?>">
                </div>

                <div class="input-cont">
                    <label>Teléfono 2</label>
                    <input type="tel" name="telefono_2"
                        value="<?php echo esc_attr($info_contacto['grupo_persona_contacto_2']['telefono_2'] ?? ''); ?>">
                </div>
            </div>

            <div class="row">
                <div class="input-cont half-w">
                    <label>Página Web de la Institución</label>
                    <input type="url" name="web"
                        value="<?php echo esc_attr(trim($info_contacto['redes_sociales']['web'] ?? '')); ?>">
                </div>

                <div class="input-cont half-w">
                    <label>Facebook</label>
                    <input type="url" name="facebook"
                        value="<?php echo esc_attr(trim($info_contacto['redes_sociales']['facebook'] ?? '')); ?>">
                </div>
            </div>

            <div class="row">
                <div class="input-cont half-w">
                    <label>Instagram</label>
                    <input type="url" name="instagram"
                        value="<?php echo esc_attr(trim($info_contacto['redes_sociales']['instagram'] ?? '')); ?>">
                </div>

                <div class="input-cont half-w">
                    <label>Tiktok</label>
                    <input type="url" name="tiktok"
                        value="<?php echo esc_attr(trim($info_contacto['redes_sociales']['tiktok'] ?? '')); ?>">
                </div>
            </div>

            <div class="row">
                <div class="input-cont">
                    <label>Tienda Adicional</label>
                    <select name="tienda_adicional">
                        <option value="">Seleccione una opción</option>
                        <option value="Sí">Sí</option>
                        <option value="No">No</option>
                    </select>
                </div>

                <div class="input-cont half-w">
                    <label>Dirección de la Tienda Adicional</label>
                    <input type="text" name="direccion_tienda_adicional">
                </div>
            </div>

            <div class="title-cont">
                <p class="title">Necesidades</p>
            </div>

            <div class="row">
                <div class="input-cont half-w">
                    <label>Principal necesidad que tiene la institución</label>
                    <textarea name="necesidad"><?php echo esc_attr($necesidades['necesidad'] ?? ''); ?></textarea>
                </div>

                <div class="input-cont">
                    <label>No. anual personas beneficiadas*</label>
                    <input type="text" name="numero_anual" required
                        value="<?php echo esc_attr(trim($necesidades['numero_anual'] ?? '')); ?>">>
                </div>
            </div>

            <div class="row">
                <div class="input-cont half-w">
                    <div class="custom-select-wrapper">
                        <label>Grupo social que atiende*</label>
                        <div class="custom-select" data-name="grupo_social">
                            <div class="selected-placeholder">
                                <?php
                                if (!empty($necesidades['grupo_social'])):
                                    foreach ($necesidades['grupo_social'] as $grupo):
                                        echo '<span class="tag" data-value="' . esc_attr($grupo) . '">' . esc_html(ucfirst($grupo)) . '<span class="remove-tag">×</span></span>';
                                    endforeach;
                                endif;
?>
                            </div>
                            <input type="text" class="custom-input-tag" placeholder="Escribe y presiona Enter" />
                            <div class="custom-options">
                                <?php
$seleccionados = array_map(fn ($item) => mb_strtolower(trim($item), 'UTF-8'), $necesidades['grupo_social'] ?? []);
$grupos = values_necesidades_groups();
foreach ($grupos as $valor):
    $valor_normalizado = mb_strtolower(trim($valor), 'UTF-8');
    $clase = in_array($valor_normalizado, $seleccionados) ? 'option disabled' : 'option';
    echo '<div class="' . esc_attr($clase) . '" data-value="' . esc_attr($valor) . '">' . esc_html(ucfirst($valor)) . '</div>';
endforeach;
?>
                            </div>
                        </div>
                        <?php
                        if (!empty($necesidades['grupo_social'])):
                            foreach ($necesidades['grupo_social'] as $grupo): ?>
                        <input type="hidden" name="grupo_social[]" required value="<?php echo esc_attr($grupo); ?>">
                        <?php endforeach;
                        endif;
?>
                    </div>
                </div>

                <div class="input-cont half-w">
                    <div class="custom-select-wrapper">
                        <label>Sector de apoyo*</label>
                        <div class="custom-select" data-name="sector_apoyo">
                            <div class="selected-placeholder">
                                <?php
        if (!empty($necesidades['sector_apoyo'])):
            foreach ($necesidades['sector_apoyo'] as $grupo):
                echo '<span class="tag" data-value="' . esc_attr($grupo) . '">' . esc_html(ucfirst($grupo)) . '<span class="remove-tag">×</span></span>';
            endforeach;
        endif;
?>
                            </div>

                            <input type="text" class="custom-input-tag" placeholder="Escribe y presiona Enter" />
                            <div class="custom-options">
                                <?php
$seleccionados = array_map(fn ($item) => mb_strtolower(trim($item), 'UTF-8'), $necesidades['sector_apoyo'] ?? []);
$grupos = values_necesidades_groups('sector_apoyo');
foreach ($grupos as $valor):
    $valor_normalizado = mb_strtolower(trim($valor), 'UTF-8');
    $clase = in_array($valor_normalizado, $seleccionados) ? 'option disabled' : 'option';
    echo '<div class="' . esc_attr($clase) . '" data-value="' . esc_attr($valor) . '">' . esc_html(ucfirst($valor)) . '</div>';
endforeach;
?>
                            </div>
                        </div>

                        <?php
                        if (!empty($necesidades['sector_apoyo'])):
                            foreach ($necesidades['sector_apoyo'] as $grupo): ?>
                        <input type="hidden" name="sector_apoyo[]" required value="<?php echo esc_attr($grupo); ?>">
                        <?php endforeach;
                        endif;
?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="input-cont full-w">
                    <label>Tipo de labor que realiza</label>
                    <textarea name="tipo_labor"
                        placeholder="Descripción de la labor que realiza la institución"><?php echo esc_attr($necesidades['tipo_labor'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="title-cont">
                <p class="title">Presentación Institucional</p>
            </div>

            <p>Favor de incluir archivo (foto, vídeo, documento) informativo sobre la institución</p>

            <div class="row">
                <div class="col">
                    <div class="input-cont half-w">
                        <label>Adjuntar Logo de la Institución</label>
                        <div class="input-file-image">
                            <?php if ($logo_url): ?>
                            <img class="preview" id="logoPreview" src="<?php echo esc_url($logo_url); ?>"
                                alt="Logo actual">
                            <?php else: ?>
                            <div class="icon">
                                <img src="<?php echo get_template_directory_uri() . '/img/icon-preview.png'; ?>" alt="">
                            </div>
                            <div class="text">Selecciona una imagen</div>
                            <img class="preview" id="logoPreview" style="display:none;">
                            <?php endif; ?>
                            <input type="file" name="logo_de_la_institucion" id="logoInput" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="input-cont half-w">
                        <label>Adjuntar Solicitud en Word o PDF</label>
                        <div class="input-file-drop" id="dropZoneCarta">
                            <div class="text">
                                <span id="fileNameCarta">Arrastra los archivos aquí</span>
                                <span class="icon">
                                    <img src="<?php echo get_template_directory_uri() . '/img/icon-clip.png'; ?>"
                                        alt="">
                                </span>
                            </div>
                            <input type="file" name="carta_solicitud" id="inputCarta" accept=".pdf,.doc,.docx" multiple>
                        </div>
                        <?php mostrar_archivo_existente('carta_solicitud', 'Carta Solicitud', $presentacion_institucional, false); ?>
                    </div>

                    <div class="input-cont half-w">
                        <label>Adjuntar Fotografías</label>
                        <div class="input-file-drop" id="dropZoneFotos">
                            <div class="text">
                                <span id="fileNameFotos">Arrastra los archivos aquí</span>
                                <span class="icon">
                                    <img src="<?php echo get_template_directory_uri() . '/img/icon-clip.png'; ?>"
                                        alt="">
                                </span>
                            </div>
                            <input type="file" name="fotografias" id="inputFotos" accept="image/*" multiple>
                        </div>
                        <?php mostrar_archivo_existente('fotografias', 'Fotografías', $presentacion_institucional, false); ?>
                    </div>
                </div>
            </div>

            <div class="title-cont">
                <p class="title">Archivos Requeridos</p>
            </div>

            <div class="row">
                <div class="input-cont half-w">
                    <label>Acta Constitutiva en PDF</label>
                    <div class="input-file-drop" id="dropZoneActaConstitutiva">
                        <div class="text">
                            <span id="fileNameActaConstitutiva">Arrastra los archivos aquí</span>
                            <span class="icon">
                                <img src="<?php echo get_template_directory_uri() . '/img/icon-clip.png'; ?>" alt="">
                            </span>
                        </div>
                        <input type="file" name="acta_constitutiva" id="inputActaConstitutiva" accept=".pdf">
                    </div>
                    <?php mostrar_archivo_existente('acta_constitutiva', 'Acta Constitutiva en PDF', $archivos_requeridos, $puede_ver_estados); ?>
                </div>

                <div class="input-cont half-w">
                    <label>Comprobante de Domicilio en PDF o foto</label>
                    <div class="input-file-drop" id="dropZoneCompDomicilio">
                        <div class="text">
                            <span id="fileNameCompDomicilio">Arrastra los archivos aquí</span>
                            <span class="icon">
                                <img src="<?php echo get_template_directory_uri() . '/img/icon-clip.png'; ?>" alt="">
                            </span>
                        </div>
                        <input type="file" name="comprobante_domicilio" id="inputCompDomicilio"
                            accept=".pdf,.png,.jpg,.jpeg">
                    </div>
                    <?php mostrar_archivo_existente('comprobante_domicilio', 'Comprobante de Domicilio', $archivos_requeridos, $puede_ver_estados); ?>
                </div>
            </div>

            <div class="row">
                <div class="input-cont half-w">
                    <label>Copia Recibo Dedudicble en PDF o foto</label>
                    <div class="input-file-drop" id="dropZoneDeducible">
                        <div class="text">
                            <span id="fileNameDeducible">Arrastra los archivos aquí</span>
                            <span class="icon">
                                <img src="<?php echo get_template_directory_uri() . '/img/icon-clip.png'; ?>" alt="">
                            </span>
                        </div>
                        <input type="file" name="deducible" id="inputDeducible" accept=".pdf,.png,.jpg,.jpeg">
                    </div>
                    <?php mostrar_archivo_existente('deducible', 'Recibo Deducible', $archivos_requeridos, $puede_ver_estados); ?>
                </div>

                <div class="input-cont half-w">
                    <label>Identificación del apoderado legal en PDF o foto</label>
                    <div class="input-file-drop" id="dropZoneApoderadoLegal">
                        <div class="text">
                            <span id="fileNameApoderadoLegal">Arrastra los archivos aquí</span>
                            <span class="icon">
                                <img src="<?php echo get_template_directory_uri() . '/img/icon-clip.png'; ?>" alt="">
                            </span>
                        </div>
                        <input type="file" name="apoderado_legal" id="inputApoderadoLegal"
                            accept=".pdf,.png,.jpg,.jpeg">
                    </div>
                    <?php mostrar_archivo_existente('apoderado_legal', 'Identificación del apoderado legal', $archivos_requeridos, $puede_ver_estados); ?>
                </div>
            </div>

            <div class="row">
                <div class="input-cont half-w">
                    <label>Solicitud de alta de institución en Excel</label>
                    <div class="input-file-drop" id="dropZoneInstitucionExcel">
                        <div class="text">
                            <span id="fileNameInstitucionExcel">Arrastra los archivos aquí</span>
                            <span class="icon">
                                <img src="<?php echo get_template_directory_uri() . '/img/icon-clip.png'; ?>" alt="">
                            </span>
                        </div>
                        <input type="file" name="institucion_excel" id="inputInstitucionExcel"
                            accept=".pdf,.png,.jpg,.jpeg">
                    </div>
                    <?php mostrar_archivo_existente('institucion_excel', 'Solicitud de alta de institución en Excel', $archivos_requeridos, $puede_ver_estados); ?>
                </div>

                <div class="input-cont half-w">
                    <label>Certificado de Donaciones en PDF</label>
                    <div class="input-file-drop" id="dropZoneCertificadoDonaciones">
                        <div class="text">
                            <span id="fileNameCertificadoDonaciones">Arrastra los archivos aquí</span>
                            <span class="icon">
                                <img src="<?php echo get_template_directory_uri() . '/img/icon-clip.png'; ?>" alt="">
                            </span>
                        </div>
                        <input type="file" name="certificado_donaciones" id="inputCertificadoDonaciones" accept=".pdf">
                    </div>
                    <?php mostrar_archivo_existente('certificado_donaciones', 'Certificado de Donaciones', $archivos_requeridos, $puede_ver_estados); ?>
                </div>
            </div>

            <div class="row">
                <div class="input-cont half-w unique">
                    <label>RFC en PDF</label>
                    <div class="input-file-drop" id="dropZoneRFC">
                        <div class="text">
                            <span id="fileNameRFC">Arrastra los archivos aquí</span>
                            <span class="icon">
                                <img src="<?php echo get_template_directory_uri() . '/img/icon-clip.png'; ?>" alt="">
                            </span>
                        </div>
                        <input type="file" name="rfc_archivo" id="inputRFC" accept=".pdf">
                    </div>
                    <?php mostrar_archivo_existente('rfc_archivo', 'RFC en PDF', $archivos_requeridos, $puede_ver_estados); ?>
                </div>
            </div>

            <button type="submit" name="submit_institucion">ALTA COMPLETA</button>
            <button class="recordatorio" name="recordatorio">ENVIAR RECORDATORIO</button>
        </form>

        <hr class="divider">
        <form action="">
            <div class="row">
                <div class="input-cont half-w">
                    <label for="">*Contraseña</label>
                    <input type="password" name="" id="">
                </div>
                <div class="input-cont half-w">
                    <label for="">*Confirmar Contraseña</label>
                    <input type="password" name="" id="">
                </div>
            </div>
        </form>

        <?php if (!empty($puede_ver_estados)): ?>
        <div class="title-cont">
            <p class="title">Tracking de status</p>
        </div>

        <?php if (!empty($mensaje_estado)): ?>
        <div class="notice" style="margin:10px 0;padding:8px;background:#f6f7f7;border:1px solid #ccd0d4;">
            <?php echo esc_html($mensaje_estado); ?>
        </div>
        <?php endif; ?>

        <table class="tracking-estatus">
            <thead>
                <tr>
                    <th></th>
                    <th>Fecha</th>
                    <th>Concepto</th>
                    <th>Status</th>
                    <?php if (!empty($puede_admin)): ?>
                    <th>Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $files = [
                    ['key' => 'acta_constitutiva',      'label' => 'Acta Constitutiva'],
                    ['key' => 'comprobante_domicilio',  'label' => 'Comprobante de Domicilio'],
                    ['key' => 'deducible',              'label' => 'Copia Recibo Deducible'],
                    ['key' => 'apoderado_legal',        'label' => 'Identificación del apoderado legal'],
                    ['key' => 'institucion_excel',      'label' => 'Solicitud de alta de institución'],
                    ['key' => 'certificado_donaciones', 'label' => 'Certificado de Donaciones'],
                    ['key' => 'rfc_archivo',            'label' => 'RFC'],
                ];

            foreach ($files as $f):
                $campo = $f['key'];
                $label = $f['label'];

                $valor = $archivos_requeridos[$campo] ?? '';
                $url   = (is_array($valor) && !empty($valor['url'])) ? $valor['url'] : (is_string($valor) ? $valor : '');
                $fecha = thd_fecha_archivo($valor);

                // Resolver estado (soporta ambas claves y RFC especial)
                $estado_val = $archivos_requeridos['estado_'.$campo] ?? $archivos_requeridos['estado_del_'.$campo] ?? null;
                if ($campo === 'rfc_archivo' && empty($estado_val)) {
                    $estado_val = $archivos_requeridos['estado_del_rfc'] ?? null;
                }
                if ($estado_val === null || $estado_val === '') {
                    $estado_val = 'Capturado';
                }
                ?>
                <tr>
                    <td><?php if ($url): ?><a href="<?php echo esc_url($url); ?>" target="_blank"
                            rel="noopener">📎</a><?php endif; ?></td>
                    <td><?php echo $fecha ? esc_html($fecha) : '—'; ?></td>
                    <td><?php echo esc_html($label); ?></td>
                    <td><?php echo thd_badge_estado($estado_val); ?></td>

                    <?php if (!empty($puede_admin)): ?>
                    <td>
                        <form method="post" style="display:inline">
                            <?php wp_nonce_field('cambiar_estado_archivo_'.$institucion_id, 'estado_nonce'); ?>
                            <input type="hidden" name="cambiar_estado_archivo" value="1">
                            <input type="hidden" name="institucion_id" value="<?php echo esc_attr($institucion_id); ?>">
                            <input type="hidden" name="archivo_key" value="<?php echo esc_attr($campo); ?>">
                            <input type="hidden" name="nuevo_estado" value="Autorizado">
                            <button type="submit" class="btn-status"
                                <?php echo $url ? '' : 'disabled'; ?>>Autorizar</button>
                        </form>
                        <form method="post" style="display:inline;margin-left:.4rem">
                            <?php wp_nonce_field('cambiar_estado_archivo_'.$institucion_id, 'estado_nonce'); ?>
                            <input type="hidden" name="cambiar_estado_archivo" value="1">
                            <input type="hidden" name="institucion_id" value="<?php echo esc_attr($institucion_id); ?>">
                            <input type="hidden" name="archivo_key" value="<?php echo esc_attr($campo); ?>">
                            <input type="hidden" name="nuevo_estado" value="Rechazado">
                            <button type="submit" class="btn-status">Rechazar</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const estadoSelect = document.getElementById('estado-select');
    const municipioSelect = document.getElementById('municipio-select');
    const entidadSelect = document.getElementById('entidad-select');
    const ciudadSelect = document.getElementById('ciudad-select');

    const basePath = window.location.origin + window.location.pathname.split('/').slice(0, 2).join('/');
    const jsonMunicipios = basePath + '/wp-content/themes/donaciones/js/municipios-estado.json';
    const jsonEstados = basePath + '/wp-content/themes/donaciones/js/estados.json';

    const estadoMap = {}; // slug => nombre real

    const valoresMunicipios = {
        ig_municipio: '<?php echo esc_js($IG_municipio); ?>',
        ig_estado: '<?php echo esc_js($IG_estado); ?>',
        ic_ciudad: '<?php echo esc_js($IC_ciudad); ?>',
        ic_entidad: '<?php echo esc_js($IC_entidad); ?>'
    };

    // Cargar estados y construir el mapa
    fetch(jsonEstados)
        .then(r => r.json())
        .then(estados => {
            estados.forEach(nombre => {
                estadoMap[nombre] = nombre;
                if (nombre == valoresMunicipios.ic_entidad) {
                    llenarMunicipios(nombre, ciudadSelect, valoresMunicipios.ic_ciudad);
                } else if (nombre == valoresMunicipios.ig_estado) {
                    llenarMunicipios(nombre, municipioSelect, valoresMunicipios.ig_municipio);
                }
            });
        })
        .catch(err => {
            console.error('Error al cargar estados:', err);
        });

    // Función para llenar municipios
    function llenarMunicipios(estadoSlug, municipioSelectEl, municipioActual) {
        const estadoNombre = estadoMap[estadoSlug];
        if (!estadoNombre) {
            municipioSelectEl.innerHTML = '<option value="">Seleccione un municipio</option>';
            return;
        }

        fetch(jsonMunicipios)
            .then(r => r.json())
            .then(data => {
                const municipios = data[estadoNombre] || [];
                municipioSelectEl.innerHTML = '<option value="">Seleccione un municipio</option>';
                let municipioExiste = false;
                municipios.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m;
                    opt.textContent = m;
                    if (m === municipioActual) {
                        opt.selected = true;
                        municipioExiste = true;
                    }
                    municipioSelectEl.appendChild(opt);
                });

                if (municipioActual && !municipioExiste) {
                    const opt = document.createElement('option');
                    opt.value = municipioActual;
                    opt.textContent = municipioActual;
                    opt.selected = true;
                    municipioSelectEl.appendChild(opt);
                }
            })
            .catch(err => {
                console.error('Error al cargar municipios:', err);
                municipioSelectEl.innerHTML = '<option value="">Error al cargar</option>';
            });
    }

    if (estadoSelect) {
        estadoSelect.addEventListener('change', function() {
            llenarMunicipios(this.value, municipioSelect, '');
        });
    }
    if (entidadSelect) {
        entidadSelect.addEventListener('change', function() {
            llenarMunicipios(this.value, ciudadSelect, '');
        });
    }

    // Manejo de archivos: escribe nombres en labels
    function handleFileChange(inputId, fileLabelId) {
        const input = document.getElementById(inputId);
        const label = document.getElementById(fileLabelId);

        if (input && label) {
            input.addEventListener('change', function(e) {
                const files = Array.from(e.target.files);
                if (files.length > 0) {
                    const max = 6;
                    const names = files.slice(0, max).map(f => f.name);
                    label.textContent = names.join(', ') + (files.length > max ?
                        ' (solo se mostrarán 6)' : '');
                    label.style.fontWeight = 'bold';
                    label.style.color = '#333';
                } else {
                    label.textContent = 'Arrastra los archivos aquí';
                    label.style.fontWeight = 'normal';
                    label.style.color = '#666';
                }
            });
        }
    }
    // Asignaciones
    handleFileChange('inputCarta', 'fileNameCarta');
    handleFileChange('inputFotos', 'fileNameFotos');
    handleFileChange('inputActaConstitutiva', 'fileNameActaConstitutiva');
    handleFileChange('inputCompDomicilio', 'fileNameCompDomicilio');
    handleFileChange('inputDeducible', 'fileNameDeducible');
    handleFileChange('inputApoderadoLegal', 'fileNameApoderadoLegal');
    handleFileChange('inputInstitucionExcel', 'fileNameInstitucionExcel');
    handleFileChange('inputCertificadoDonaciones', 'fileNameCertificadoDonaciones');
    handleFileChange('inputRFC', 'fileNameRFC');

    // Preview de logo
    const logoInput = document.getElementById('logoInput');
    const logoPreview = document.getElementById('logoPreview');
    if (logoInput && logoPreview) {
        logoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    logoPreview.src = evt.target.result;
                    logoPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Custom select chips
    document.querySelectorAll('.custom-select').forEach(select => {
        const placeholder = select.querySelector('.selected-placeholder');
        const options = select.querySelectorAll('.custom-options .option');

        options.forEach(option => {
            option.addEventListener('click', () => {
                if (option.classList.contains('disabled')) return;
                const value = option.dataset.value;

                const newTag = document.createElement('span');
                newTag.className = 'tag';
                newTag.dataset.value = value;
                newTag.innerHTML = `${value}<span class="remove-tag">×</span>`;
                placeholder.appendChild(newTag);

                option.classList.add('disabled');
                updateHiddenInput();
            });
        });

        placeholder.addEventListener('click', e => {
            if (e.target.classList.contains('remove-tag')) {
                const tag = e.target.closest('.tag');
                const value = tag.dataset.value;
                tag.remove();

                const matchingOption = select.querySelector(
                    `.custom-options .option[data-value="${CSS.escape(value)}"]`);
                if (matchingOption) matchingOption.classList.remove('disabled');

                updateHiddenInput();
            }
        });

        function updateHiddenInput() {
            const wrapper = select.closest('.custom-select-wrapper');
            wrapper.querySelectorAll(`input[name="${select.dataset.name}[]"]`).forEach(el => el
                .remove());
            const tags = placeholder.querySelectorAll('.tag');
            tags.forEach(tag => {
                const value = tag.dataset.value;
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = select.dataset.name + '[]';
                input.value = value;
                wrapper.appendChild(input);
            });
        }

        const inputTag = select.querySelector('.custom-input-tag');
        inputTag.addEventListener('input', function() {
            const query = inputTag.value.trim().toLowerCase();
            let tieneCoincidencias = false;
            options.forEach(opt => {
                const text = opt.textContent.toLowerCase();
                const visible = text.includes(query) && !opt.classList.contains(
                    'disabled');
                opt.style.display = visible ? 'block' : 'none';
                if (visible) tieneCoincidencias = true;
            });
            const optionsBox = select.querySelector('.custom-options');
            optionsBox.style.display = tieneCoincidencias ? 'block' : 'none';
        });

        inputTag.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const rawValue = inputTag.value.trim();
                if (!rawValue) return;

                const existe = Array.from(placeholder.querySelectorAll('.tag'))
                    .some(tag => tag.dataset.value.toLowerCase() === rawValue.toLowerCase());
                if (existe) {
                    inputTag.value = '';
                    return;
                }

                const tag = document.createElement('span');
                tag.className = 'tag';
                tag.dataset.value = rawValue;
                tag.innerHTML = `${rawValue}<span class="remove-tag">×</span>`;
                placeholder.appendChild(tag);

                inputTag.value = '';
                updateHiddenInput();
                options.forEach(opt => opt.style.display = 'none');
            }
        });

        inputTag.addEventListener('focus', function() {
            options.forEach(opt => {
                opt.style.display = opt.classList.contains('disabled') ? 'none' :
                    'block';
            });
            select.querySelector('.custom-options').style.display = 'block';
        });
    });
});
</script>
<?php get_footer('admin'); ?>