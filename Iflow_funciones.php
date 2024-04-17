<?php
namespace iflow\iflow\iflow_funciones;

use iflow\iflow\Helper;
use iflow\iflow\conectores;
use iflow\iflow\Hooks;

use WC_Shipping_Method;


if (!defined('ABSPATH')) {
    exit; 
}
/**
 * Registramos un nuevo estado de pedido de WooCommerce
 * Estado de pedido "En Fabricacion"
 */
function registro_posventa_estado_pedido() {
    register_post_status( 'wc-posventa', array(
        'label'                     => 'En Fabricación', //Nombre público
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'En Fabricación (%s)', 'En Fabricación (%s)' )
    ) );
}




/**
 * Añadimos el estado "posventa" a la lista de estados que puede tener un pedido
 * Lo colocamos a continuación del estado "completado", pues sería su situación lógica
 */

function anadir_posventa_lista( $order_statuses ) {
    $new_order_statuses = array();
    
    foreach ( $order_statuses as $key => $status ) {
        $new_order_statuses[ $key ] = $status;
        if ( 'wc-completed' === $key ) {
            $new_order_statuses['wc-posventa'] = 'En Fabricación';
        }
    }
    return $new_order_statuses;
}