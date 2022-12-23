<?php defined('ABSPATH') or die('No script kiddies please!');

/*
Plugin Name: Uplatnica
Plugin URI: https://milos.live
Description: Generisanje opšte uplatnice i NBS IPS QR kôda za uplate iz Srbije, u PDF formatu.
Author: Milos M.
Version: 0.8.1
Author URI: https://milos.live
*/

defined('WOOPLATNICA_FILE') or define('WOOPLATNICA_FILE', __FILE__);

if (!class_exists('tFPDF')) {
    require_once(plugin_dir_path(__FILE__).'/tfpdf/tfpdf.php');
}

register_activation_hook(WOOPLATNICA_FILE, function () {
    if (!class_exists('WC_Payment_Gateway')) {
        die('WooCommerce nije aktiviran ili Muplatnica nije kompatibilna sa verzijom WooCommerce-a koju imate.');
    }
});

add_action('wp_loaded', function () {
    $muplatnicaClasses = [
        'Muplatnica'            => true,
        'Uplatnica'              => false,
        'WC_Gateway_Muplatnica' => false,
    ];

    if (!class_exists('WC_Payment_Gateway')) {
        require_once(ABSPATH.'wp-admin/includes/plugin.php');
        deactivate_plugins(WOOPLATNICA_FILE);
        wp_redirect('plugins.php?deactivate=true');
        die();
    }

    foreach ($muplatnicaClasses as $muplatnicaClass => $init) {
        require_once(__DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.$muplatnicaClass.'.php');
        if ($init) {
            $$muplatnicaClass = new $muplatnicaClass;
        }
    }
});