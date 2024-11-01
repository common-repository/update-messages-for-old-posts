<?php
/**
 * Plugin Name:       Push Update Messages To Old Posts
 * Plugin URI:        http://guaven.com/updatepusher
 * Description:       Don't let time to make your content outdated
 * Version:           1.0.0
 * Author:            Guaven Labs
 * Author URI:        http://guaven.com/
 * Text Domain:       guaven_updatepusher
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}



//if (is_admin())
require_once(dirname(__FILE__)."/settings.php");
//else
require_once(dirname(__FILE__)."/functions.php");




add_action('admin_menu', 'guaven_updatepusher_admin');
