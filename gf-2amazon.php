<?php
/**
 * GF 2 Amazon MWS
 *
 * @package     GF2MWS
 * @author      Reyaz Beigh
 * @license     GPLv3
 *
 * @wordpress-plugin
 * Plugin Name: GF 2 Amazon MWS
 * Version: 1.0
 * Description: Connects GF to MWS and validates Order submitted by user on amazon MWS
 * Author: Reyaz Beigh
 * Author URI: http://logicparadise.com
 * Plugin URI: 
 * Domain Path: 
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0
 */
define("AM_APPLICATION_NAME", "GravityForms2AmazonMWS");
define("APPLICATION_VERSION", "1.0");

define("PLUGIN_DIR", __DIR__);
require_once PLUGIN_DIR . '/lib/Amazon.php';

/**
 * Add the settings page to the menu
 */
function gf_2amazon_menu() {
    $page_title = 'GF To Amazon MWS';
    $menu_title = 'Gf 2 Amazon';
    $capability = 'administrator';
    $menu_slug = 'gf_2amazon';
    $function = 'gf_2amazon_options';
    $icon_url = 'dashicons-media-code';
    $position = 4;
    add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
    add_action('admin_init', 'register_gf_2amazon_settings');
}

add_action('admin_menu', 'gf_2amazon_menu');

/**
 * The plugin options page
 */
function register_gf_2amazon_settings() {
    //register our settings

    register_setting('gf-2amazon-settings-group', 'gf_order_id_page');
    register_setting('gf-2amazon-settings-group', 'order_id_field_label');
    register_setting('gf-2amazon-settings-group', 'order_id_invalid_message');
    register_setting('gf-2amazon-settings-group', 'AWS_ACCESS_KEY_ID');
    register_setting('gf-2amazon-settings-group', 'AWS_SECRET_ACCESS_KEY');
    register_setting('gf-2amazon-settings-group', 'MERCHANT_ID');
}

function gf_2amazon_options() {
    ?>
    <form method="post" action="options.php">
        <div class="wrap">
            <h1>Configuration</h1>
            <?php settings_fields('gf-2amazon-settings-group'); ?>
            <?php do_settings_sections('gf-2amazon-settings-group'); ?>
            <h2>
                Please setup the rules
            </h2>
            <div>
                <p>
                    <label for="gf_order_id_page">Gravity Form Page number with Amazon Order ID Input</label>
                </p>
                <input id="gf_order_id_page" type="text" name="gf_order_id_page" value="<?php echo esc_attr(get_option('gf_order_id_page')); ?>"/>
                <p>
                    <label for="order_id_field_label">Order Id field label</label>
                </p>
                <input id="order_id_field_label" type="text" name="order_id_field_label"  value="<?php echo esc_attr(get_option('order_id_field_label')); ?>"/>
                
                
                <p>
                    <label for="order_id_invalid_message">Order ID Invalid message</label>
                </p>
                <textarea id="order_id_field_label"  name="order_id_invalid_message"  ><?php echo esc_attr(get_option('order_id_invalid_message')); ?></textarea>
                <p>
                    <label for="AWS_ACCESS_KEY_ID">AWS_ACCESS_KEY_ID</label>
                </p>
                <input id="order_id_field_label" type="text" name="AWS_ACCESS_KEY_ID"  value="<?php echo esc_attr(get_option('AWS_ACCESS_KEY_ID')); ?>"/>
                
                <p>
                    <label for="AWS_SECRET_ACCESS_KEY">AWS_SECRET_ACCESS_KEY</label>
                </p>
                <input id="order_id_field_label" type="text" name="AWS_SECRET_ACCESS_KEY"  value="<?php echo esc_attr(get_option('AWS_SECRET_ACCESS_KEY')); ?>"/>
                
                <p>
                    <label for="MERCHANT_ID">MERCHANT_ID</label>
                </p>
                <input id="order_id_field_label" type="text" name="MERCHANT_ID"  value="<?php echo esc_attr(get_option('MERCHANT_ID')); ?>"/>
                
                
                
            </div>
            <?php submit_button(); ?>
        </div>
    </form>
    <?php
}

add_action('gform_post_paging', 'validateAmazonOrder', 10, 3);

function setAmazonConfig() {
    define("AWS_ACCESS_KEY_ID", esc_attr(get_option('AWS_ACCESS_KEY_ID'))); 
    define("AWS_SECRET_ACCESS_KEY", esc_attr(get_option('AWS_SECRET_ACCESS_KEY'))); 
    define("MERCHANT_ID", esc_attr(get_option('MERCHANT_ID'))); 
}

function validateAmazonOrder($form, $source_page_number, $current_page_number) {
    setAmazonConfig();

    if ($source_page_number == esc_attr(get_option('gf_order_id_page'))) {

        $order_id = getOrderIdSubmitted($form, esc_attr(get_option('order_id_field_label')));
        $amazon = new Amazon();
        $order = $amazon->getOrder($order_id);
        $js = "";
        
        if (!$order->isSuccess) {
            $inalid_order_msg =  esc_attr(get_option('order_id_invalid_message'));
            $js = <<<JS
                    var invalid_order_html = "<h2 class='entry-title'>$inalid_order_msg</h2><p><a onclick='history.go(-1)' href='#'> Go Back</a></p>";
                    jQuery(document).ready(function(){
                        jQuery("#primary").attr("style","text-align:center");
                        jQuery("#primary").html(invalid_order_html);
   
   });
                    
JS;
        } 
        echo '<script type="text/javascript">'
        . $js
        . '</script>';
    }
    #exit;
    

}

function getOrderIdSubmitted($form, $field_label) {
    $order_id = "";
    foreach ($form['fields'] as $field):
        if ($field->label == $field_label) {
            $order_id = $_POST['input_' . $field->id];
            break;
        }
    endforeach;
    return $order_id;
}
