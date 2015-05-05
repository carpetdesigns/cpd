<?php
/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://makedo.in
 * @since      1.0.0
 *
 * @package    CPD
 * @subpackage CPD/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @package    CPD
 * @subpackage CPD/admin
 * @author     Make Do <hello@makedo.in>
 */
class CPD_Journal_Dashboards{

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $instance       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct() {
		
		
	}


	/**
	 * Rename page titles
	 * 
	 * @param  string 	$translation 	The translated text
	 * @param  string 	$text        	The text to be translated
	 * @param  string 	$domain      	The domain of the text we are translating
	 * @return string 	$translation 	The translated text
	 */
	public function rename_page_titles( $translation, $text, $domain )
	{
	    if ( $domain == 'default' && $text == 'Sites' )
		{
			remove_filter( 'gettext', 'rename_sites_page' );
			return 'Journals';
		}

		if ( $domain == 'default' && $text == 'My Sites' )
		{
			remove_filter( 'gettext', 'rename_sites_page' );
			return 'My Journals';
		}

		if ( $domain == 'default' && $text == 'Primary Site' )
		{
			remove_filter( 'gettext', 'rename_sites_page' );
			return 'Primary Journal';
		}

		if ( $domain == 'default' && $text == 'Posts' )
		{
			remove_filter( 'gettext', 'rename_sites_page' );
			return 'Journal Entries';
		}

		return $translation;
	}

	function rename_post_object() {
		global $wp_post_types;
		$labels = &$wp_post_types['post']->labels;
		$labels->name = 'Journal Entry';
		$labels->singular_name = 'Journal Entry';
		$labels->add_new = 'Add Journal Entry';
		$labels->add_new_item = 'Add Journal Entry';
		$labels->edit_item = 'Edit Journal Entry';
		$labels->new_item = 'Journal Entry';
		$labels->view_item = 'View Journal Entries';
		$labels->search_items = 'Search Journal Entries';
		$labels->not_found = 'No Journal Entries found';
		$labels->not_found_in_trash = 'No Journal Entries found in Trash';
		$labels->all_items = 'All Journal Entries';
		$labels->menu_name = 'Journal Entry';
		$labels->name_admin_bar = 'Journal Entry';
	}

	public function force_network_color_scheme( $color_scheme ) {
		
		$screen = get_current_screen();

		$current_user 	= wp_get_current_user();
		$roles 			= $current_user->roles;

		//$color_scheme 	= 'midnight';

		if( in_array( 'supervisor', 		$roles) ) {
			$color_scheme 	= 'ectoplasm';
		}
		if( in_array( 'participant', 		$roles) ) {
			$color_scheme 	= 'ocean';
		}

		if( strpos( $screen->base, '-network') ) {
			$color_scheme 	= 'sunrise';
		}

		return $color_scheme;
	}
}