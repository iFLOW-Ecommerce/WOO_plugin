<?php
/*
 * Plugin Name: Envíos con iFLOW eCOMMERCE SA.
 * Plugin URI: https://ecommerce.iflow21.com/
 * Description: Integra WooCommerce con iFLOW SA para realizar envíos a todo el país.
 * Version: 3.5.1
 * Author: Zeus Solutions
 * Author URI: https://www.zeussolutions.com.ar/
 * Requires PHP: 7
 * License: GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 6.4.0
 * WC tested up to: 8.7.2
 * Text Domain: iFLOW eCOMMERCE
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
require_once 'Helper.php';
require_once 'Hooks.php';
require_once 'Utilidades.php';
require_once 'Configuraciones.php';
require_once 'Metodos.php';
require_once 'Utilidades.php';

//Funciones especiales para IFLOW
require_once 'Iflow_funciones.php';


// VARIABLES GLOBALES
define ('IFLOW_API_USER',' ');
define ('IFLOW_API_KEY',' ');
define ('IFLOW_SERVICIO',' ');
define ('IFLOW_COST_OFFLINE','500');
define ('IFLOW_VERSION','3.5.1');

define ('IFLOW_LOGGER_CONTEXT',serialize(array('source'=>'IFLOW')));



//INSTALACION DEL PLUGIN

register_activation_hook(__FILE__, 'iFLOW\iFLOW\Utilidades\activar_plugin');


//Aca creo el menu de iFLOW eCOMMERCE

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'iflow\iflow\Utilidades\add_plugin_column_links');
add_filter('plugin_row_meta', 'Iflow\iflow\Utilidades\add_plugin_description_links', 10, 4);

//
