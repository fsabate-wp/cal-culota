<?php
// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Agregar estilos CSS para el frontend
add_action('wp_head', 'cal_culota_frontend_styles');
function cal_culota_frontend_styles() {
    echo '<style>
        .cal-culota-installments {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
        }
        .cal-culota-installments th,
        .cal-culota-installments td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .cal-culota-installments th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
    </style>';
}

// Crear shortcode para mostrar la tabla de cuotas
add_shortcode('cal_culota_tabla', 'cal_culota_display_installments_shortcode');
function cal_culota_display_installments_shortcode($atts) {
    $options = get_option('cal_culota_settings');
    if (!isset($options['show_calculation']) || $options['show_calculation'] != 1) {
        return '';
    }

    global $product;
    if (!$product) {
        $product = wc_get_product(get_the_ID());
    }
    if (!$product) {
        return '';
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'cal_culota_options';
    $price = $product->get_price();

    $installment_options = $wpdb->get_results("SELECT * FROM $table_name");

    if (!$installment_options) {
        return '';
    }

    ob_start();

    echo '<h3>' . __('Opciones de pago en cuotas', 'cal-culota') . '</h3>';
    echo '<table class="cal-culota-installments">';
    echo '<tr><th>' . __('Medio de pago', 'cal-culota') . '</th><th>' . __('Cuotas', 'cal-culota') . '</th><th>' . __('Valor de cuota', 'cal-culota') . '</th></tr>';

    foreach ($installment_options as $option) {
        $installment_price = $price * (1 + $option->porcentaje_interes / 100) / $option->cantidad_cuotas;
        echo '<tr>';
        echo '<td>' . esc_html($option->nombre_medio_pago) . '</td>';
        echo '<td>' . esc_html($option->cantidad_cuotas) . '</td>';
        echo '<td>' . wc_price($installment_price) . '</td>';
        echo '</tr>';
    }

    echo '</table>';
    ob_end_flush();
}