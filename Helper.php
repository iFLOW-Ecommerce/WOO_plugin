<?php


namespace iflow\iflow; 



if (!defined('ABSPATH')) {
    exit; 
}

class Helper
{
    private $order, $logger;

    public function __construct($order = '')
    {
        $this->order = $order;
        $this->logger = wc_get_logger();
    }


    public static function get_state_name($state_id = '')
    {
        $states = [
            'C' => 'Capital Federal',
            'B' => 'Buenos Aires',
            'K' => 'Catamarca',
            'H' => 'Chaco',
            'U' => 'Chubut',
            'X' => 'Cordoba',
            'W' => 'Corrientes',
            'E' => 'Entre Rios',
            'P' => 'Formosa',
            'Y' => 'Jujuy',
            'L' => 'La Pampa',
            'F' => 'La Rioja',
            'M' => 'Mendoza',
            'N' => 'Misiones',
            'Q' => 'Neuquen',
            'R' => 'Rio Negro',
            'A' => 'Salta',
            'J' => 'San Juan',
            'D' => 'San Luis',
            'Z' => 'Santa Cruz',
            'S' => 'Santa Fe',
            'G' => 'Santiago del Estero',
            'V' => 'Tierra del Fuego',
            'T' => 'Tucuman',
        ];

        if (isset($states[$state_id])) {
            return $states[$state_id];
        }
        
        return null;
    }


    public static function get_shipping_zone_regions()
        {
            $regions = array();
            $regions[] = array('code' => 'AR:C', 'type' => 'state');
            $regions[] = array('code' => 'AR:B', 'type' => 'state');
            $regions[] = array('code' => 'AR:K', 'type' => 'state');
            $regions[] = array('code' => 'AR:H', 'type' => 'state');
            $regions[] = array('code' => 'AR:U', 'type' => 'state');
            $regions[] = array('code' => 'AR:X', 'type' => 'state');
            $regions[] = array('code' => 'AR:W', 'type' => 'state');
            $regions[] = array('code' => 'AR:E', 'type' => 'state');
            $regions[] = array('code' => 'AR:P', 'type' => 'state');
            $regions[] = array('code' => 'AR:Y', 'type' => 'state');
            $regions[] = array('code' => 'AR:L', 'type' => 'state');
            $regions[] = array('code' => 'AR:F', 'type' => 'state');
            $regions[] = array('code' => 'AR:M', 'type' => 'state');
            $regions[] = array('code' => 'AR:N', 'type' => 'state');
            $regions[] = array('code' => 'AR:Q', 'type' => 'state');
            $regions[] = array('code' => 'AR:R', 'type' => 'state');
            $regions[] = array('code' => 'AR:A', 'type' => 'state');
            $regions[] = array('code' => 'AR:J', 'type' => 'state');
            $regions[] = array('code' => 'AR:D', 'type' => 'state');
            $regions[] = array('code' => 'AR:Z', 'type' => 'state');
            $regions[] = array('code' => 'AR:S', 'type' => 'state');
            $regions[] = array('code' => 'AR:G', 'type' => 'state');
            $regions[] = array('code' => 'AR:V', 'type' => 'state');
            $regions[] = array('code' => 'AR:T', 'type' => 'state');
            return $regions;
        }
    


        private function get_product_dimensions($product_id)
        {
            $product = wc_get_product($product_id);
            if (!$product) {
                return false;
            }
    
            if (!$product->needs_shipping()) {
                return null;
            }
    
            if (empty($product->get_height()) || empty($product->get_length()) || empty($product->get_width()) || !$product->has_weight()) {
                return false;
            }
            
            $new_product = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'sku' => $product->get_sku(),
                'height' => ceil($product->get_height() ? wc_get_dimension($product->get_height(), 'cm') : '0'),
                'width' => ceil($product->get_width() ? wc_get_dimension($product->get_width(), 'cm') : '0'),
                'length' => ceil($product->get_length() ? wc_get_dimension($product->get_length(), 'cm') : '0'),
                'weight' => ceil($product->has_weight() ? wc_get_weight($product->get_weight(), 'kg')*1000 : '0'),
            );
            return $new_product;
        }

        

        public function get_items_from_cart()
        {
            $products = array(
                'products' => array(),
                'shipping_info' => array()
            );
    
            $items = WC()->cart->get_cart();
    
            foreach ($items as $item) {
                $product_id = $item['data']->get_id();
                $product = $this->get_product_dimensions($product_id);
                if (is_null($product)) {
                    // product is a virtual product or does not need shipping
                    continue;
                }
                if (!$product) {
                    $this->logger->error('iflow Helper: Error obteniendo productos del carrito, producto con malas dimensiones - ID: ' . $product_id, unserialize(IFLOW_LOGGER_CONTEXT));
                    return false;
                }
                for ($i = 0; $i < $item['quantity']; $i++) {
                    array_push($products['products'], $product);
                }
            }
    
            $packages = $this->get_packages_from_products($products);
    
            if (!$packages) {
                $this->logger->error('iflow Helper: Error obteniendo productos del carrito, productos con malas dimensiones/peso', unserialize(IFLOW_LOGGER_CONTEXT));
                return false;
            }
    
            return $packages;
        }
        private function get_packages_from_products($products)
        {
            $products['shipping_info']['total_weight'] = 0;
            $products['shipping_info']['total_volume'] = 0;
            $products['items'] = array();
            $products['packages'] = array();
    
            $skus = array();
    
            foreach ($products['products'] as $index => $product) {
                // $product is https://docs.woocommerce.com/wc-apidocs/class-WC_Product.html
                $sku = $product['sku'];
                if (empty($sku)) {
                    $sku = 'wc'.$product['id'];
                }
    
                $products['shipping_info']['total_weight'] += $product['weight'];
                $products['shipping_info']['total_volume'] += $product['height'] * $product['width'] * $product['length'];
                $skus[] = $sku;
    
                $products['items'][] = array(
                    'weight' => intval(ceil($product['weight'])),
                    'height' => intval(ceil($product['height'])),
                    'width' => intval(ceil($product['width'])),
                    'length' => intval(ceil($product['length'])),
                    'sku' => substr($sku,0,60),
                );
    
                // One package per unit of product sold
                if (get_option('iflow_packaging_mode') != 'grouped') {
                    $products['packages'][] = array(
                        'classification_id' => 1,
                        'weight' => intval(ceil($product['weight'])),
                        'height' => intval(ceil($product['height'])),
                        'width' => intval(ceil($product['width'])),
                        'length' => intval(ceil($product['length'])),
                        'description_1' => substr($sku,0,60),
                        'description_2' => substr($product['name'],0,60)
                    );
                }
            }
    
            // One package grouping all products
            if (get_option('iflow_packaging_mode') == 'grouped') {
                $side = intval(ceil(pow($products['shipping_info']['total_volume'],1/3)));
                $products['packages'][] = array(
                    'classification_id' => 1,
                    'weight' => intval(ceil($products['shipping_info']['total_weight'])),
                    'height' => $side,
                    'width' => $side,
                    'length' => $side,
                    'description_1' => substr(implode('_',$skus),0,60),
                );
            }
    
            return $products;
    
        }

        
        
    
    
    }