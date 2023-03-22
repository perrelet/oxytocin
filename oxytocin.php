<?php

/**
 * Plugin Name:       Oxytocin
 * Plugin URI:        https://digitalis.ca
 * Description:       Oxygen, but better.
 * Version:           0.0.1
 * Author:            Digitalis Web Build Co.
 * Author URI:        https://digitalis.ca
 * Text Domain:       digitalis
 */

if (!defined('WPINC')) die;

define('OXYTOCIN_VERSION', 		'0.0.1' );
define('OXYTOCIN_PATH', 		plugin_dir_path(__FILE__ )); //Trailing slash = /
define('OXYTOCIN_URI',			plugin_dir_url(__FILE__ ));
define('OXYTOCIN_BASE',			plugin_basename(__FILE__));
define('OXYTOCIN_SLUG',			plugin_basename(__DIR__));
define('OXYTOCIN_ROOT_FILE',	__FILE__ );
define('OXYTOCIN_OPTION',		'oxytocin_');

require OXYTOCIN_PATH . 'include/digitalis-framework/load.php';
require OXYTOCIN_PATH . 'include/oxytocin.class.php';

/* OXYTOCIN */

function oxytocin () {

    return Oxytocin\Oxytocin::get_instance();

}

add_action('plugins_loaded', 'oxytocin');