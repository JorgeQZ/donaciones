<?php
function registrar_cpt_instituciones()
{
    $labels = array(
        'name' => 'Instituciones',
        'singular_name' => 'Institución',
        'menu_name' => 'Instituciones',
        'name_admin_bar' => 'Institución',
        'add_new' => 'Agregar nueva',
        'add_new_item' => 'Agregar nueva institución',
        'new_item' => 'Nueva institución',
        'edit_item' => 'Editar institución',
        'view_item' => 'Ver institución',
        'all_items' => 'Todas las instituciones',
        'search_items' => 'Buscar instituciones',
        'not_found' => 'No se encontraron instituciones',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-building',
        'supports' => array('title'),
        'has_archive' => false,
        'publicly_queryable' => true,  // explícito si quieres
        'rewrite' => array('slug' => 'institucion'),
        'capability_type' => 'post',
    );

    register_post_type('institucion', $args);
}
add_action('init', 'registrar_cpt_instituciones');

if (function_exists('acf_add_local_field_group')):

    acf_add_local_field_group(array(
        'key' => 'group_presentacion_institucional',
        'title' => 'Presentación Institucional',
        'fields' => array(
            array(
                'key' => 'field_adjuntar_fotografias',
                'label' => 'Adjuntar Fotografías',
                'name' => 'adjuntar_fotografias',
                'type' => 'repeater',
                'instructions' => 'Puedes subir varias imágenes. Máximo 6.',
                'min' => 1,
                'max' => 6,
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_fotografia_1',
                        'label' => 'Fotografía',
                        'name' => 'fotografia',
                        'type' => 'image',
                        'return_format' => 'array',
                        'preview_size' => 'medium',
                        'library' => 'all'
                    )
                )
            )
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'institucion'
                )
            )
        )
    ));

    // Añadir columnas personalizadas al listado de instituciones
    add_filter('manage_institucion_posts_columns', function ($columns) {
        $new_columns = [];

        // Mantener la columna de título y fecha
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = 'Nombre Fiscal';
        $new_columns['rfc'] = 'RFC';
        $new_columns['sede'] = 'Sede';
        $new_columns['estado'] = 'Estado';
        $new_columns['ciudad'] = 'Ciudad';
        $new_columns['director'] = 'Director';
        $new_columns['correo_contacto'] = 'Correo';
        $new_columns['telefono'] = 'Teléfono';
        $new_columns['date'] = $columns['date'];

        return $new_columns;
    });

    // Mostrar valores en columnas personalizadas
    add_action('manage_institucion_posts_custom_column', function ($column, $post_id) {
        $info_contacto = get_field('informacion_de_contacto', $post_id);
        switch ($column) {
            case 'rfc':
                $info_general = get_field('informacion_general', $post_id);
                echo esc_html($info_general['rfc'] ?? '-');
                break;
            case 'sede':
                echo esc_html($info_contacto['sede'] ?? '-');
                break;
            case 'estado':
                $info_general = get_field('informacion_general', $post_id);
                echo esc_html($info_general['estado'] ?? '-');
                break;
            case 'ciudad':
                echo esc_html($info_contacto['ciudad'] ?? '-');
                break;
            case 'director':
                echo esc_html($info_contacto['datos_del_presidente']['nombre_del_presidente'] ?? '-');
                break;
            case 'correo_contacto':
                echo esc_html($info_contacto['datos_del_presidente']['correo_contacto'] ?? '-');
                break;
            case 'telefono':
                echo esc_html($info_contacto['datos_del_presidente']['telefono'] ?? '-');
                break;
        }
    }, 10, 2);

endif;

// ENVIO DE DATOS DEL FORMULARIO REGISTRO DE INSTITUCIÓN A POST-TYPE INSTITUCIÓN

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_institucion'])) {
    if (isset($_POST['institucion_nonce']) && wp_verify_nonce($_POST['institucion_nonce'], 'registrar_institucion')) {

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Sanitizar campos
        $rfc = sanitize_text_field($_POST['rfc']);
        $nombre_fiscal = sanitize_text_field($_POST['nombre_fiscal']);
        $domicilio_fiscal = sanitize_text_field($_POST['domicilio_fiscal']);
        $tipo_institucion = sanitize_text_field($_POST['tipo_institucion']);
        $estado = sanitize_text_field($_POST['estado']);
        $municipio = sanitize_text_field($_POST['municipio']);

        $entidad_federativa = sanitize_text_field($_POST['entidad_federativa']);
        $ciudad = sanitize_text_field($_POST['ciudad']);
        $sede = sanitize_text_field($_POST['sede']);
        $subsidiaria = sanitize_text_field($_POST['institucion_subsidiaria']);
        $nombre_director = sanitize_text_field($_POST['nombre_del_presidente']);
        $correo_contacto = sanitize_email($_POST['correo_contacto']);
        $telefono = sanitize_text_field($_POST['telefono']);
        $persona_contacto_1 = sanitize_text_field($_POST['persona_contacto_1']);
        $correo_contacto_1 = sanitize_email($_POST['correo_contacto_1']);
        $telefono_1 = sanitize_text_field($_POST['telefono_1']);
        $persona_contacto_2 = sanitize_text_field($_POST['persona_contacto_2']);
        $correo_contacto_2 = sanitize_email($_POST['correo_contacto_2']);
        $telefono_2 = sanitize_text_field($_POST['telefono_2']);
        $web = esc_url_raw($_POST['web']);
        $facebook = sanitize_text_field($_POST['facebook']);
        $instagram = sanitize_text_field($_POST['instagram']);
        $tiktok = sanitize_text_field($_POST['tiktok']);
        $tienda_adicional = sanitize_text_field($_POST['tienda_adicional']);
        $direccion_tienda = sanitize_text_field($_POST['direccion_tienda_adicional']);
        $necesidad = sanitize_text_field($_POST['necesidad']);
        $numero_anual = sanitize_text_field($_POST['numero_anual']);
        $grupo_social = isset($_POST['grupo_social']) ? $_POST['grupo_social'] : [];
        $sector_apoyo = isset($_POST['sector_apoyo']) ? $_POST['sector_apoyo'] : [];
        $tipo_labor = sanitize_text_field($_POST['tipo_labor']);

        // Crear post
        $post_id = wp_insert_post([
            'post_type' => 'institucion',
            'post_title' => $nombre_fiscal,
            'post_status' => 'publish',
        ]);

        if (!is_wp_error($post_id)) {

            // Información General
            update_field('informacion_general', [
                'rfc' => $rfc,
                'nombre_fiscal' => $nombre_fiscal,
                'domicilio_fiscal' => $domicilio_fiscal,
                'tipo_institucion' => $tipo_institucion,
                'estado' => $estado,
                'municipio' => $municipio,
            ], $post_id);

            // Información de Contacto
            update_field('informacion_de_contacto', [
                'entidad_federativa' => $entidad_federativa,
                'ciudad' => $ciudad,
                'sede' => $sede,
                'institucion_subsidiaria' => $subsidiaria,
                'tienda_adicional' => $tienda_adicional,
                'direccion_tienda_adicional' => $direccion_tienda,
                'datos_del_presidente' => [
                    'nombre_del_presidente' => $nombre_director,
                    'correo_contacto' => $correo_contacto,
                    'telefono' => $telefono,
                ],
                'grupo_persona_contacto_uno' => [
                    'persona_contacto_1' => $persona_contacto_1,
                    'correo_contacto_1' => $correo_contacto_1,
                    'telefono_1' => $telefono_1,
                ],
                'grupo_persona_contacto_2' => [
                    'persona_contacto_2' => $persona_contacto_2,
                    'correo_contacto_2' => $correo_contacto_2,
                    'telefono_2' => $telefono_2,
                ],
                'redes_sociales' => [
                    'web' => $web,
                    'facebook' => $facebook,
                    'instagram' => $instagram,
                    'tiktok' => $tiktok,
                ]
            ], $post_id);

            // Necesidades
            update_field('necesidades', [
                'necesidad' => $necesidad,
                'numero_anual' => $numero_anual,
                'grupo_social' => $grupo_social,
                'sector_apoyo' => $sector_apoyo,
                'tipo_labor' => $tipo_labor,
            ], $post_id);

            // Archivos
            $archivos = [
                'logo_de_la_institucion',
                'carta_solicitud',
                'fotografias',
                'acta_constitutiva',
                'comprobante_domicilio',
                'deducible',
                'apoderado_legal',
                'institucion_excel',
                'certificado_donaciones',
                'rfc_archivo'
            ];

            // Archivos: separar en 2 grupos
            $presentacion_institucional = [];
            $archivos_requeridos = [];

            // Presentación Institucional
            if (!empty($_FILES['logo_de_la_institucion']['name'])) {
                $upload = media_handle_upload('logo_de_la_institucion', $post_id);
                if (!is_wp_error($upload)) {
                    $presentacion_institucional['logo_de_la_institucion'] = $upload;
                }
            }

            if (!empty($_FILES['carta_solicitud']['name'])) {
                $upload = media_handle_upload('carta_solicitud', $post_id);
                if (!is_wp_error($upload)) {
                    $presentacion_institucional['carta_solicitud'] = $upload;
                }
            }

            if (!empty($_FILES['fotografias']['name'])) {
                $upload = media_handle_upload('fotografias', $post_id);
                if (!is_wp_error($upload)) {
                    $presentacion_institucional['fotografias'] = $upload;
                }
            }

            // Archivos Requeridos
            if (!empty($_FILES['acta_constitutiva']['name'])) {
                $upload = media_handle_upload('acta_constitutiva', $post_id);
                if (!is_wp_error($upload)) {
                    $archivos_requeridos['acta_constitutiva'] = $upload;
                }
            }

            if (!empty($_FILES['comprobante_domicilio']['name'])) {
                $upload = media_handle_upload('comprobante_domicilio', $post_id);
                if (!is_wp_error($upload)) {
                    $archivos_requeridos['comprobante_domicilio'] = $upload;
                }
            }

            if (!empty($_FILES['deducible']['name'])) {
                $upload = media_handle_upload('deducible', $post_id);
                if (!is_wp_error($upload)) {
                    $archivos_requeridos['deducible'] = $upload;
                }
            }

            if (!empty($_FILES['apoderado_legal']['name'])) {
                $upload = media_handle_upload('apoderado_legal', $post_id);
                if (!is_wp_error($upload)) {
                    $archivos_requeridos['apoderado_legal'] = $upload;
                }
            }

            if (!empty($_FILES['institucion_excel']['name'])) {
                $upload = media_handle_upload('institucion_excel', $post_id);
                if (!is_wp_error($upload)) {
                    $archivos_requeridos['institucion_excel'] = $upload;
                }
            }

            if (!empty($_FILES['certificado_donaciones']['name'])) {
                $upload = media_handle_upload('certificado_donaciones', $post_id);
                if (!is_wp_error($upload)) {
                    $archivos_requeridos['certificado_donaciones'] = $upload;
                }
            }

            if (!empty($_FILES['rfc_archivo']['name'])) {
                $upload = media_handle_upload('rfc_archivo', $post_id);
                if (!is_wp_error($upload)) {
                    $archivos_requeridos['rfc_archivo'] = $upload;
                }
            }

            // Guardar archivos agrupados en ACF
            if (!empty($presentacion_institucional)) {
                update_field('presentacion_institucional', $presentacion_institucional, $post_id);
            }

            if (!empty($archivos_requeridos)) {
                update_field('archivos_requeridos', $archivos_requeridos, $post_id);
            }

            // Redirigir con éxito
            wp_redirect(add_query_arg('status', 'success'));
            exit;

        } else {
            wp_die('Error al registrar la institución.');
        }

    } else {
        wp_die('Nonce inválido.');
    }
}