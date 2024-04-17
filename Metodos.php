<?php
namespace iflow\iflow;

use WC_Shipping_Method;

use iflow\iflow\conectores;
use iflow\iflow\iflow_funciones;
use iflow\iflow\Utilidades;

if (!defined('ABSPATH')) {
    exit; 
}

function iflow_init()
{
    if (!class_exists('WC_iflow')) { 
        class WC_iflow extends WC_Shipping_Method
        {
            private $logger;

            public function __construct($instance_id = 0)
            {
                $this->id = 'LV494';
                $this->method_title = 'iFLOW';
                $this->method_description = 'Envíos con IFLOW eCOMMERCE para Argentina';
                $this->title = 'Envío con IFLOW SA';
                $this->instance_id = absint($instance_id);
                $this->supports = array(
                    'shipping-zones',
                    'instance-settings',
                    'instance-settings-modal'
                );
                $this->logger = wc_get_logger();
                $this->init();

                add_action('woocommerce_update_options_shipping_iflow', array($this, 'process_admin_options'));
            }

            function init()
            {
                $this->form_fields = array();
                $this->instance_form_fields = array(
                    'service_types' => array(
                        'title' => __('Tipos de Servicio Habilitados', 'woocommerce'),
                        'description'=>'Selecciona los tipos de servicio que quieras ofrecer. Selecciona múltiples opciones manteniendo la tecla CTRL.',
                        'type' => 'multiselect',
                        'default' => array('Fulfillment','Pickup-Pont','Send-day','Tradicional'),
                        'options' => array(
                            'Fulfillment' => 'Fulfillment - Entrega a domicilio',
                            'Pickup-Pont' => 'Puntos de Retiro habilitados',
                            'Send-day' => 'Entrega en 24Hs',
                            'Tradicional' => 'Servicio de Colecta y Distribucion estandar'
                             )
                    ),
                );
                
            }
    
            public function get_products_from_cart()
            {
                $helper = new Helper();
                $products = $helper->get_items_from_cart();
                return $products;
            }

             public function calculate_shipping($package = array())
             {
                $products = $this->get_products_from_cart();
                $costo_ord  = WC()->cart->get_subtotal();
                $total_Peso = $produtos["shipping_info"]["total_weight"]; 
             
                $max_ancho  = 0;
                $max_Alto   = 0;
                $max_largo  = 0;
                
                foreach ($products["items"] as $key1 => $items) { 
                    
					if ($max_ancho < $items["width"] ) { $max_ancho = $items["width"]; } 

                    if  ($max_Alto < $items["height"] ) {$max_Alto = $items["height"];}

                    if  ($max_Alto < $items["length"] ) {$max_largo = $items["length"];}
                    
                }

                if ($max_ancho == 0 )  { $max_ancho = 1; } 
                if ($max_Alto == 0 )   { $max_Alto = 1;  } 
                if ($max_largo == 0 )  { $max_largo = 1; } 
                if ($total_Peso == 0 ) { $total_Peso = 1;}     
                
                $shipping_address = [
                    'city' => WC()->customer->get_shipping_city(),
                    'state' => WC()->customer->get_shipping_state(),
                    'zipcode' => WC()->customer->get_shipping_postcode()
                ];

                $billing_address = [
                    'city' => WC()->customer->get_billing_city(),
                    'state' => WC()->customer->get_billing_state(),
                    'zipcode' => WC()->customer->get_billing_postcode()
                ];


                if (!empty($shipping_address['city']) && !empty($shipping_address['state']) && !empty($shipping_address['zipcode'])) {
                    $destination = $shipping_address;
                } else {
                    $destination = $billing_address;
                }

                $destination['zipcode'] = filter_var($destination['zipcode'], FILTER_SANITIZE_NUMBER_INT);
                $destination['state'] = Helper::get_state_name($destination['state']);
                $codigo_postal = $destination['zipcode'];
                $provincia = $destination['state'] ;

                $cotizar["packages"][0]["width"]= $max_ancho;
                $cotizar["packages"][0]["height"]=$max_Alto;
                $cotizar["packages"][0]["length"]=$max_largo;
                $cotizar["packages"][0]["real_weight"]=$total_Peso;
                $cotizar["packages"][0]["gross_price"]=$costo_ord;
                $cotizar["zip_code"]=$codigo_postal;
                $cotizar["province"]=$provincia;
                $cotizar["delivery_mode"]= 1;
            

                $COTjson = json_encode($cotizar);
                
                $user_api  = get_option('IFLOW_API_USER');
                $clave_api = get_option('IFLOW_API_KEY');
                wc_get_logger()->info('DATOS ENVIADOS A COTIZAR: '.$COTjson, unserialize(IFLOW_LOGGER_CONTEXT));
               
           
                // Obtengo el Token de la API para cotizar.

                //$URL = ("http://test-api.iflow21.com/api/login");
                $URL = ("http://api.iflow21.com/api/login");

                    $curl = curl_init();
                                        curl_setopt_array($curl, array(
                                        CURLOPT_URL => "$URL",
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_ENCODING => "",
                                        CURLOPT_MAXREDIRS => 10,
                                        CURLOPT_TIMEOUT => 0,
                                        CURLOPT_FOLLOWLOCATION => true,
                                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                        CURLOPT_CUSTOMREQUEST => "POST",
                                        CURLOPT_POSTFIELDS =>"{\"_username\":\"$user_api\",\"_password\":\"$clave_api\"}",
                                        CURLOPT_HTTPHEADER => array(
                                            "Content-Type: application/json",
                                            "Cookie: SERVERID=api_iflow21"
                                        ),
                                        ));

                                        $response = curl_exec($curl);
                                        curl_close($curl);
                                        $arraypsd = json_decode($response,true);
                                    
                                        $token_api= $arraypsd["token"];

                                        
                        /// Envio a la API los datos de la orden para obtener la cotizacion.

                        //$URL = ("http://test-api.iflow21.com/api/rate");
                        $URL = ("http://api.iflow21.com/api/rate");
                        $curl = curl_init();
                                    curl_setopt_array($curl, array(
                                        CURLOPT_URL => "$URL",
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_ENCODING => "",
                                        CURLOPT_MAXREDIRS => 10,
                                        CURLOPT_TIMEOUT => 0,
                                        CURLOPT_FOLLOWLOCATION => true,
                                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                        CURLOPT_CUSTOMREQUEST => "POST",
                                        CURLOPT_POSTFIELDS =>$COTjson,
                                        CURLOPT_HTTPHEADER => array(
                                        "Content-Type: application/json",
                                        "Authorization: Bearer $token_api",
                                        "Cookie: SERVERID=api_iflow21"
                                    ),
                                        ));

                                    $response = curl_exec($curl);
            
                                    wc_get_logger()->info('RESPUESTA DEL COTIZADOR: '.$response, unserialize(IFLOW_LOGGER_CONTEXT));
            
                                    curl_close($curl);
            
                                    $cotizado = json_decode($response, true);

                                    
                    // Inicio la evaluacion para mostrar la cotizacion del envio

                                 if (!empty(get_option('iflow_additional_charge', 0))) {
                                        $additional_charge = true;
                                    } else {
                                        $additional_charge = false;
                                    }
                                 $use_free_shipping = false;
                                 
                                 if (get_option('iflow_free_shipping_threshold')) {
                                        if (WC()->cart->get_subtotal() >= floatval(get_option('iflow_free_shipping_threshold'))) {
                                            $use_free_shipping = true;
                                        }
                                        
                                        if (!empty(get_option('iflow_additional_charge', 0))) {
                                            $additional_charge = true;
                                        } else {
                                            $additional_charge = false;
                                        }
                                 }
                    $cost = (isset($cotizado["results"]["final_value"])? $cotizado["results"]["final_value"] : get_option('IFLOW_COST_OFFLINE'));

                    wc_get_logger()->info('COTIZACION FINAL EN PESO: '.$cost, unserialize(IFLOW_LOGGER_CONTEXT));
                    
                    if ($use_free_shipping) {
                                $cost = 0;
                    } elseif ($additional_charge){
                                                            $operator = get_option('iflow_additional_charge_operation', 'add');
                                                            if ($operator == 'sub') {
                                                                $sign = -1;
                                                            } else {
                                                                $sign = 1;
                                                            }

                                                            if (get_option('iflow_additional_charge_type', 'rel') == 'abs') {
                                                                // Cambio en valor absoluto
                                                                $cost = $cost + get_option('iflow_additional_charge', 0) * $sign;

                                                            } else {
                                                                // Cambio en valor relativo
                                                                $cost = $cost + $cost * get_option('iflow_additional_charge', 0)/100 * $sign;
                                                            }

                                $cost = max(0, $cost);
                              }
                    // Armado de rates
                $rate = array(
                                    'id' => 'LV494',
                                    'label' => 'Delivery Service iFLOW S.A.', 
                                    'cost' => $cost,
                                    'calc_tax' => 'per_order'
                                );
                                 
                            $this->add_rate($rate);
                            return;
    
            }

        }

    }
}


