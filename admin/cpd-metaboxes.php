<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if( !class_exists( 'CPD_Metaboxes' ) ) {

/**
 * Metaboxes
 *
 * Metabox functionality
 *
 * @package    CPD
 * @subpackage CPD/admin
 * @author     Make Do <hello@makedo.in>
 */
class CPD_Metaboxes {

	private static $instance = null;
	private $text_domain;

	/**
	 * Creates or returns an instance of this class.
	 */
	public static function get_instance() {
		/**
		 * If an instance hasn't been created and set to $instance create an instance 
		 * and set it to $instance.
		 */
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}


	/**
	 * Initialize the class and set its properties.
	 *
	 * @param      string    $instance       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct() {
		
	}

	/**
	 * Set the text domain
	 *
	 * @param      string    $text_domain       The text domain of the plugin.
	 */
	public function set_text_domain( $text_domain ) { 
		$this->text_domain = $text_domain;
	}


	/**
	 * Hide Metaboxes
	 * 
	 * @param  array 	$hidden 	array of metaboxes to hide
	 * @param  object 	$screen 	the screen object
	 *
	 * @hook 	filter_cpd_hide_metaboxes 	Filter the metaboxes we wish to hide
	 */
	public function hide_metaboxes( $hidden, $screen ) {

		$hidden 	= 	apply_filters(
							'filter_cpd_hide_metaboxes',
							array(
								'postcustom',
								'commentsdiv',
								'commentstatusdiv',
								'slugdiv',
								'trackbacksdiv',
								'revisionsdiv',
								'tagsdiv-post_tag',
								'authordiv',
								'wpseo_meta',
								'relevanssi_hidebox',
								'aiosp'
							)
						);

		return $hidden;
	}
}
}