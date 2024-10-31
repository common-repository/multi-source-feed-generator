<?php

namespace Cronycle\Collection;

class API extends Request
{

	CONST API_URL = 'https://api.cronycle.com/';

	private $_auth_token;

	public function __construct( $token )
	{
		$this->_auth_token = $token;
	}

	public function query( $method, $params, $add_headers = false, $version = 3 )
	{
		return parent::query( self::API_URL.'/v'.$version.'/'.$method , $params, $add_headers );
	}

	public function getAuthToken()
	{
		return $this->_auth_token;
	}

	public function setAuthToken( $token )
	{
		$this->_auth_token = $token;
	}

	public function getUserDetails()
	{
		$params = array(
			'auth_token' => $this->getAuthToken(),
		);
		return $this->query( 'user.json', $params );
	}

	public function getCollection( $id, $include_links = 10, $include_first = 4, $ignore_cache = true )
	{
		$params = array(
			'auth_token' => $this->getAuthToken(),
			'include_links' => $include_links,
			'include_first' => $include_first,
			'ignore_cache' => $ignore_cache,
		);
		return $this->query( 'collections/'.$id.'.json', $params );
	}

	public function getCollections()
	{
		$params = array(
			'auth_token' => $this->getAuthToken(),
		);

		$response = $this->query( 'collections.json', $params, false, 6 );
		$json = array();
		if ( isset( $response[0] ) ) foreach ( $response as $collection )
			$json[] = array( 'text' => $collection['name'], 'value' => $collection['private_id'] );

		return json_encode( $json );
	}

	public function getCollectionLinks( $id, $timestamp = null )
	{
		$params = array(
			'auth_token' 		=> $this->getAuthToken(),
			'per_page' 			=> 10,
			'max_timestamp' 	=> (int)$timestamp ? $timestamp : time(),
			'ignore_cache' 		=> false,
		);
		return $this->query( 'collections/'.$id.'/links', $params, true );
	}

}