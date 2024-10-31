<?php
/*
Plugin Name: Multi Source Feed Generator
Plugin URI: https://cronycle.com/plugins/wordpress-multi-rss-feed  
Description: Embed RSS and twitter feeds into your Wordpress site which are filtered using specific key words.
Version: 1.4.2
Author: Cronycle
Author URI: https://cronycle.com 
*/

namespace Cronycle\Collection;

require_once 'cronycle-collection-class.php';

$CronycleCollection = new CronycleCollection();

register_activation_hook( __FILE__, array( $CronycleCollection, 'install' ) );
register_deactivation_hook( __FILE__, array( $CronycleCollection, 'uninstall' ) );

add_action( 'admin_head', array( $CronycleCollection, 'addAdminHead' ) ); 
add_action( 'admin_menu', array( $CronycleCollection, 'addAdminMenu' ) ); 

add_action( 'wp_ajax_getCollections', array( $CronycleCollection, 'getCollections' ) );
add_action( 'wp_ajax_getArticles', array( $CronycleCollection, 'getArticles' ) );
add_action( 'wp_ajax_nopriv_getArticles', array( $CronycleCollection, 'getArticles' ) );
add_action( 'wp_ajax_toggleDisplaySettings', array( $CronycleCollection, 'toggleDisplaySettings' ) );
add_shortcode( 'cronycle', array( $CronycleCollection, 'shortcode' ) );

add_action( 'wp_enqueue_scripts', array( $CronycleCollection, 'addFrontendHead' ) );
