<?php
namespace iflow\iflow\utilidades;
//error_reporting(0);
use iflow\iflow\Helper;
use iflow\iflow\conectores;
use iflow\iflow\Hooks;

use WC_Shipping_Method;


if (!defined('ABSPATH')) {
    exit; 
}
function add_method($methods)
{
    $methods['LV494'] = 'iflow\iflow\WC_iflow';
    return $methods;
}

function activate_plugin() {

    if (version_compare(PHP_VERSION, '7.0', '<')) {
        $flag = 'PHP';
        $version = '7.0';

    } else if (version_compare($wp_version, '4.9', '<')) {
        $flag = 'WordPress';
        $version = '4.9';

    } else {
        if (defined('IFLOW_API_USER') && 
            defined('IFLOW_API_KEY') && 
            defined('IFLOW_SERVICIO') && 
            !empty('IFLOW_API_USER') &&
            !empty('IFLOW_API_KEY') &&
            !empty('IFLOW_SERVICIO') ) {
                                        update_option('iflow_api_user', IFLOW_API_USER);
                                        update_option('iflow_api_key', IFLOW_API_KEY);
                                        update_option('iflow_api_servicio', IFLOW_SERVICIO);
                                    }
            

        if (!empty(get_option('iflow_api_user')) && !empty(get_option('iflow_api_key')) ) {
            
                update_option('iflow_validar_crendiales', true);
        }

// CREO LAS ZONAS DE ENVIO: 03/20204

        $delivery_zones = \WC_Shipping_Zones::get_zones();

        foreach ($delivery_zones as $zone_id => $zone_data ) {
            
            if ($zone_data['zone_name'] == 'Argentina' ) {
            
                $zone = \WC_Shipping_Zones::get_zone($zone_id);
                
                $methods = $zone->get_shipping_methods();

                

                foreach ($methods as $method) {
                    if ($method->id == 'LV494') {
                        return;
                    }
                }

                $zone->add_shipping_method('LV494');
                $zone->save();
                return;
            }
        }

 // CREO NUEVA ZONA DE ENVIO.03/2024

        $zone = new \WC_Shipping_Zone();
        
        if ($zone) {
            $zone->set_zone_name('Argentina');
            $zone->set_locations(Helper::get_shipping_zone_regions());
            $zone->add_shipping_method('LV494');
            $zone->save();
        }
        return;
    }

    function add_action_button($actions, $order)
    {
        $order_shipping_methods = $order->get_shipping_methods();
        $chosen_shipping_method = reset($order_shipping_methods);
        if (!$chosen_shipping_method) {
            return $actions;
        }
        $chosen_shipping_method_id = $chosen_shipping_method->get_method_id();
        $chosen_shipping_method = explode("|", $chosen_shipping_method_id);
        if ($chosen_shipping_method[0] === 'iflow') {
            $shipment_info = $order->get_meta('iflow_shipment', true);
            if ($shipment_info) {
                $shipment_info = unserialize($shipment_info);
                $actions['iflow-label'] = array(
                    'url' => 'https://www.iflow21.com/servicios/#ecommerce',
                    'name' => 'Obtener documentación Iflow',
                    'action' => 'Iflow-label',
                );
            }
        }
        return $actions;
    }

    function add_button_css_file($hook)
    {
        if ($hook !== 'edit.php') return;
        wp_enqueue_style('action-button.css', plugin_dir_url(__FILE__) . 'css/action-button.css', array(), 1.0);
    }
    deactivate_plugins(basename(__FILE__)); 
    wp_die('<p><strong>iFLOW </strong> Requiere al menos ' . $flag . ' version ' . $version . ' o mayor.</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => true));


}

function add_plugin_column_links($links)
{
    $links[] = '<a href="' .esc_url(get_admin_url(null, 'options-general.php?page=iflow_settings')) . '">Configurar</a>';
    return $links;
}


function add_plugin_description_links($meta, $file, $data, $status)
{
    if ($data['TextDomain'] == 'iFLOW_WOOCOMMERCE') {
        $meta[] = '<a href="' . esc_url('') . '">Guía de configuración</a>';
        $meta[] = '<a href="' . esc_url('') . '">Resolver problemas</a>';
    }
    return $meta;
}

function add_button_css_file($hook)
{
    if ($hook !== 'edit.php') return;
    wp_enqueue_style('action-button.css', plugin_dir_url(__FILE__) . 'css/action-button.css', array(), 1.0);
}

function iflow_add_free_shipping_label($label, $method)
{
    $label_tmp = explode(':', $label);
    if ($method->get_cost() == 0 && get_option('iflow_create_free_shipments') == 'yes') {
        $label = $label_tmp[0] . __(' - ¡Gratis!', 'woocommerce');
        // TODO: Agregar tiempo de entrega de opción ganadora al resultado de free shipping.
    }
    return $label;
}



function clear_cache()
{
    $packages = WC()->cart->get_shipping_packages();
    foreach ($packages as $key => $value) {
        $shipping_session = "shipping_for_package_$key";
        unset(WC()->session->$shipping_session);
    }
}


function update_order_meta($order_id)
{
  
    $order = wc_get_order($order_id);
    if (!$order) return false;

    //var_dump($order);exit;
    $chosen_shipping_method = WC()->session->get('chosen_shipping_methods');
    $chosen_shipping_method = reset($chosen_shipping_method);
    $chosen_shipping_method = explode("|", $chosen_shipping_method);
    $chosen_shipping_method[0] = explode(":", $chosen_shipping_method[0])[0];
    
    wc_get_logger()->info('ORDEN CREADA: '.$order_id .' Y ASIGNADA A : ' .json_encode($chosen_shipping_method, true) , unserialize(IFLOW_LOGGER_CONTEXT));
    
    wc_get_logger()->info('chosen_shipping_method[0] : ' .$chosen_shipping_method[0] , unserialize(IFLOW_LOGGER_CONTEXT));

   if ($chosen_shipping_method[0] === 'LV494' || ($chosen_shipping_method[0]=='free_shipping' && get_option('iflow_create_free_shipments') == 'yes')){
            $data = array();
    
            if ($chosen_shipping_method[0] === 'LV494') {

                $data['carrier_id'] = 'LV494';
                $data['service_type'] = get_option('IFLOW_SERVICIO');
            
                if (isset($chosen_shipping_method[3])) {
                    $data['logistic_type'] = get_option('IFLOW_SERVICIO');
                }
                
                
            } 
            else {
            
                $data['carrier_id'] = 'NO IFLOW';;
                $data['logistic_type'] = get_option('IFLOW_SERVICIO');
                
            }

            $order->update_meta_data('iflow_shipping_info', serialize($data));
            $order->save();

    wc_get_logger()->info('chosen_shipping_method[0] : ' .$data , unserialize(IFLOW_LOGGER_CONTEXT));

    
    } 
   

    }


    function process_order_status($order_id, $old_status, $new_status) {
      
    $order = wc_get_order($order_id);

      $order_shipping_methods = $order->get_items('shipping');

      $order_shipping_method = reset($order_shipping_methods);

      $shipment_creation_trigger_status = get_option('iflow_shipping_status');

      $user_api  = get_option('IFLOW_API_USER');
      $clave_api = get_option('IFLOW_API_KEY');

      $ord_id = $order->id;

      wc_get_logger()->info('Nuevo cambio de estado:'.$order->get_id(), unserialize(IFLOW_LOGGER_CONTEXT));
      
      wc_get_logger()->info('Parametro de iflow : '.get_option('IFLOW_SERVICIO'), unserialize(IFLOW_LOGGER_CONTEXT));

      wc_get_logger()->info('Method : '.$order_shipping_method->get_method_id(), unserialize(IFLOW_LOGGER_CONTEXT));

      wc_get_logger()->info('Iflow_shipping_info 2 : '.$order->get_meta('iflow_shipping_info', true), unserialize(IFLOW_LOGGER_CONTEXT));

      
      if (!$order || !$shipment_creation_trigger_status || !$order_shipping_method) return false;

      if (!in_array($order_shipping_method->get_method_id(), ['LV494','free_shipping'])) return false;

    
      
     if ($order->get_meta('iflow_shipping_info', true) && 

         in_array($shipment_creation_trigger_status, ['wc-'.$new_status, $new_status])) {

                wc_get_logger()->info('Creando la orden de envio:'.$order->get_id(), unserialize(IFLOW_LOGGER_CONTEXT));

             $serviceE     = get_option('IFLOW_SERVICIO'); 
       
            if ($serviceE == 'Fulfillment') {
                if ($old_status == 'posventa' && $new_status == 'completed') {
                    // enviar la orden al middleware fulfillmet
                    wc_get_logger()->info('Antes estaba en Fabricacion: '.$order->get_id(), unserialize(IFLOW_LOGGER_CONTEXT));
                    
                    $URL_TEST ="https://qa-tienda.iflow21.com/ds/mdl30/fulfillment.php?store_id=".$user_api."&order_id=".$ord_id;
                                
                    $URL_PROD ="https://tienda.iflow21.com/wo/mdl30/fulfillment.php?store_id=".$user_api."&order_id=".$ord_id; 
                
               

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                    CURLOPT_URL => $URL_PROD,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    ));

                    $response = curl_exec($curl);
                    wc_get_logger()->info('Respuesta desde el Middleware para Milonga: '.$response, unserialize(IFLOW_LOGGER_CONTEXT));

                    curl_close($curl);
               
                }
            }
        
            if ($serviceE == 'Puerta_a_Puerta') {
                // enviar la orden al middleware API
                $user_api  = get_option('IFLOW_API_USER');
                $clave_api = get_option('IFLOW_API_KEY');
                if ($new_status == 'completed') {

                    //-- ENVIO ORDEN A LA MIDDLEWARE --> API
                    wc_get_logger()->info('Envio La orden al servicio de API:'.$user_api .' -- ' .$clave_api, unserialize(IFLOW_LOGGER_CONTEXT));

                    $URL_TEST ="https://qa-tienda.iflow21.com/ds/mdl30/orders.php?store_id=".$user_api."&order_id=".$ord_id;
                    $URL_PROD ='https://tienda.iflow21.com/wo/mdl30/orders.php?store_id='.$user_api."&order_id=".$ord_id;
                    
                    $curl = curl_init();

                        curl_setopt_array($curl, array(
                        CURLOPT_URL => $URL_PROD,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        ));

                        $response = curl_exec($curl);
                        wc_get_logger()->info('Respueta del middleware api:  '.$response, unserialize(IFLOW_LOGGER_CONTEXT));

                        curl_close($curl);
                    
                    }

            }

            // ++ Encaso de sumas mas tipos de servicios agregar la logia aca
                 //if ($serviceE == '') {
                 // enviar la orden al middleware API
                 // }

 
        

    }
    wc_get_logger()->info('El pedido No esta asignado a IFLOW ', unserialize(IFLOW_LOGGER_CONTEXT));
    }


    function iflow_order_items_column( $order_columns ) {
            $order_columns['Iflow_Tracking'] = 'Pedido IFLOW';
            return $order_columns;
        }

    function iflow_order_items_column_cnt($colname) {
     
            global $the_order; // the global order object
            if( $colname == 'Iflow_Tracking' ) {
                global $post;
                $order = wc_get_order($post->ID);
                $shipment = $order->get_meta('iflow_tracking_id', true);
                echo $shipment;
            }
    }