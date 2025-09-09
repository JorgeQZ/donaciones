<?php
$ajax_url   = admin_url('admin-ajax.php');
$rem_nonce  = wp_create_nonce('thd_send_reminder');

$args = [
  'post_type'      => 'institucion',
  'posts_per_page' => -1,
  'post_status'    => 'publish',
];
$q = new WP_Query($args);

if ($q->have_posts()) : ?>
<table>
    <thead>
        <tr>
            <th></th>
            <th>NOMBRE</th>
            <th>RFC</th>
            <th>SEDE</th>
            <th>ESTADO</th>
            <th>CIUDAD</th>
            <th>DIRECTOR</th>
            <th>CONTACTO</th>
            <th>TELÉFONO</th>
            <th>COMPLETO</th>
            <th>ACCIONES</th>
        </tr>
    </thead>
    <tbody>
        <?php
    while ($q->have_posts()) :
        $q->the_post();
        $post_id       = get_the_ID();
        $info_general  = (array) get_field('informacion_general', $post_id);
        $info_contacto = (array) get_field('informacion_de_contacto', $post_id);
        $single_url    = get_permalink($post_id);

        // faltantes (si no existe helper, define un mínimo)
        if (!function_exists('thd_institucion_missing_fields')) {
            function thd_institucion_missing_fields($pid)
            {
                $ig = (array) get_field('informacion_general', $pid);
                $ic = (array) get_field('informacion_de_contacto', $pid);
                $ne = (array) get_field('necesidades', $pid);
                $faltan = [];
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
                    $faltan[] = 'Grupo social';
                }
                if (empty($ne['sector_apoyo'])) {
                    $faltan[] = 'Sector de apoyo';
                }
                return $faltan;
            }
        }
        $missing      = thd_institucion_missing_fields($post_id);
        $miss_count   = is_array($missing) ? count($missing) : 0;
        $complete_txt = $miss_count ? $miss_count.' faltante(s)' : 'Sí';
        ?>
        <tr data-url="<?php echo esc_url($single_url); ?>" data-id="<?php echo esc_attr($post_id); ?>">
            <td>
                <input type="checkbox" name="instituciones[]" value="<?php echo esc_attr($post_id); ?>">
            </td>
            <td><?php the_title(); ?></td>
            <td><?php echo esc_html($info_general['rfc'] ?? '-'); ?></td>
            <td><?php echo esc_html($info_contacto['sede'] ?? '-'); ?></td>
            <td><?php echo esc_html($info_general['estado'] ?? '-'); ?></td>
            <td><?php echo esc_html($info_contacto['ciudad'] ?? '-'); ?></td>
            <td><?php echo esc_html($info_contacto['datos_del_presidente']['nombre_del_presidente'] ?? '-'); ?></td>
            <td><?php echo esc_html($info_contacto['datos_del_presidente']['correo_contacto'] ?? '-'); ?></td>
            <td><?php echo esc_html($info_contacto['datos_del_presidente']['telefono'] ?? '-'); ?></td>
            <td>
                <span
                    style="padding:2px 6px;border-radius:12px;
            <?php echo $miss_count ? 'background:#fff5f5;border:1px solid #dc3232;color:#dc3232;' : 'background:#f6ffed;border:1px solid #46b450;color:#155724;'; ?>">
                    <?php echo esc_html($complete_txt); ?>
                </span>
            </td>
            <td>
                <button type="button" class="btn-reminder" data-ajax="<?php echo esc_url($ajax_url); ?>"
                    data-nonce="<?php echo esc_attr($rem_nonce); ?>" data-post="<?php echo esc_attr($post_id); ?>"
                    title="Enviar recordatorio">
                    Recordatorio
                </button>
            </td>
        </tr>
        <?php
    endwhile; ?>
    </tbody>
</table>

<script>
// Delegación de eventos para el botón "Recordatorio" (funciona tras recargas AJAX)
(function() {
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-reminder');
        if (!btn) return;

        e.stopPropagation(); // que no dispare el click de la fila
        btn.disabled = true;
        const old = btn.textContent;
        btn.textContent = 'Enviando...';

        const ajax = btn.dataset.ajax;
        const nonce = btn.dataset.nonce;
        const pid = btn.dataset.post;

        fetch(ajax, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: new URLSearchParams({
                    action: 'thd_send_inst_reminder',
                    post_id: pid,
                    nonce: nonce
                })
            })
            .then(r => r.json())
            .then(j => {
                if (j && j.success) {
                    btn.textContent = 'Enviado';
                } else {
                    alert((j && j.data && j.data.msg) ? j.data.msg : 'No se pudo enviar.');
                    btn.textContent = old;
                }
            })
            .catch(() => {
                alert('Error de red.');
                btn.textContent = old;
            })
            .finally(() => {
                btn.disabled = false;
            });
    }, true);
})();
</script>

<?php else : ?>
<p>No hay instituciones registradas.</p>
<?php endif;

wp_reset_postdata();