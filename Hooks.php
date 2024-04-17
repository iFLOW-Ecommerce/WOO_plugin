<?php

if (!defined('ABSPATH')) {
	exit;   // Exit if accessed directly.
}

// --- Configuraciones
add_action('admin_init', 'iflow\iflow\Configuraciones\init_settings');
add_action('admin_menu', 'iflow\iflow\Configuraciones\create_menu_option');

// --- Metodos
add_action('woocommerce_shipping_init', 'iflow\iflow\iflow_init');
add_filter('woocommerce_shipping_methods', 'iflow\iflow\Utilidades\add_method');

// --- Validaciones
add_action('woocommerce_checkout_update_order_meta', 'iflow\iflow\Utilidades\update_order_meta');
add_filter('woocommerce_cart_shipping_method_full_label', 'iflow\iflow\Utilidades\iflow_add_free_shipping_label', 10, 2);
add_filter('woocommerce_checkout_update_order_review', 'iflow\iflow\Utilidades\clear_cache');

// --- Ordenes
add_action('woocommerce_order_status_changed', 'iflow\iflow\Utilidades\process_order_status', 10, 3);

// --- Funcionalidades del Plugin

add_action( 'woocommerce_flat_rate_shipping_add_rate', array( "30", 'calculate_extra_shipping' ), 10, 2 );

// Fucionalidades exclusivas de IFLOW SA
	add_action( 'init', 'iflow\iflow\iflow_funciones\registro_posventa_estado_pedido' );
	add_filter( 'wc_order_statuses', 'iflow\iflow\iflow_funciones\anadir_posventa_lista' );
	add_filter('manage_edit-shop_order_columns', 'iflow\iflow\Utilidades\iflow_order_items_column',20 );          //Agrego la columna de estado del pedido.
	add_action( 'manage_shop_order_posts_custom_column' , 'iflow\iflow\Utilidades\iflow_order_items_column_cnt' );//Agrego la columna de estado del pedido.

	

// Compatibilidad con plugin Woocommerce Shipping Calculator On Product Page (Magerips)
add_filter( 'woocommerce_shipping_calculator_enable_city', '__return_true');