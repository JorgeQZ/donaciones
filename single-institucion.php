<?php
/**
 * Template Name: Instituciones
 **/

get_header('admin');

// Verifica si hay ID en el query
$institucion_id = get_the_ID();
$institucion = null;

if ($institucion_id) {
    $institucion = get_post($institucion_id);
    if (!$institucion || $institucion->post_type !== 'institucion') {
        $institucion = null; // No válido
    }
}

/**
 * variables de estados
 * Se usa para el select de estados
 * y para el select de entidad federativa
 */
$estados = [
    "Aguascalientes",
    "Baja California",
    "Baja California Sur",
    "Campeche",
    "Chiapas",
    "Chihuahua",
    "Ciudad de México",
    "Coahuila",
    "Colima",
    "Durango",
    "Estado de México",
    "Guanajuato",
    "Guerrero",
    "Hidalgo",
    "Jalisco",
    "Michoacán",
    "Morelos",
    "Nayarit",
    "Nuevo León",
    "Oaxaca",
    "Puebla",
    "Querétaro",
    "Quintana Roo",
    "San Luis Potosí",
    "Sinaloa",
    "Sonora",
    "Tabasco",
    "Tamaulipas",
    "Tlaxcala",
    "Veracruz",
    "Yucatán",
    "Zacatecas"
];

// Carga campos (ACF o meta)
$info_general = $institucion ? get_field('informacion_general', $institucion_id) : [];
$info_contacto = $institucion ? get_field('informacion_de_contacto', $institucion_id) : [];
$necesidades = $institucion ? get_field('necesidades', $institucion_id) : [];
$presentacion_institucional = $institucion ? get_field('presentacion_institucional', $institucion_id) : [];
$archivos_requeridos = $institucion ? get_field('archivos_requeridos', $institucion_id) : [];

echo '<pre>';
print_r($info_general);
print_r($info_contacto);
print_r($necesidades);
print_r($presentacion_institucional);
print_r($archivos_requeridos);
echo '</pre>';

function slugify_estado($string)
{
    $string = strtolower($string);
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string); // quita acentos
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string); // elimina símbolos raros
    $string = preg_replace('/[\s]+/', '-', $string); // espacios → guiones
    return trim($string, '-');
}
function mostrar_archivo_existente($campo, $label, $grupo, $mostrar_estado = true)
{
    if (empty($grupo[$campo])) {
        return;
    }

    $archivo = $grupo[$campo];
    $url = '';

    // Detecta si es un array (objeto ACF de tipo archivo) o solo una URL
    if (is_array($archivo) && isset($archivo['url'])) {
        $url = $archivo['url'];
    } elseif (is_string($archivo)) {
        $url = $archivo;
    }

    if ($url) {
        echo '<p><a href="' . esc_url($url) . '" target="_blank">📎 Ver ' . esc_html($label) . '</a></p>';
    }

    // Muestra estado del archivo (si aplica)
    if ($mostrar_estado) {
        $estado_key = 'estado_del_' . $campo;
        if (!empty($grupo[$estado_key])) {
            $estado = strtolower($grupo[$estado_key]);
            $color = match ($estado) {
                'capturado' => '#f0ad4e',
                'autorizado' => '#5cb85c',
                'rechazado' => '#d9534f',
                default => '#999'
            };
            echo '<p><strong>Estado:</strong> <span style="color:' . esc_attr($color) . '; font-weight:bold;">' . ucfirst($estado) . '</span></p>';
        }
    }
}

function mostrar_imagen_acf($campo, $grupo, $label = '', $tamano = 'medium')
{
    if (empty($grupo[$campo])) {
        return;
    }

    $imagen = $grupo[$campo];

    // echo $imagen . '' . $label . '' . $tamano;

    // Si es un solo objeto (una imagen)
    if (is_array($imagen) && isset($imagen['url'])) {
        $url = $imagen['sizes'][$tamano] ?? $imagen['url'];
        echo '<div class="imagen-acf">';
        if ($label) {
            echo '<p><strong>' . esc_html($label) . '</strong></p>';
        }
        echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($imagen['alt'] ?? '') . '" style="max-width:100%; height:auto;">';
        echo '</div>';
    }

    // Si es una galería o lista de imágenes (repeater o múltiple)
    elseif (is_array($imagen) && isset($imagen[0])) {
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

$logo = $presentacion_institucional['logo_de_la_institucion'] ?? null;
$logo_url = '';

if (is_array($logo) && isset($logo['url'])) {
    $logo_url = $logo['sizes']['medium'] ?? $logo['url'];
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
                        <option value="asociacion civil">Asociación Civil</option>
                        <option value="fundacion">Fundación</option>
                        <option value="iap">IAP</option>
                        <option value="otro">Otro</option>
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
                    <input type="text" name="domicilio_fiscal" value=" " required>

                </div>
            </div>
            <div class="row">
                <div class="input-cont">
                    <label>Estado</label>
                    <select name="estado" id="estado-select">
                        <option value="">Seleccione un estado</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?php echo esc_attr(slugify_estado($estado)); ?>">
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
                            <option value="<?php echo esc_attr(slugify_estado($estado)); ?>">
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

                <div class="input-cont  half-w">
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
                    <textarea name="necesidad"></textarea>
                </div>


                <div class="input-cont">
                    <label>No. anual personas beneficiadas*</label>
                    <input type="text" name="numero_anual" required>
                </div>
            </div>

            <div class="row">
                <div class="input-cont half-w">
                    <div class="custom-select-wrapper">
                        <label>Grupo social que atiende*</label>
                        <div class="custom-select" data-name="grupo_social">
                            <div class="selected-placeholder">Seleccione una o más opciones</div>
                            <div class="custom-options hidden">
                                <div data-value="comunidad">Comunidad</div>
                                <div data-value="inclusion">Inclusión</div>
                                <div data-value="jovenes">Jóvenes</div>
                                <div data-value="mujeres">Mujeres</div>
                                <div data-value="ninos">Niños(as)</div>
                            </div>
                        </div>
                        <input type="hidden" name="grupo_social" required>
                    </div>
                </div>
                <div class="input-cont half-w">
                    <div class="custom-select-wrapper">
                        <label>Sector de apoyo*</label>
                        <div class="custom-select" data-name="sector_apoyo">
                            <div class="selected-placeholder">Seleccione una o más opciones</div>
                            <div class="custom-options hidden">
                                <div data-value="comunidad">Comunidad</div>
                                <div data-value="inclusion">Inclusión</div>
                                <div data-value="jovenes">Jóvenes</div>
                                <div data-value="mujeres">Mujeres</div>
                                <div data-value="ninos">Niños(as)</div>
                            </div>
                        </div>
                        <input type="hidden" name="sector_apoyo" required>
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
                        <?php mostrar_archivo_existente('carta_solicitud', 'Carta Solicitud', $presentacion_institucional, false);
                        ?>

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
                        <?php mostrar_archivo_existente('fotografias', 'Fotografías', $presentacion_institucional, false);
                        ?>
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
                    <?php mostrar_archivo_existente('acta_constitutiva', 'Acta Constitutiva en PDF', $archivos_requeridos); ?>

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
                    <?php
                    mostrar_archivo_existente('comprobante_domicilio', 'Comprobante de Domicilio', $archivos_requeridos);
                    ?>
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
                    <?php
                    mostrar_archivo_existente('deducible', 'Recibo Deducible', $archivos_requeridos);
                    ?>
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
                    <?php
                    mostrar_archivo_existente('apoderado_legal', 'Identificación del apoderado legal', $archivos_requeridos);
                    ?>
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
                    <?php mostrar_archivo_existente('institucion_excel', 'Solicitud de alta de institución en Excel', $archivos_requeridos); ?>
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
                    <?php
                    mostrar_archivo_existente('certificado_donaciones', 'Certificado de Donaciones', $archivos_requeridos);
                    ?>
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
                    <?php mostrar_archivo_existente('rfc_archivo', 'RFC en PDF', $archivos_requeridos); ?>

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

    </div>
    <script>
        function slugify(text) {
            return text.normalize("NFD").replace(/[\u0300-\u036f]/g, "")
                .toLowerCase().replace(/\s+/g, '-');
        }
        document.addEventListener('DOMContentLoaded', function () {

            /**
             * Manejo de select de estado y municipio
             */
            const estadoSelect = document.getElementById('estado-select');
            const municipioSelect = document.getElementById('municipio-select');

            const entidadSelect = document.getElementById('entidad-select');
            const ciudadSelect = document.getElementById('ciudad-select');

            // Construye la URL del archivo JSON relativo a la ruta base
            const basePath = window.location.origin + window.location.pathname.split('/').slice(0, 2).join('/');
            const jsonUrl = basePath + '/wp-content/themes/donaciones/js/municipios-estado.json';


            // Lista original de estados con formato exacto como en el JSON
            const estadosOriginal = [
                "Aguascalientes", "Baja California", "Baja California Sur", "Campeche", "Chiapas",
                "Chihuahua", "Ciudad de México", "Coahuila", "Colima", "Durango", "Estado de México",
                "Guanajuato", "Guerrero", "Hidalgo", "Jalisco", "Michoacán", "Morelos", "Nayarit",
                "Nuevo León", "Oaxaca", "Puebla", "Querétaro", "Quintana Roo", "San Luis Potosí",
                "Sinaloa", "Sonora", "Tabasco", "Tamaulipas", "Tlaxcala", "Veracruz", "Yucatán", "Zacatecas"
            ];



            // Mapeo para traducir el value del select al nombre real del JSON
            const estadoMap = {};

            // Llena el mapa con los nombres originales de los estados
            estadosOriginal.forEach(nombre => {
                estadoMap[slugify(nombre)] = nombre;
            });

            // Maneja el cambio en el select de estado
            function populateMunicipios(estadoSelectEl, municipioSelectEl) {
                const estadoSlug = estadoSelectEl.value;
                const estadoNombre = estadoMap[estadoSlug];

                if (!estadoNombre) {
                    municipioSelectEl.innerHTML = '<option value="">Seleccione un municipio</option>';
                    return;
                }

                fetch(jsonUrl)
                    .then(r => r.json())
                    .then(data => {
                        const municipios = data[estadoNombre] || [];
                        municipioSelectEl.innerHTML = '<option value="">Seleccione un municipio</option>';
                        municipios.forEach(m => {
                            const opt = document.createElement('option');
                            opt.value = opt.textContent = m;
                            municipioSelectEl.appendChild(opt);
                        });
                    })
                    .catch(err => {
                        console.error('Error al cargar municipios:', err);
                        municipioSelectEl.innerHTML = '<option value="">Error al cargar</option>';
                    });
            }

            // Asigna el evento de cambio a los selects
            estadoSelect.addEventListener('change', () => populateMunicipios(estadoSelect, municipioSelect));
            entidadSelect.addEventListener('change', () => populateMunicipios(entidadSelect, ciudadSelect));

            if (estadoSelect.value) {
                populateMunicipios(estadoSelect, municipioSelect);
            }
            if (entidadSelect.value) {
                populateMunicipios(entidadSelect, ciudadSelect);
            }

            // 🔽 Manejo de archivos arrastrados
            function handleFileChange(inputId, fileLabelId) {
                const input = document.getElementById(inputId);
                const label = document.getElementById(fileLabelId);

                if (input && label) {
                    input.addEventListener('change', function (e) {
                        const files = Array.from(e.target.files);
                        if (files.length > 0) {
                            const max = 6;
                            const names = files.slice(0, max).map(f => f.name);
                            label.textContent = names.join(', ') + (files.length > max ? ' (solo se mostrarán 6)' : '');
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

            // Asigna el manejo de archivos a los inputs
            handleFileChange('inputCarta', 'fileNameCarta');
            handleFileChange('inputFotos', 'fileNameFotos');
            handleFileChange('inputActaConstitutiva', 'fileNameActaConstitutiva');
            handleFileChange('inputCompDomicilio', 'fileNameCompDomicilio');
            handleFileChange('inputDeducible', 'fileNameDeducible');
            handleFileChange('inputApoderadoLegal', 'fileNameApoderadoLegal');
            handleFileChange('inputInstitucionExcel', 'fileNameInstitucionExcel');
            handleFileChange('inputCertificadoDonaciones', 'fileNameCertificadoDonaciones');
            handleFileChange('inputRFC', 'fileNameRFC');

            // 🔽 Input imagen logo
            const logoInput = document.getElementById('logoInput');
            const logoPreview = document.getElementById('logoPreview');
            if (logoInput && logoPreview) {
                logoInput.addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (evt) {
                            logoPreview.src = evt.target.result;
                            logoPreview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>
</div>

<div class="container">
    <div class="title-cont">
        <p class="title">Tracking de status</p>
    </div>

    <table>
        <thead>
            <tr>
                <th></th>
                <th>Fecha</th>
                <th>Concepto</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <!-- <?php while ($instituciones->have_posts()):
                $instituciones->the_post();
                $post_id = get_the_ID();
                $info_contacto = get_field('informacion_de_contacto', $post_id);
                $info_general = get_field('informacion_general', $post_id);
                $single_url = get_permalink($post_id);

                ?>
                <tr data-url="<?php echo esc_url($single_url); ?>">
                    <td>
                        <input type="checkbox" name="instituciones[]" value="<?php echo esc_attr($post_id); ?>">
                    </td>
                    <td><?php the_title(); ?></td>
                    <td><?php echo esc_html($info_general['rfc'] ?? '-'); ?></td>
                    <td><?php echo esc_html($info_contacto['sede'] ?? '-'); ?></td>
                    <td><?php echo esc_html($info_general['estado'] ?? '-'); ?></td>
                    <td><?php echo esc_html($info_contacto['ciudad'] ?? '-'); ?></td>
                    <td><?php echo esc_html($info_contacto['datos_del_presidente']['nombre_del_presidente'] ?? '-') ?></td>
                    <td><?php echo esc_html($info_contacto['datos_del_presidente']['correo_contacto'] ?? '-'); ?></td>
                    <td><?php echo esc_html($info_contacto['datos_del_presidente']['telefono'] ?? '-'); ?></td>
                </tr>
            <?php endwhile; ?> -->
        </tbody>
    </table>

</div>

<?php get_footer('admin'); ?>