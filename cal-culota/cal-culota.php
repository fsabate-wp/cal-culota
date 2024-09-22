<?php
/**
 * Plugin Name: Cal-Culota
 * Plugin URI: https://fernandosabate.com/cal-culota
 * Description: Plugin para mostrar opciones de pago en cuotas en productos de WooCommerce.
 * Version: 1.0.0
 * Author: Fer Sabaté
 * Author URI: https://fernandosabate.com
 * Text Domain: cal-culota
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar si WooCommerce está activo
function cal_culota_check_woocommerce() {
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        add_action('admin_notices', 'cal_culota_woocommerce_missing_notice');
        return false;
    }
    return true;
}

function cal_culota_woocommerce_missing_notice() {
    echo '<div class="error"><p>' . __('Cal-Culota requiere que WooCommerce esté instalado y activado.', 'cal-culota') . '</p></div>';
}

// Inicializar el plugin solo si WooCommerce está activo
if (cal_culota_check_woocommerce()) {
    // Incluir archivos necesarios
    require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
    require_once plugin_dir_path(__FILE__) . 'includes/frontend-display.php';

    // Activar el plugin
    register_activation_hook(__FILE__, 'cal_culota_activate');

    // Desactivar el plugin
    register_deactivation_hook(__FILE__, 'cal_culota_deactivate');

    // Agregar shortcode
    add_shortcode('cal_culota_tabla', 'cal_culota_display_installments_shortcode');
}

function cal_culota_activate() {
    $default_options = array(
        'show_calculation' => 0
    );
    
    if (!get_option('cal_culota_settings')) {
        add_option('cal_culota_settings', $default_options);
    }

    // Crear la tabla de opciones de cuota
    cal_culota_create_table();
}

function cal_culota_deactivate() {
    // Código de desactivación
}

function cal_culota_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cal_culota_options';
    
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nombre_medio_pago varchar(255) NOT NULL,
        cantidad_cuotas int NOT NULL,
        porcentaje_interes decimal(5,2) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}