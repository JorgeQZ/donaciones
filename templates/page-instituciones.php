<?php
/**
 * Template Name: Instituciones
**/

get_header();
?>

<div class="wrapper">

    <div class="formulario">
        <p class="titulo">Registro de Institución</p>

        <form action="" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('registrar_institucion', 'institucion_nonce'); ?>

            <p class="subtitulo">Información General</p>

            <div>
                <label>RFC*</label>
                <input type="text" name="rfc" required>
            </div>

            <div>
                <label>Tipo de Institución</label>
                <select name="tipo_institucion">
                    <option value="">Seleccione una opción</option>
                    <option value="asociacion civil">Asociación Civil</option>
                    <option value="fundacion">Fundación</option>
                    <option value="iap">IAP</option>
                    <option value="otro">Otro</option>
                </select>
            </div>

            <div>
                <label>Nombre Fiscal*</label>
                <input type="text" name="nombre_fiscal" required>
            </div>

            <div>
                <label>Domicilio Fiscal</label>
                <input type="text" name="domicilio_fiscal">
            </div>

            <div>
                <label>Estado</label>
                <select name="estado">
                    <option value="">Seleccione una opción</option>
                    <option value="aguascalientes">Aguascalientes</option>
                    <option value="baja-california">Baja California</option>
                    <option value="baja-california-sur">Baja California Sur</option>
                    <option value="campeche">Campeche</option>
                    <option value="chiapas">Chiapas</option>
                    <option value="chihuahua">Chihuahua</option>
                    <option value="ciudad-de-mexico">Ciudad de México</option>
                    <option value="coahuila">Coahuila</option>
                    <option value="colima">Colima</option>
                    <option value="durango">Durango</option>
                    <option value="estado-de-mexico">Estado de México</option>
                    <option value="guanajuato">Guanajuato</option>
                    <option value="guerrero">Guerrero</option>
                    <option value="hidalgo">Hidalgo</option>
                    <option value="jalisco">Jalisco</option>
                    <option value="michoacan">Michoacán</option>
                    <option value="morelos">Morelos</option>
                    <option value="nayarit">Nayarit</option>
                    <option value="nuevo-leon">Nuevo León</option>
                    <option value="oaxaca">Oaxaca</option>
                    <option value="puebla">Puebla</option>
                    <option value="queretaro">Querétaro</option>
                    <option value="quintana-roo">Quintana Roo</option>
                    <option value="san-luis-potosi">San Luis Potosí</option>
                    <option value="sinaloa">Sinaloa</option>
                    <option value="sonora">Sonora</option>
                    <option value="tabasco">Tabasco</option>
                    <option value="tamaulipas">Tamaulipas</option>
                    <option value="tlaxcala">Tlaxcala</option>
                    <option value="veracruz">Veracruz</option>
                    <option value="yucatan">Yucatán</option>
                    <option value="zacatecas">Zacatecas</option>
                </select>
            </div>

            <div>
                <label>Municipio</label>
                <input type="text" name="municipio">
            </div>

            <p class="subtitulo">Información de Contacto</p>

            <div>
                <label>Institución Subsidiaria</label>
                <select name="institucion_subsidiaria">
                    <option value="">Seleccione una opción</option>
                    <option value="si">Sí</option>
                    <option value="no">No</option>
                </select>
            </div>

            <div>
                <label>Entidad Federativa*</label>
                <select name="entidad_federativa" required>
                    <option value="">Seleccione una opción</option>
                    <option value="aguascalientes">Aguascalientes</option>
                    <option value="baja-california">Baja California</option>
                    <option value="baja-california-sur">Baja California Sur</option>
                    <option value="campeche">Campeche</option>
                    <option value="chiapas">Chiapas</option>
                    <option value="chihuahua">Chihuahua</option>
                    <option value="ciudad-de-mexico">Ciudad de México</option>
                    <option value="coahuila">Coahuila</option>
                    <option value="colima">Colima</option>
                    <option value="durango">Durango</option>
                    <option value="estado-de-mexico">Estado de México</option>
                    <option value="guanajuato">Guanajuato</option>
                    <option value="guerrero">Guerrero</option>
                    <option value="hidalgo">Hidalgo</option>
                    <option value="jalisco">Jalisco</option>
                    <option value="michoacan">Michoacán</option>
                    <option value="morelos">Morelos</option>
                    <option value="nayarit">Nayarit</option>
                    <option value="nuevo-leon">Nuevo León</option>
                    <option value="oaxaca">Oaxaca</option>
                    <option value="puebla">Puebla</option>
                    <option value="queretaro">Querétaro</option>
                    <option value="quintana-roo">Quintana Roo</option>
                    <option value="san-luis-potosi">San Luis Potosí</option>
                    <option value="sinaloa">Sinaloa</option>
                    <option value="sonora">Sonora</option>
                    <option value="tabasco">Tabasco</option>
                    <option value="tamaulipas">Tamaulipas</option>
                    <option value="tlaxcala">Tlaxcala</option>
                    <option value="veracruz">Veracruz</option>
                    <option value="yucatan">Yucatán</option>
                    <option value="zacatecas">Zacatecas</option>
                </select>
            </div>

            <div>
                <label>Ciudad*</label>
                <input type="text" name="ciudad" required>
            </div>

            <div>
                <label>Sede</label>
                <input type="text" name="sede">
            </div>

            <div>
                <label>Nombre del Presidente/Director de la Institución</label>
                <input type="text" name="nombre_del_presidente">
            </div>

            <div>
                <label>Correo Contacto</label>
                <input type="email" name="correo_contacto">
            </div>

            <div>
                <label>Teléfono</label>
                <input type="text" name="telefono">
            </div>

            <div>
                <label>Persona de Contacto 1</label>
                <input type="text" name="persona_contacto_1">
            </div>

            <div>
                <label>Correo Contacto 1</label>
                <input type="email" name="correo_contacto_1">
            </div>

            <div>
                <label>Teléfono 1</label>
                <input type="text" name="telefono_1">
            </div>

            <div>
                <label>Persona de Contacto 2</label>
                <input type="text" name="persona_contacto_2">
            </div>

            <div>
                <label>Correo Contacto 2</label>
                <input type="email" name="correo_contacto_2">
            </div>

            <div>
                <label>Teléfono 2</label>
                <input type="text" name="telefono_2">
            </div>

            <div>
                <label>Página Web de la Institución</label>
                <input type="text" name="web">
            </div>

            <div>
                <label>Facebook</label>
                <input type="text" name="facebook">
            </div>

            <div>
                <label>Instagram</label>
                <input type="text" name="instagram">
            </div>

            <div>
                <label>Tiktok</label>
                <input type="text" name="tiktok">
            </div>

            <div>
                <label>Tienda Adicional</label>
                <select name="tienda_adicional">
                    <option value="">Seleccione una opción</option>
                    <option value="Sí">Sí</option>
                    <option value="No">No</option>
                </select>
            </div>

            <div>
                <label>Dirección de la Tienda Adicional</label>
                <input type="text" name="direccion_tienda_adicional">
            </div>

            <p class="subtitulo">Necesidades</p>

            <div class="divtextarea">
                <label>Principal necesidad que tiene la institución</label>
                <textarea name="necesidad"></textarea>
            </div>

            <div>
                <label>No. anual personas beneficiadas*</label>
                <input type="text" name="numero_anual" required>
            </div>

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

            <div>
                <label>Tipo de labor que realiza</label>
                <input type="text" name="tipo_labor">
            </div>

            <p class="subtitulo">Presentación Institucional</p>

            <div>
                <label>Adjuntar Logo de la Institución</label>
                <input type="file" name="logo_de_la_institucion">
            </div>

            <div>
                <label>Adjuntar Carta Solicitud en Word o Pdf</label>
                <input type="file" name="carta_solicitud">
            </div>

            <div>
                <label>Adjuntar Fotografías</label>
                <input type="file" name="fotografias">
            </div>

            <p class="subtitulo">Archivos Requeridos</p>

            <div>
                <label>Acta Constitutiva en PDF</label>
                <input type="file" name="acta_constitutiva">
            </div>

            <div>
                <label>Comprobante de Domicilio en PDF o foto</label>
                <input type="file" name="comprobante_domicilio">
            </div>

            <div>
                <label>Copia Recibo Dedudicble en PDF o foto</label>
                <input type="file" name="deducible">
            </div>

            <div>
                <label>Identificación del apoderado legal en PDF o foto</label>
                <input type="file" name="apoderado_legal">
            </div>

            <div>
                <label>Solicitud de alta de institución en Excel</label>
                <input type="file" name="institucion_excel">
            </div>

            <div>
                <label>Certificado de Donaciones en PDF</label>
                <input type="file" name="certificado_donaciones">
            </div>

            <div>
                <label>RFC en PDF</label>
                <input type="file" name="rfc_archivo">
            </div>

            <button type="submit" name="submit_institucion">Enviar</button>
        </form>

    </div>

    <script>
        document.querySelectorAll('.custom-select').forEach(selectEl => {
        const optionsContainer = selectEl.querySelector('.custom-options');
        const placeholder = selectEl.querySelector('.selected-placeholder');
        const hiddenInput = selectEl.parentElement.querySelector('input[type="hidden"]');
        let selected = [];

        // Abrir/cerrar menú
        selectEl.addEventListener('click', (e) => {
        e.stopPropagation();
        optionsContainer.classList.toggle('hidden');
        });

        // Cerrar menú globalmente
        document.addEventListener('click', () => {
        document.querySelectorAll('.custom-options').forEach(opt => opt.classList.add('hidden'));
        });

        // Manejar selección
        function handleOptionClick(optionDiv) {
        const value = optionDiv.dataset.value;
        const text = optionDiv.textContent;

        if (!selected.find(s => s.value === value)) {
            selected.push({ value, text });
            updateUI();
        }
        }

        // Actualizar etiquetas y valores
        function updateUI() {
        placeholder.innerHTML = '';
        selected.forEach(item => {
            const span = document.createElement('span');
            span.className = 'tag';
            span.dataset.value = item.value;
            span.textContent = item.text;

            const removeBtn = document.createElement('span');
            removeBtn.className = 'remove-tag';
            removeBtn.textContent = '×';
            removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            selected = selected.filter(s => s.value !== item.value);

            const restored = document.createElement('div');
            restored.dataset.value = item.value;
            restored.textContent = item.text;
            restored.addEventListener('click', () => handleOptionClick(restored));
            optionsContainer.appendChild(restored);

            updateUI();
            });

            span.appendChild(removeBtn);
            placeholder.appendChild(span);
        });

        hiddenInput.value = selected.map(s => s.value).join(',');

        Array.from(optionsContainer.children).forEach(opt => {
            if (selected.find(s => s.value === opt.dataset.value)) {
            opt.remove();
            }
        });
        }

        // Inicializar opciones
        Array.from(optionsContainer.children).forEach(opt => {
        opt.addEventListener('click', () => handleOptionClick(opt));
        });
    });
    </script>

</div>

<?php get_footer(); ?>