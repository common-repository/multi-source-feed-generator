<?php

namespace Cronycle\Collection;

class View
{
	
	private $_blocks;
	private $_storages;
	private $_storage;
	private $_contents;	
	private $_content;
	
	protected $_conn = null;

	public function __construct( $tpl = '' )
	{
		$this->use_storage();
		if ( $tpl ) $this->load( $tpl );
	}
	
	public function use_storage( $storage = null )
	{
		$storage = is_null( $storage ) ? 'default' : $storage;
		
		if ( !isset( $this->_storages[ $storage ] ) ) 
			$this->_storages[ $storage ] = array();
		
		if ( !isset( $this->_contents[ $storage ] ) ) 
			$this->_contents[ $storage ] = '';
		
		$this->_storage = &$this->_storages[ $storage ];
		$this->_content = &$this->_contents[ $storage ];
		return $this;
	}
					
	public function load( $file, $static = false )
	{
		if ( !file_exists( $file ) ) 
			throw new Exception( "ERROR: $file template can not be loaded..." );
			
		$content = file_get_contents( $file);
		
		if ( $static ) return $content;
		
		$blocks = explode( '<!--#', $content );
		unset( $blocks[0] );
		foreach ( $blocks as $item )
		{				
			$item = ltrim( $item, '<!--#' );
			$pos = stripos( $item, '#-->' );
			$block = substr( $item, 0, $pos );
			$this->_blocks[ $block ] = substr( $item, $pos + 4 );
		}			
	}
		
	public function __set( $placeholder, $value )
	{
		$this->_storage[ $placeholder ] = $value;
	}
	
	public function __get( $placeholder )
	{
		return isset( $this->_storage[ $placeholder ] ) ? $this->_storage[ $placeholder ] : ''; 
	}
	
	public function assign( $mixed, $secure = true )
	{
		if ( is_array( $mixed ) )
		{
			foreach ( $mixed as $key => $value )
				if ( is_string( $value ) || is_numeric( $value ) ) $this->_storage[ $key ] = $secure ? htmlspecialchars( $value ) : $value; 
		}
		else if ( is_object( $mixed ) && method_exists( $mixed, 'get_data' ) )
		{
			foreach ( $mixed->get_data() as $key => $value )
				if ( is_string( $value ) || is_numeric( $value ) ) $this->_storage[ $key ] = $secure ? htmlspecialchars( $value ) : $value;
		}
		return $this;
	}
	
	public function add( $block )
	{
		$replacements = array();
		foreach ( $this->_storage as $placeholder => $value )
			$replacements['{'.$placeholder.'}'] = $value;
					
		$data = strtr( $this->get( $block ), $replacements );
		$this->_content .= trim( $data, " \n\r\0\x0B" )."\n";				
		$this->_storage = array();
		
		return $this;
	}
	
	public function get( $block )
	{
		if ( !isset( $this->_blocks[ $block ] ) )
			throw new Exception( 'ERROR: block "'.$block.'" was not found...' );	
		
		return $this->_blocks[ $block ];
	}
	
	public function clear()
	{
		$this->_storage = array();
		$this->_content = '';
		return $this;
	}
	
	public function content( $flush = false )
	{		
		if ( $flush ) 
			echo $this->_content;
		else
			return $this->_content;
	}
	
	public function storage_content( $storage )
	{
		return isset( $this->_contents[ $storage ] ) ? $this->_contents[ $storage ] : '';
	}
	
	public static function short_name_custom( $str, $limit = 30 )
	{
		return mb_strlen( $str, "utf-8" ) < $limit ? $str : sprintf( '%s...', mb_substr( $str, 0, $limit, 'utf-8' ) );
	}

}