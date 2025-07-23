<?php
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

$IG_estado = $info_general['estado'] ?? '';
$IG_municipio = $info_general['municipio'] ?? '';

$IC_entidad = $info_contacto['entidad_federativa'] ?? '';
$IC_ciudad = $info_contacto['ciudad'] ?? '';
$tipo_institucion = $info_general['tipo_institucion'] ?? '';

$tipos_institucion = [
    'asociacion civil',
    'fundacion',
    'iap',
    'otro'
];

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
                            <option value="<?php echo esc_attr($tipo); ?>" <?php if (esc_attr($tipo) == $tipo_institucion) {
                                   echo 'selected';
                               } ?>>
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
                            <option value="<?php echo esc_attr($estado); ?>" <?php if (esc_attr($estado) == $IG_estado) {
                                   echo 'selected';
                               } ?>>
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
                            <option value="<?php echo esc_attr($estado); ?>" <?php if (esc_attr($estado) == $IC_entidad) {
                                   echo 'selected';
                               } ?>>
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
            </tbody>
        </table>


    </div>
</div>


<script>

    document.addEventListener('DOMContentLoaded', function () {

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
                        console.log(nombre);
                        llenarMunicipios(nombre, ciudadSelect, valoresMunicipios.ic_ciudad);

                    } else if (nombre == valoresMunicipios.ig_estado) {
                        console.log(nombre);
                        llenarMunicipios(nombre, municipioSelect, valoresMunicipios.ig_municipio);
                    }
                });
            })
            .catch(err => {
                console.error('Error al cargar estados:', err);
            });

        // Función para llenar municipios basada en estadoSlug
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

        estadoSelect.addEventListener('change', function () {
            const estadoSlug = this.value;
            llenarMunicipios(estadoSlug, municipioSelect, '');
        });

        entidadSelect.addEventListener('change', function () {
            const estadoSlug = this.value;
            llenarMunicipios(estadoSlug, ciudadSelect, '');
        });

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
<?php get_footer('admin'); ?>