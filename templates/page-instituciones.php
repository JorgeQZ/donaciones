<?php
/**
 * Template Name: Page Instituciones
 **/
get_header('admin');

if (function_exists('thd_render_notice_from_query')) {
    echo thd_render_notice_from_query();
}

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
// Logo (presentación institucional)
// ————————————————————————————————————————
$logo = $presentacion_institucional['logo_de_la_institucion'] ?? null;
$logo_url = '';
if (is_array($logo) && isset($logo['url'])) {
    $logo_url = $logo['sizes']['medium'] ?? $logo['url'];
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

    $map_norm = ['capturado' => 'capturado','autorizado' => 'autorizado','rechazado' => 'rechazado'];
    if (!$archivo_key || !isset($map_norm[$nuevo_estado_in])) {
        $mensaje_estado = 'Solicitud inválida.';
    } else {
        // Posibles nombres de subcampo ACF (name)
        $sub_names = [];
        if ($archivo_key === 'rfc_archivo') {
            $sub_names[] = 'estado_del_rfc';
        }
        $sub_names[] = 'estado_'.$archivo_key;      // preferido
        $sub_names[] = 'estado_del_'.$archivo_key;  // compat

        // 1) key del subcampo y name encontrado
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
        if (!$found_name) {
            $found_name = $sub_names[0];
        }
        $meta_key = 'archivos_requeridos_'.$found_name;

        // 2) Determinar VALUE correcto según choices
        $target_value = $nuevo_estado_in;
        if ($sub_key) {
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

        // 3) Guardar (primero por field_key; si falla, por meta)
        $saved = null;
        if ($sub_key) {
            $saved = update_field($sub_key, $target_value, $institucion_id);
        }
        if ($saved === false || $saved === null) {
            update_post_meta($institucion_id, $meta_key, $target_value);
        }

        // 4) Verificar lectura
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

        <?php
        $pid = isset($_GET['pid']) ? (int) $_GET['pid'] : (get_the_ID() ?: 0);
if ($pid) {
    if ($creds = get_transient('thd_inst_pw_' . $pid)) {
        delete_transient('thd_inst_pw_' . $pid);
        echo '<div class="notice success" style="padding:12px;border:1px solid #46b450;background:#f6ffed;margin-bottom:16px;">'
        . '<strong>Usuario listo.</strong><br>'
        . 'Usuario (RFC): <code>'.esc_html($creds['rfc']).'</code><br>'
        . 'Contraseña: <code>'.esc_html($creds['password']).'</code><br>'
        . '<a href="'.esc_url(wp_login_url()).'">Ir a iniciar sesión</a>'
        . '</div>';
    }
    if ($err = get_post_meta($pid, '_institucion_user_error', true)) {
        delete_post_meta($pid, '_institucion_user_error');
        echo '<div class="notice error" style="padding:12px;border:1px solid #dc3232;background:#fff5f5;margin-bottom:16px;">'
        . '<strong>Error:</strong> '.esc_html($err).'</div>';
    }
}
?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field('registrar_institucion', 'institucion_nonce'); ?>
            <input type="hidden" name="action" value="registrar_institucion">
            <input type="hidden" name="submit_institucion" value="1">
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
                        <?php $val = $info_contacto['institucion_subsidiaria'] ?? ''; ?>
                        <option value="">Seleccione una opción</option>
                        <option value="si" <?php selected($val, 'si'); ?>>Sí</option>
                        <option value="no" <?php selected($val, 'no'); ?>>No</option>
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
                    <input type="text" name="sede" value="<?php echo esc_attr($info_contacto['sede'] ?? ''); ?>">
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
                    <input type="text" name="correo_contacto" <input type="email" name="correo_contacto"
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
                        <?php $val_t = $info_contacto['tienda_adicional'] ?? ''; ?>
                        <option value="">Seleccione una opción</option>
                        <option value="Sí" <?php selected($val_t, 'Sí'); ?>>Sí</option>
                        <option value="No" <?php selected($val_t, 'No'); ?>>No</option>
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
                    <textarea name="necesidad"><?php echo esc_textarea($necesidades['necesidad'] ?? ''); ?></textarea>
                </div>

                <div class="input-cont">
                    <label>No. anual personas beneficiadas*</label>
                    <input type="text" name="numero_anual" required
                        value="<?php echo esc_attr(trim($necesidades['numero_anual'] ?? '')); ?>">
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
                    <label>Copia Recibo Deducible en PDF o foto</label>
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
                        <input type="file" name="institucion_excel" id="inputInstitucionExcel" accept=".xlsx,.xls,.csv">

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
            <button type="button" class="recordatorio" name="recordatorio">ENVIAR RECORDATORIO</button>

        </form>

        <hr class="divider">
        <form action="">
            <div class="row">
                <div class="input-cont half-w">
                    <label for="">*Contraseña</label>
                    <input type="password" name="inst_password">
                </div>
                <div class="input-cont half-w">
                    <label for="">*Confirmar Contraseña</label>
                    <input type="password" name="inst_password_confirm">
                </div>
            </div>
        </form>
    </div>
</div>

<?php get_footer('admin'); ?>