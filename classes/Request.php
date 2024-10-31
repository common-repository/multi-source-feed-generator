<?php

namespace Cronycle\Collection;

class Request
{
	public function query( $url, $params, $add_headers = false )
	{
		$token = $params['auth_token'];
		unset( $params['auth_token'] );

		$url = count( $params ) ? $url.'?'.http_build_query( $params ) : $url;

		$curl = curl_init( $url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, array(
			'Content-type: application/json',
			'Authorization: Token auth_token='.$token,
		) );

		if ( $add_headers )
			curl_setopt( $curl, CURLOPT_HEADER, true );

		$response = curl_exec( $curl );

		if ( $add_headers )
		{
			$headers_size = curl_getinfo( $curl, CURLINFO_HEADER_SIZE );
			$headers = $this->parse_headers( trim( substr( $response, 0, $headers_size ) ) );
			$body = json_decode( trim( substr( $response, $headers_size ) ), true );
		}
		curl_close( $curl );

		return $add_headers ? array( 'headers' => $headers, 'response' => $body ) : json_decode( $response, true );
	}

	public function parse_headers( $raw_headers )
	{
		$headers = array();

		foreach ( explode( "\n", $raw_headers ) as $i => $h )
		{
			$h = explode( ':', $h, 2 );

			if ( isset( $h[1] ) )
				$headers[ $h[0] ] = trim( $h[1] );
		}

		return $headers;
	}
}