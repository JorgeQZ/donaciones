document.addEventListener('DOMContentLoaded', () => {
  const cfg = window.THD_INST || {};
  const estadoSelect   = document.getElementById('estado-select');
  const municipioSelect= document.getElementById('municipio-select');
  const entidadSelect  = document.getElementById('entidad-select');
  const ciudadSelect   = document.getElementById('ciudad-select');

  const estadoMap = {}; // nombre => nombre

  const valoresMunicipios = {
    ig_municipio: cfg.IG_municipio || '',
    ig_estado:    cfg.IG_estado    || '',
    ic_ciudad:    cfg.IC_ciudad    || '',
    ic_entidad:   cfg.IC_entidad   || ''
  };

  // --- utils ---
  const cssEscape = (window.CSS && CSS.escape) ? CSS.escape : (s) => String(s).replace(/[^a-zA-Z0-9_\-]/g, '\\$&');

  // Llenar municipios para un estado dado
  function llenarMunicipios(estadoNombre, selectEl, valorActual) {
    if (!estadoNombre) {
      selectEl.innerHTML = '<option value="">Seleccione un municipio</option>';
      return;
    }
    fetch(cfg.jsonMunicipios)
      .then(r => r.json())
      .then(data => {
        const municipios = data[estadoNombre] || [];
        selectEl.innerHTML = '<option value="">Seleccione un municipio</option>';
        let existe = false;
        municipios.forEach(m => {
          const opt = document.createElement('option');
          opt.value = m;
          opt.textContent = m;
          if (m === valorActual) { opt.selected = true; existe = true; }
          selectEl.appendChild(opt);
        });
        // si el valor actual no está en la lista, lo agregamos para no perderlo
        if (valorActual && !existe) {
          const opt = document.createElement('option');
          opt.value = valorActual;
          opt.textContent = valorActual;
          opt.selected = true;
          selectEl.appendChild(opt);
        }
      })
      .catch(err => {
        console.error('Error al cargar municipios:', err);
        selectEl.innerHTML = '<option value="">Error al cargar</option>';
      });
  }

  // Cargar estados y precargar selects
  fetch(cfg.jsonEstados)
    .then(r => r.json())
    .then(estados => {
      estados.forEach(nombre => {
        estadoMap[nombre] = nombre;
        if (nombre === valoresMunicipios.ic_entidad) {
          llenarMunicipios(nombre, ciudadSelect, valoresMunicipios.ic_ciudad);
        } else if (nombre === valoresMunicipios.ig_estado) {
          llenarMunicipios(nombre, municipioSelect, valoresMunicipios.ig_municipio);
        }
      });
    })
    .catch(err => console.error('Error al cargar estados:', err));

  // Eventos change
  if (estadoSelect) {
    estadoSelect.addEventListener('change', function () {
      llenarMunicipios(this.value, municipioSelect, '');
    });
  }
  if (entidadSelect) {
    entidadSelect.addEventListener('change', function () {
      llenarMunicipios(this.value, ciudadSelect, '');
    });
  }

  // --- Manejo de archivos: escribir nombres en labels ---
  function handleFileChange(inputId, fileLabelId) {
    const input = document.getElementById(inputId);
    const label = document.getElementById(fileLabelId);
    if (!input || !label) return;

    input.addEventListener('change', (e) => {
      const files = Array.from(e.target.files || []);
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

  // --- Preview de logo ---
  const logoInput = document.getElementById('logoInput');
  const logoPreview = document.getElementById('logoPreview');
  if (logoInput && logoPreview) {
    logoInput.addEventListener('change', (e) => {
      const file = (e.target.files || [])[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = (evt) => { logoPreview.src = evt.target.result; logoPreview.style.display = 'block'; };
      reader.readAsDataURL(file);
    });
  }

  // --- Custom select chips ---
  document.querySelectorAll('.custom-select').forEach(select => {
    const placeholder = select.querySelector('.selected-placeholder');
    const optionsBox  = select.querySelector('.custom-options');
    const options     = select.querySelectorAll('.custom-options .option');
    const inputTag    = select.querySelector('.custom-input-tag');

    function updateHiddenInput() {
      const wrapper = select.closest('.custom-select-wrapper');
      wrapper.querySelectorAll(`input[name="${select.dataset.name}[]"]`).forEach(el => el.remove());
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

    if (placeholder) {
      placeholder.addEventListener('click', e => {
        if (!e.target.classList.contains('remove-tag')) return;
        const tag = e.target.closest('.tag');
        const value = tag.dataset.value;
        tag.remove();
        const matchingOption = select.querySelector(`.custom-options .option[data-value="${cssEscape(value)}"]`);
        if (matchingOption) matchingOption.classList.remove('disabled');
        updateHiddenInput();
      });
    }

    if (inputTag) {
      inputTag.addEventListener('input', () => {
        const q = inputTag.value.trim().toLowerCase();
        let any = false;
        options.forEach(opt => {
          const text = opt.textContent.toLowerCase();
          const visible = text.includes(q) && !opt.classList.contains('disabled');
          opt.style.display = visible ? 'block' : 'none';
          if (visible) any = true;
        });
        if (optionsBox) optionsBox.style.display = any ? 'block' : 'none';
      });

      inputTag.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const rawValue = inputTag.value.trim();
        if (!rawValue) return;

        const exists = Array.from(placeholder.querySelectorAll('.tag'))
          .some(tag => tag.dataset.value.toLowerCase() === rawValue.toLowerCase());
        if (exists) { inputTag.value = ''; return; }

        const tag = document.createElement('span');
        tag.className = 'tag';
        tag.dataset.value = rawValue;
        tag.innerHTML = `${rawValue}<span class="remove-tag">×</span>`;
        placeholder.appendChild(tag);

        inputTag.value = '';
        updateHiddenInput();
        options.forEach(opt => opt.style.display = 'none');
      });

      inputTag.addEventListener('focus', () => {
        options.forEach(opt => { opt.style.display = opt.classList.contains('disabled') ? 'none' : 'block'; });
        if (optionsBox) optionsBox.style.display = 'block';
      });
    }
  });
});


// --- Estado por AJAX (autorizar/rechazar) ---
document.addEventListener('submit', async (e) => {
  const form = e.target.closest('.tracking-estatus form');
  if (!form) return;            // solo interceptamos los forms de la tabla
  e.preventDefault();

  const fd  = new FormData(form);
  const pid = fd.get('institucion_id');
  const key = fd.get('archivo_key');
  const est = fd.get('nuevo_estado');
  const nonce = fd.get('ajax_nonce');

  if (!pid || !key || !est || !nonce || !THD_INST || !THD_INST.ajaxUrl) { form.submit(); return; }

  // Señal de cargando
  const btn = form.querySelector('.btn-status');
  const tr  = form.closest('tr');
  const tdEstado = tr ? tr.querySelector('.td-estado') : null;
  if (btn) { btn.disabled = true; btn.dataset._txt = btn.textContent; btn.textContent = 'Guardando…'; }

  try {
    const res = await fetch(THD_INST.ajaxUrl, {
      method: 'POST',
      headers: {'Accept':'application/json'},
      body: new URLSearchParams({
        action: 'thd_cambiar_estado_archivo',
        institucion_id: pid,
        archivo_key: key,
        nuevo_estado: est,
        nonce: nonce
      })
    });
    const json = await res.json();
    if (!json || !json.success) throw new Error((json && json.data && json.data.msg) || 'Error');

    if (tdEstado) tdEstado.innerHTML = json.data.badge || '';
    // Toast rápido (opcional)
    if (!document.querySelector('.thd-notice')) {
      const n = document.createElement('div');
      n.className = 'thd-notice';
      n.innerHTML = '<span class="thd-notice__icon">✓</span><div class="thd-notice__text">Estado actualizado</div><button class="thd-notice__close">×</button>';
      document.body.appendChild(n);
      const close = n.querySelector('.thd-notice__close');
      if (close) close.addEventListener('click', ()=>{ n.classList.add('is-hide'); setTimeout(()=>n.remove(),250); });
      setTimeout(()=>{ n.classList.add('is-hide'); setTimeout(()=>n.remove(),250); }, 2500);
    }
  } catch (err) {
    console.error(err);
    // Fallback: si falla el AJAX, enviamos el form normal
    form.submit();
    return;
  } finally {
    if (btn) { btn.disabled = false; btn.textContent = btn.dataset._txt || 'Listo'; }
  }
});
