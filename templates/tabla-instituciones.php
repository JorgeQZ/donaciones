<?php
$args = [
   'post_type'      => 'institucion',
   'posts_per_page' => -1,
   'post_status'    => 'publish'
];

$instituciones = new WP_Query($args);

if ($instituciones->have_posts()) : ?>
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
         </tr>
      </thead>
      <tbody>
         <?php while ( $instituciones->have_posts() ) :
         $instituciones->the_post();
         $post_id = get_the_ID();
         $info_contacto = get_field('informacion_de_contacto', $post_id);
         $info_general = get_field('informacion_general', $post_id);
         $single_url = get_permalink( $post_id );

         ?>
         <tr data-url="<?php echo  esc_url( $single_url  ); ?>">
            <td>
                 <input type="checkbox"
                      name="instituciones[]"
                      value="<?php echo esc_attr($post_id); ?>">
            </td>
            <td><?php the_title(); ?></td>
            <td><?php echo esc_html($info_general['rfc'] ?? '-');?></td>
            <td><?php echo esc_html($info_contacto['sede'] ?? '-'); ?></td>
            <td><?php echo esc_html($info_general['estado'] ?? '-'); ?></td>
            <td><?php echo esc_html($info_contacto['ciudad'] ?? '-'); ?></td>
            <td><?php echo esc_html($info_contacto['datos_del_presidente']['nombre_del_presidente'] ?? '-')?></td>
            <td><?php echo esc_html($info_contacto['datos_del_presidente']['correo_contacto'] ?? '-'); ?></td>
            <td><?php echo esc_html($info_contacto['datos_del_presidente']['telefono'] ?? '-'); ?></td>
         </tr>
         <?php endwhile; ?>
      </tbody>
   </table>
<?php else : ?>
   <p>No hay instituciones registradas.</p>
<?php endif;

wp_reset_postdata();
?>