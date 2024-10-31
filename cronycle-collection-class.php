<?php

namespace Cronycle\Collection;

require_once 'classes/View.php';
require_once 'classes/Request.php';
require_once 'classes/API.php';

class CronycleCollection 
{

	const PREFIX = 'cronycle_';
	
	private $_conn = null;
	private $_api = null;
	private $_options;
	
	public function __construct()
	{
		global $wpdb;
		
		$this->_conn = &$wpdb;
		$this->_api = new API( $this->getOption( 'auth_token' ) );
		
		$this->_options = array(
			'auth_token' 		=> '',
			'pro_account' 		=> 0,
			'remove_frame' 		=> 0,
		);
	}
	
	public function install()
	{
		foreach ( $this->_options as $k => $v )
			$this->updateOption( $k, $v );
	}
	
	public function uninstall()
	{
		foreach ( $this->_options as $k => $v )
			$this->deleteOption( $k);
	}
	
	public function getOption( $opt )
	{
		return get_option( self::PREFIX.$opt );
	}
	
	public function updateOption( $opt, $val )
	{
		update_option( self::PREFIX.$opt, $val );
	}
	
	public function deleteOption( $opt )
	{
		delete_option( self::PREFIX.$opt );
	}
	
	/*
	 * Enqueue styles to blog frontend
	*/
	public function addFrontendHead() 
	{
		wp_enqueue_style( 'cronycle-collection-frontend', plugin_dir_url( __FILE__ ).'assets/css/styles.css' );
		
		wp_register_script( 'angular-core', plugin_dir_url( __FILE__ ).'assets/bower_components/angular/angular.min.js' );
		wp_register_script( 'angular-swipe', plugin_dir_url( __FILE__ ).'assets/bower_components/angular-swipe/dist/angular-swipe.min.js' );
		wp_register_script( 'angular-md5', plugin_dir_url( __FILE__ ).'assets/bower_components/angular-md5/angular-md5.min.js' );

		wp_register_script( 'angular-collections-app', plugin_dir_url( __FILE__ ).'assets/dist/js/app.js' );
		wp_localize_script( 'angular-collections-app', 'ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		
		wp_enqueue_script( 'angular-core' );
		wp_enqueue_script( 'angular-swipe', array( 'angular-core' ) );
		wp_enqueue_script( 'angular-md5', array( 'angular-core' ) );
		wp_enqueue_script( 'angular-collections-app', array( 'angular-core', 'angular-swipe', 'angular-md5' ) );
	}
	
	/*
	 * Enqueue styles to admin area
	*/
	public function addAdminHead()
	{
		global $typenow;
		
		wp_enqueue_style( 'cronycle-collection-admin', plugin_dir_url( __FILE__ ).'assets/css/admin.css' );
		
		// Add only on Post Type: post and page
		if( !in_array( $typenow, array( 'post', 'page' ) ) ) return;
		
		add_filter( 'mce_external_plugins', array( $this, 'addTinymcePlugin' ) );
		
		// Add to first line in TinyMCE
		add_filter( 'mce_buttons', array( $this, 'addTinymceButton' ) );
	}
	
	/*
	 * Inlcude js for TinyMCE
	 */
	public function addTinymcePlugin( $plugin_array )
	{
		$plugin_array['cronycle_collection_plugin'] = plugins_url( '/assets/dist/js/plugin.js', __FILE__ );
		return $plugin_array;
	}
	
	/*
	 * Add the button key for address via JS
	*/
	public function addTinymceButton( $buttons )
	{
		array_push( $buttons, 'cronycle_collection' );
		return $buttons;
	}
	
	/*
	 * Add menu option to Settings page
	*/
	public function addAdminMenu()
	{
		add_options_page( 'Cronycle Collections', 'Cronycle Collections', 'administrator', __FILE__, array( $this, 'init' ) );
	}
	
	/*
	 * Initialize settings
	*/
	public function init()
	{
		$Block = new View( __DIR__.'/views/settings.html' );
		$Block->messages = '';
		
 		if ( isset( $_POST['go'] ) )
 		{
 			$this->updateOption( 'auth_token', $_POST['cronycle_auth_token'] );
 			$this->_api->setAuthToken( $_POST['cronycle_auth_token'] );

 			$user = $this->_api->getUserDetails();
 			
			// Checking if user has pro account
			$this->updateOption( 'pro_account', isset( $user['is_pro'] ) && $user['is_pro'] ? 1 : 0 );
			$this->updateOption( 'remove_frame', 0 );
			
 			if ( isset( $user['id'] ) )
 			{
				// Show success message
 				$Block->use_storage( 'msg' )->assign( array( 'message' => __( 'Your auth token rules and u can move on!' ) ) )->add( 'success' )->use_storage();
 				$Block->messages = $Block->storage_content( 'msg' );
 			}
 			else
 			{
 				// Show auth error
 				$Block->use_storage( 'msg' )->assign( array( 'message' => __( 'Woops, something went wrong while we tried to get your details...' ) ) )->add( 'error' )->use_storage();
 				$Block->messages = $Block->storage_content( 'msg' );
 			}
 		}
 		
 		if ( $this->getOption( 'pro_account' ) )
 		{
 			$Block->use_storage( 'display_settings' );
 			if ( $this->getOption( 'remove_frame' ) ) $Block->assign( array( 'checked' => ' checked' ) );
 			$Block->add( 'display-settings' )->use_storage();
 			
 			$Block->assign( array(
 				'display_settings' => $Block->storage_content( 'display_settings' ),
 				'unblock' => '',
 			), false );
 		}
 		else
 		{
 			$Block->assign( array(
				'display_settings' => '',
 				'unblock' => $Block->get( 'unblock' ),
 			), false );
 		}
 		
		$Block->assign( array(
			'auth_token' 	=>	$this->getOption( 'auth_token' ),
			'mode' 			=>	$this->getOption( 'pro_account' ) ? 'pro' : 'normal',
		) )->add( 'form' )->content( true );
	}
	
	/*
	 * This method toggle display settings
	*/
	public function toggleDisplaySettings()
	{
 		$this->updateOption( 'remove_frame', (int)$_GET['remove_frame'] );
 		wp_die();
	}
	
	/*
	 * This method allows to get collections list
	*/
	public function getCollections()
	{
 		echo $this->_api->getCollections();
 		wp_die();
	}
	
	/* 
	 * This method allows to parse shortcode in the format
	 * [cronycle collection="COLLECTION_ID" name="" style="" width="" height="" instance=""]
	 */
	public function shortcode( $atts ) 
	{
		extract( $atts );
		
		$Block = new View( __DIR__.'/views/collection.html' );
		
		$response = $this->_api->getCollection( $collection );
		if ( isset( $response['id'] ) )
		{ 
			$Block->use_storage( 'powered' );
			if ( !$this->getOption( 'remove_frame' ) )
			{
				$Block->assign( array(
					'plugin_url' => plugin_dir_url( __FILE__ ),
				) )->add( 'powered' );
			}
			$Block->use_storage();
			
			$mode = strpos( $style, 'inline' ) !== false ? 'h' : 'v';
			
			$Block->assign( $response )->assign( array(
				'collection_name' 	=> $response['name'],
				'id' 				=> $collection,
				'timestamp' 		=> time(),
				'mode' 				=> $mode,
				'prev_action'		=> $mode == 'v' ? 'down' : 'right',
				'next_action'		=> $mode == 'v' ? 'up' : 'left',
				'width'				=> $width ? $width : ( $mode == 'v' ? '250px' : '100%' ),
				'height'			=> $height ? $height : ( $mode == 'v' ? '600px' : '500px' ),
				'instance'			=> $instance,
			) )->assign( array(
				'powered' => $this->getOption( 'remove_frame' ) ? '' : $Block->storage_content( 'powered' ),
			), false );
		
			$Block->add( 'articles' );
		}
		
	    return $Block->content();
	}
	
	/*
	 * This method allows to get articles list
	*/
	public function getArticles()
	{
		$articles = $this->_api->getCollectionLinks( $_GET['collection'], $_GET['timestamp'] );

		$response = array();
		$response['total'] = $articles['headers']['X-Total-Links-Count'];
		$response['timestamp'] = $articles['headers']['X-Max-Timestamp'];
		
		if ( isset( $articles['response'][0]['id'] ) ) foreach ( $articles['response'] as $article )
		{
			$media_type = isset( $article['assets'][0]['media_type'] ) ? $article['assets'][0]['media_type'] : '';
			$media_embed = $media_type == 'video' ? $article['assets'][0]['embed_code'] : '';
			$media_url = isset( $article['assets'][0]['url_original'] ) ? $article['assets'][0]['url_original'] : '';

			$response['articles'][] = array(
				'url' 			=> $article['url'],
				'name' 			=> $article['name'] !== '' ? View::short_name_custom( trim( $article['name'] ), 46 ) : '',
				'description' 	=> $article['description'] !== '' ? View::short_name_custom( strip_tags( trim( $article['description'] ) ), 100 ) : '',
				'date' 			=> date( 'j F Y, H:i', $article['published_at'] ),
				'media_type' 	=> $media_type,
				'media_embed' 	=> $media_embed,
				'media_url' 	=> $media_url,
				'sources'		=> isset( $article['sources'][0] ) ? array(
					'name'		=> $article['sources'][0]['screen_name'],
					'url'		=> $article['url'],
					'image'		=> isset( $article['sources'][0]['profile_image_url'] ) ? $article['sources'][0]['profile_image_url'] : '',
				): null,
			);
		}
		
		echo json_encode( $response );
		wp_die();
	}
	
}