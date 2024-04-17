<?php

namespace iflow\iflow\configuraciones;

use iflow\iflow\conectores;


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


function init_settings()
{
        register_setting('iflow_main_section', 'iflow_other_options');

        // Creo las variables para los campos configurables.

            add_settings_section(
                'iflow_main_section',
                'Configuración',
                __NAMESPACE__ . '\print_instructions',
                'iflow_settings'
            );


            add_settings_field(
                'api_user',
                'Usuario de API',
                __NAMESPACE__ . '\print_api_user',
                'iflow_settings',
                'iflow_main_section'
            );

            add_settings_field(
                'api_key',
                'Contraseña API',
                __NAMESPACE__ . '\print_api_key',
                'iflow_settings',
                'iflow_main_section'
            );

            add_settings_field(
                'api_servicio',
                'Tipo de Servicio',
                __NAMESPACE__ . '\print_api_servicio',
                'iflow_settings',
                'iflow_main_section'
            );


            add_settings_field(
                'default_shipping_status',
                'Estado en el que debe solicitar la creacion del Pedido',
                __NAMESPACE__ . '\print_default_shipping_status',
                'iflow_settings',
                'iflow_main_section'
    );
            add_settings_field(
                    'enable_free_shipping_creation',
                    'Crear los envíos gratuitos con iflow',
                    __NAMESPACE__ . '\print_free_shipping_creation',
                    'iflow_settings',
                    'iflow_main_section'
                );
            
            add_settings_field(
                    'free_shipping_threshold',
                    'Ofrecer envío gratis según valor de orden',
                    __NAMESPACE__ . '\print_free_shipping_threshold',
                    'iflow_settings',
                    'iflow_main_section'
                );  
                
            add_settings_field(
                    'additional_charge',
                    'Modificar el precio del envío',
                    __NAMESPACE__ . '\print_additional_charge',
                    'iflow_settings',
                    'iflow_main_section'
                );
      
            add_settings_field(
                    'servicetype_instructions',
                    'Tipos de servicio habilitados',
                    __NAMESPACE__ . '\print_servicetype_instructions',
                    'iflow_settings',
                    'iflow_main_section'
                );
                
            add_settings_field(
                    'servicetype_box',
                    'Box de Envios',
                    __NAMESPACE__ . '\print_servicetype_box',
                    'iflow_settings',
                    'iflow_main_section'
                );

}

// Creo los campos en el administrador de IFLOW.

function print_instructions()
{
    echo '<p>Para continuar deberás crear credenciales de API.</p><p>Deberás crear una <b>Cuenta<b> desde la sección de <a href="https://registro.iflow21.com/register" target="_blank">Registrarse</a>.</p> ';
}

function print_api_user()
{
    
    $previous_config = get_option('IFLOW_API_USER');
    echo '<input type="text" required name="api_user" value="' . ($previous_config ? $previous_config : '') . '" />';

   
}

function print_api_key()
{
    $previous_config = get_option('IFLOW_API_KEY');
    echo '<input type="password" required name="api_key" value="' . ($previous_config ? $previous_config : '') . '" />';
}

function print_api_servicio()
{
    $previous_config = get_option('IFLOW_SERVICIO');
     echo '<p><label><input type="radio" required name="api_servicio" value="Puerta_a_Puerta"'.($previous_config=='Puerta_a_Puerta' || empty($previous_config) ? ' checked':'').'>Puerta a Puerta</label></p>';
     echo '<p><label><input type="radio" required name="api_servicio" value="Fulfillment"'.($previous_config=='Fulfillment' || empty($previous_config) ? ' checked':'').'> Fulfillment</label></p>';

}


function print_default_shipping_status()
{
    $statuses = wc_get_order_statuses();
    $previous_config = get_option('iflow_shipping_status');
    if (!$previous_config) update_option('iflow_shipping_status', 'wc-completed');
    echo '<select name="shipping_status">';

    foreach ($statuses as $status_key => $status_name) {
        if ($previous_config) {
            echo '<option value="' . $status_key . '" ' . ($previous_config === $status_key ? 'selected' : '') . '>' . $status_name . '</option>';
        } else {
            echo '<option value="' . $status_key . '" ' . ($status_key === 'wc-completed' ? 'selected' : '') . '>' . $status_name . '</option>';
        }
    }
    echo '</select>';
    echo '<div class="help-box info-text">Los pedidos con este estado serán enviados automáticamente atravez de iFLOW SA</div>';
}

function print_free_shipping_creation()
{
    $previous_config = get_option('iflow_create_free_shipments');
    echo '<p><label><input type="radio" name="enable_free_shipping_creation" value="yes"'.($previous_config=='yes' ? ' checked':'').'> Si</label> ';
    echo '&nbsp;&nbsp;&nbsp;&nbsp;<label><input type="radio" name="enable_free_shipping_creation" value="no"'.($previous_config=='no' || empty($previous_config) ? ' checked':'').'> No</label></p>';
    echo '<div class="help-box info-text">Si habilitas esta opción, los envíos con Envío gratis se crearán en iflow.  <br>
<small>Aprende <a href="https://docs.woocommerce.com/document/free-shipping/" target="_blank">cómo configurar envío gratis en Woocomerce</a> .</small>
</div>';
}
function print_free_shipping_threshold()
{
    $previous_config = get_option('iflow_free_shipping_threshold');
    echo '<input type="number" name="free_shipping_threshold" value="' . ($previous_config ? $previous_config : '') . '" />
	<div class="help-box info-text">Hacer que el precio del envío se muestre como gratis si el total de la orden es igual o mayor al valor indicado. Dejar en blanco para desactivar opción.</div>';
}

function print_additional_charge(){
	$previous_config = get_option('iflow_additional_charge_operation', 'add');
    echo '<select name="additional_charge_operation">';
    echo '<option value="add" ' . ($previous_config === 'add' ? 'selected' : '') . '>Sumar (+)</option>';
    echo '<option value="sub" ' . ($previous_config === 'sub' ? 'selected' : '') . '>Restar (-)</option>';
    echo '</select>';

    $previous_config = get_option('iflow_additional_charge', '0');
    echo '<br><input type="number" required name="additional_charge" min="0" value="' . (isset($previous_config) ? $previous_config : 0) . '" />';

    $previous_config = get_option('iflow_additional_charge_type','rel');
    echo '<br><select name="additional_charge_type">';
    echo '<option value="rel" ' . ($previous_config === 'rel' ? 'selected' : '') . '>%</option>';
    echo '<option value="abs" ' . ($previous_config === 'abs' ? 'selected' : '') . '>$</option>';
    echo '</select>';

    echo '<div class="help-box info-text">Usa esta opción si quieres cobrarle un precio de envío distinto tu cliente. iflow te cobrará el precio real que corresponda para el envío.</div>';

}

function print_servicetype_instructions()
{
    echo '<div class="help-box info-text">Para gestionar los tipos de servicio habilitados ingresa a los <a href="admin.php?page=wc-settings&tab=shipping" target="_blank">ajustes de envío de WooCommerce</a>, ingresa a la zona que quieras modificar, y en el metodo de envío <b>Envío con iflow</b> (clic en Editar) selecciona los tipos de servicio que quieras habilitar.</div>';
}

function print_servicetype_box()
{
    echo '<div class="help-box info-text">
          Por el momento Iflow cuanta son solo un tamaño maximo de box,
          El box no podra ser modificado y se calculara de forma dinamica tomando 
          como parametros de calculos los datos volumetricos de sus productos.
          </div>';
}

function create_menu_option()
{
    add_menu_page(
        'Integracion con iFLOW SA',
        'Envíos con iflow',
        'manage_woocommerce',
        'iflow_settings',
        __NAMESPACE__ . '\settings_page_content',
		'dashicons-store'
    );
}


function settings_page_content() {

   if (!current_user_can('manage_woocommerce')){
       return;
   }
       
    // Save api_user
    if (isset($_POST['api_user'])) {
        wp_verify_nonce($_REQUEST['iflow_wpnonce'], 'iflow_settings_save' );
        update_option('IFLOW_API_USER', sanitize_text_field($_POST['api_user']));
    }

    // Save api_key
    if (isset($_POST['api_key'])) {
        wp_verify_nonce($_REQUEST['iflow_wpnonce'], 'iflow_settings_save' );
        update_option('IFLOW_API_KEY', sanitize_text_field($_POST['api_key']));
    }

    // Save api_service
    if (isset($_POST['api_servicio'])) {
        wp_verify_nonce($_REQUEST['iflow_wpnonce'], 'iflow_settings_save' );
        update_option('IFLOW_SERVICIO', sanitize_text_field($_POST['api_servicio']));
    }

   // Save shipping status
   if (isset($_POST['shipping_status'])) {
        wp_verify_nonce($_REQUEST['iflow_wpnonce'], 'iflow_settings_save' );
        update_option('iflow_shipping_status', sanitize_text_field($_POST['shipping_status']));
    }

   // Save shipping status
   if (isset($_POST['enable_free_shipping_creation'])) {
        wp_verify_nonce($_REQUEST['iflow_wpnonce'], 'iflow_settings_save' );
        update_option('iflow_create_free_shipments', sanitize_text_field($_POST['enable_free_shipping_creation']));
    }

   // aditional charge

    if (isset($_POST['additional_charge'])) {
		wp_verify_nonce($_REQUEST['iflow_wpnonce'], 'iflow_settings_save' );
        update_option('iflow_additional_charge', filter_var($_POST['additional_charge'],FILTER_SANITIZE_NUMBER_INT));
    }

    if (isset($_POST['additional_charge_type'])) {
        wp_verify_nonce($_REQUEST['iflow_wpnonce'], 'iflow_settings_save' );
        update_option('iflow_additional_charge_type', sanitize_text_field($_POST['additional_charge_type']));
    }

    if (isset($_POST['additional_charge_operation'])) {
        wp_verify_nonce($_REQUEST['iflow_wpnonce'], 'iflow_settings_save' );
        update_option('iflow_additional_charge_operation', sanitize_text_field($_POST['additional_charge_operation']));
    }


    if (isset($_POST['free_shipping_threshold'])) {
        wp_verify_nonce($_REQUEST['iflow_wpnonce'], 'iflow_settings_save' );
        update_option('iflow_free_shipping_threshold', filter_var($_POST['free_shipping_threshold'],FILTER_SANITIZE_NUMBER_FLOAT));
    }



    

    ?>

	<div class="wrap">
        <img src="<?=plugin_dir_url(__FILE__) ?>imagenes/iflow.png" />

		<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
		
        <form action="options-general.php?page=iflow_settings" method="post">
        
            <?php
                wp_enqueue_style('admin.css', plugin_dir_url(__FILE__) . 'css/admin.css', array(), IFLOW_VERSION);
                wp_nonce_field('iflow_settings_save','iflow_wpnonce',false,true);
                settings_fields('iflow_settings');
                do_settings_sections('iflow_settings');
                submit_button('Guardar');
            ?>
		
        </form>
	</div>
<?php

}

    