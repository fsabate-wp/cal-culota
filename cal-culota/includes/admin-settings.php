<?php
// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Agregar menú de configuración
add_action('admin_menu', 'cal_culota_add_admin_menu');
function cal_culota_add_admin_menu() {
    add_menu_page(
        'Cal-Culota',
        'Cal-Culota',
        'manage_options',
        'cal-culota',
        'cal_culota_options_page',
        'dashicons-calculator',
        56
    );
}

// Registrar configuraciones
add_action('admin_init', 'cal_culota_settings_init');
function cal_culota_settings_init() {
    register_setting('cal_culota', 'cal_culota_settings');

    add_settings_section(
        'cal_culota_section',
        __('Configuración de Cal-Culota', 'cal-culota'),
        'cal_culota_section_callback',
        'cal-culota'
    );

    add_settings_field(
        'cal_culota_show_calculation',
        __('Mostrar cálculo de cuotas', 'cal-culota'),
        'cal_culota_show_calculation_render',
        'cal-culota',
        'cal_culota_section'
    );
}

function cal_culota_section_callback() {
    echo __('Configura las opciones de pago en cuotas', 'cal-culota');
}

function cal_culota_show_calculation_render() {
    $options = get_option('cal_culota_settings');
    $checked = isset($options['show_calculation']) && $options['show_calculation'] == 1 ? 'checked' : '';
    ?>
    <input type='checkbox' name='cal_culota_settings[show_calculation]' <?php echo $checked; ?> value='1'>
    <?php
}

// Página de opciones
function cal_culota_options_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cal_culota_options';

    // Manejar formulario de agregar/editar
    if (isset($_POST['cal_culota_submit'])) {
        if (!isset($_POST['cal_culota_nonce']) || !wp_verify_nonce($_POST['cal_culota_nonce'], 'cal_culota_add_edit')) {
            wp_die('Acción no autorizada');
        }

        $nombre_medio_pago = sanitize_text_field($_POST['nombre_medio_pago']);
        $cantidad_cuotas = intval($_POST['cantidad_cuotas']);
        $porcentaje_interes = floatval($_POST['porcentaje_interes']);

        if (isset($_POST['id'])) {
            // Editar opción existente
            $wpdb->update(
                $table_name,
                array(
                    'nombre_medio_pago' => $nombre_medio_pago,
                    'cantidad_cuotas' => $cantidad_cuotas,
                    'porcentaje_interes' => $porcentaje_interes
                ),
                array('id' => intval($_POST['id']))
            );
        } else {
            // Agregar nueva opción
            $wpdb->insert(
                $table_name,
                array(
                    'nombre_medio_pago' => $nombre_medio_pago,
                    'cantidad_cuotas' => $cantidad_cuotas,
                    'porcentaje_interes' => $porcentaje_interes
                )
            );
        }
    }

    // Manejar eliminación
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        if (!isset($_GET['cal_culota_nonce']) || !wp_verify_nonce($_GET['cal_culota_nonce'], 'cal_culota_delete')) {
            wp_die('Acción no autorizada');
        }

        $wpdb->delete($table_name, array('id' => intval($_GET['id'])));
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php settings_errors('cal_culota_messages'); ?>
        <form action='options.php' method='post'>
            <?php
            settings_fields('cal_culota');
            do_settings_sections('cal-culota');
            submit_button();
            ?>
        </form>

        <h2><?php _e('Cómo usar', 'cal-culota'); ?></h2>
        <p><?php _e('Para mostrar la tabla de cuotas en cualquier página o plantilla de producto, use el siguiente shortcode:', 'cal-culota'); ?></p>
        <code>[cal_culota_tabla]</code>

        <h2><?php _e('Opciones de cuota', 'cal-culota'); ?></h2>
        
        <?php
        // Mostrar tabla de opciones existentes
        $opciones = $wpdb->get_results("SELECT * FROM $table_name");
        if ($opciones) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Medio de Pago</th><th>Cantidad de Cuotas</th><th>Porcentaje de Interés</th><th>Acciones</th></tr></thead>';
            echo '<tbody>';
            foreach ($opciones as $opcion) {
                echo '<tr>';
                echo '<td>' . esc_html($opcion->nombre_medio_pago) . '</td>';
                echo '<td>' . esc_html($opcion->cantidad_cuotas) . '</td>';
                echo '<td>' . esc_html($opcion->porcentaje_interes) . '%</td>';
                echo '<td>';
                echo '<a href="?page=cal-culota&action=edit&id=' . $opcion->id . '">Editar</a> | ';
                echo '<a href="?page=cal-culota&action=delete&id=' . $opcion->id . '&cal_culota_nonce=' . wp_create_nonce('cal_culota_delete') . '" onclick="return confirm(\'¿Estás seguro de que quieres eliminar esta opción?\')">Eliminar</a>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No hay opciones de cuota configuradas.</p>';
        }
        ?>

        <h3><?php echo isset($_GET['action']) && $_GET['action'] == 'edit' ? 'Editar' : 'Agregar'; ?> opción de cuota</h3>
        <form method="post">
            <?php wp_nonce_field('cal_culota_add_edit', 'cal_culota_nonce'); ?>
            <?php
            if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
                $opcion = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['id'])));
                echo '<input type="hidden" name="id" value="' . $opcion->id . '">';
            }
            ?>
            <table class="form-table">
                <tr>
                    <th><label for="nombre_medio_pago">Nombre del Medio de Pago</label></th>
                    <td><input type="text" name="nombre_medio_pago" id="nombre_medio_pago" value="<?php echo isset($opcion) ? esc_attr($opcion->nombre_medio_pago) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="cantidad_cuotas">Cantidad de Cuotas</label></th>
                    <td><input type="number" name="cantidad_cuotas" id="cantidad_cuotas" value="<?php echo isset($opcion) ? esc_attr($opcion->cantidad_cuotas) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="porcentaje_interes">Porcentaje de Interés</label></th>
                    <td><input type="number" step="0.01" name="porcentaje_interes" id="porcentaje_interes" value="<?php echo isset($opcion) ? esc_attr($opcion->porcentaje_interes) : ''; ?>" required></td>
                </tr>
            </table>
            <?php submit_button('Guardar Opción', 'primary', 'cal_culota_submit'); ?>
        </form>
    </div>
    <?php
}