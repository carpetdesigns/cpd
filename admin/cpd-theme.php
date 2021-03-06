<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'CPD_Theme' ) ) {

	/**
	 * Blogs
	 *
	 * Methods affecting blogs
	 *
	 * @package    CPD
	 * @subpackage CPD/admin
	 * @author     Make Do <hello@makedo.in>
	 */
	class CPD_Theme {

		private static $instance = null;
		private        $text_domain;
		private        $github_data;
		public         $config;

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
		 */
		public function __construct() {

			$slug            = get_option( 'cpd_theme', 'cpd-theme' );
			$theme           = wp_get_theme( $slug );
			$blogs_to_update = array();

			$this->config = array(
				'slug'                => $slug,                                                  // this is the slug of your plugin
				'data'                => get_theme_root() . '/' . $slug . '/style.css',          // this is the path of your plugin
				'proper_folder_name'  => $slug,                                                  // this is the name of the folder your plugin lives in
				'api_url'             => 'https://api.github.com/repos/mkdo/' . $slug,           // the GitHub API url of your GitHub repo
				'raw_url'             => 'https://raw.github.com/mkdo/' . $slug . '/master',     // the GitHub raw url of your GitHub repo
				'github_url'          => 'https://github.com/mkdo/' . $slug,                     // the GitHub url of your GitHub repo
				'zip_url'             => 'https://github.com/mkdo/' . $slug . '/zipball/master', // the zip url of the GitHub repo
				'sslverify'           => true,                                                   // whether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
				'requires'            => '4.0',                                                  // which version of WordPress does your plugin require?
				'tested'              => '4.0',                                                  // which version of WordPress is your plugin tested up to?
				'access_token'        => '',                                                     // Access private repositories by authorizing under Appearance > GitHub Updates when this example plugin is installed
			);

			// Legacy installs
			if ( $slug == 'cpd-theme' ) {
				$theme = wp_get_theme( 'aspire-cpd' );
				if ( $theme->exists() ) {
					$slug = 'aspire-cpd';
					$this->config['slug'] = $slug;
					$this->config['data'] = get_theme_root() . '/' . $slug . '/style.css';
				}
			}

			// $blogs = wp_get_sites();
			// foreach ( $blogs as $blog ) {
			// 	switch_to_blog( $blog['blog_id'] );

			// 	$stylesheet =  get_option( 'stylesheet' );

			// 	if( $stylesheet == $slug ) {
			// 		$blogs_to_update[] = $blog['blog_id'];
			// 	}

			// 	restore_current_blog();
			// }

			// $this->config['blogs_to_update'] = $blogs_to_update;

			$this->set_defaults();
		}

		/**
		 * Set the text domain
		 *
		 * @param string  $text_domain The text domain of the plugin.
		 */
		public function set_text_domain( $text_domain ) {
			$this->text_domain = $text_domain;
		}

		/**
		 * Set defaults
		 */
		public function set_defaults() {

			if ( !empty( $this->config['access_token'] ) ) {

				// See Downloading a zipball (private repo) https://help.github.com/articles/downloading-files-from-the-command-line
				extract( parse_url( $this->config['zip_url'] ) ); // $scheme, $host, $path

				$zip_url = $scheme . '://api.github.com/repos' . $path;
				$zip_url = add_query_arg( array( 'access_token' => $this->config['access_token'] ), $zip_url );

				$this->config['zip_url'] = $zip_url;
			}

			if ( ! isset( $this->config['new_version'] ) )
				$this->config['new_version'] = $this->get_new_version();

			if ( ! isset( $this->config['last_updated'] ) )
				$this->config['last_updated'] = $this->get_date();

			$theme_data = $this->get_theme_data( $this->config['data'] );

			if ( ! isset( $this->config['name'] ) )
				$this->config['name'] = $theme_data->get( 'Name' );

			if ( ! isset( $this->config['version'] ) )
				$this->config['version'] = $theme_data->get( 'Version' );

			if ( ! isset( $this->config['author'] ) )
				$this->config['author'] = $theme_data->get( 'Author' );

			if ( ! isset( $this->config['homepage'] ) )
				$this->config['homepage'] = $theme_data->get( 'ThemeURI' );

			if ( ! isset( $this->config['theme_uri'] ) )
				$this->config['theme_uri'] = $theme_data->get( 'ThemeURI' );

			if ( ! isset( $this->config['sections'] ) )
				$this->config['sections']['description'] =$theme_data->get( 'Description' );

		}

		/**
		 * Get New Version from GitHub
		 *
		 * @return int $version the version number
		 */
		public function get_new_version() {
			$version = get_site_transient( md5( $this->config['slug'] ).'_new_version' );

			if ( $this->overrule_transients() || ( !isset( $version ) || !$version || '' == $version ) ) {

				$raw_response = $this->remote_get( trailingslashit( $this->config['raw_url'] ) . 'style.css' );

				if ( is_wp_error( $raw_response ) ) {
					$version = false;
				}

				if ( is_array( $raw_response ) ) {
					if ( !empty( $raw_response['body'] ) )
						preg_match( '/.*Version\:\s*(.*)$/mi', $raw_response['body'], $matches );
				}

				if ( empty( $matches[1] ) )
					$version = false;
				else
					$version = $matches[1];

				// refresh every 6 hours
				if ( false !== $version )
					set_site_transient( md5( $this->config['slug'] ).'_new_version', $version, 60*60*6 );
			}

			return $version;
		}

		/**
		 * Interact with GitHub
		 *
		 * @param string  $query
		 *
		 * @return mixed
		 */
		public function remote_get( $query ) {
			if ( ! empty( $this->config['access_token'] ) )
				$query = add_query_arg( array( 'access_token' => $this->config['access_token'] ), $query );

			$raw_response = wp_remote_get( $query, array(
					'sslverify' => $this->config['sslverify']
				) );

			return $raw_response;
		}

		/**
		 * Get update date
		 *
		 * @return string $date the date
		 */
		public function get_date() {
			$_date = $this->get_github_data();
			return ( !empty( $_date->updated_at ) ) ? date( 'Y-m-d', strtotime( $_date->updated_at ) ) : false;
		}

		/**
		 * Get Plugin data
		 *
		 * @return object $data the data
		 */
		public function get_theme_data() {

			$data = wp_get_theme( $this->config['slug'] );
			return $data;
		}

		/**
		 * Get GitHub Data from the specified repository
		 *
		 * @return array $github_data the data
		 */
		public function get_github_data() {

			if ( isset( $this->github_data ) && ! empty( $this->github_data ) ) {
				$github_data = $this->github_data;
			} else {
				$github_data = get_site_transient( md5( $this->config['slug'] ).'_github_data' );

				if ( $this->overrule_transients() || ( ! isset( $github_data ) || ! $github_data || '' == $github_data ) ) {
					$github_data = $this->remote_get( $this->config['api_url'] );

					if ( is_wp_error( $github_data ) )
						return false;

					$github_data = json_decode( $github_data['body'] );

					// refresh every 6 hours
					set_site_transient( md5( $this->config['slug'] ).'_github_data', $github_data, 60*60*6 );
				}

				// Store the data in this class instance for future calls
				$this->github_data = $github_data;
			}

			return $github_data;
		}

		/**
		 * Check wether or not the transients need to be overruled and API needs to be called for every single page load
		 *
		 * @return bool overrule or not
		 */
		public function overrule_transients() {
			return defined( 'WP_GITHUB_FORCE_UPDATE' ) && WP_GITHUB_FORCE_UPDATE;
		}

		/**
		 * Hook into the plugin update check and connect to GitHub
		 *
		 * @param object  $transient the plugin data transient
		 * @return object $transient updated plugin data transient
		 */
		public function pre_set_site_transient_update_themes( $transient ) {

			// Check if the transient contains the 'checked' information
			// If not, just return its value without hacking it
			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			// check the version and decide if it's new
			$update = version_compare( $this->config['new_version'], $this->config['version'] );

			if ( 1 === $update ) {

				$transient->response[ $this->config['slug'] ] = array(
					'new_version' => $this->config['new_version'],
					'package'     => add_query_arg( array( 'access_token' => $this->config['access_token'] ), $this->config['zip_url'] ),
					'url'         => $this->config['zip_url'],
				);
			}

			return $transient;
		}

		/**
		 * Upgrader/Updater
		 * Move & activate the plugin, echo the update message
		 *
		 * @param boolean $true       always true
		 * @param mixed   $hook_extra not used
		 * @param array   $result     the result of the move
		 * @return array $result the result of the move
		 */
		public function upgrader_post_install( $true, $hook_extra, $result ) {

			global $wp_filesystem;

			if ( isset( $hook_extra['theme'] ) && ( $hook_extra['theme'] == $this->config['slug'] ) ) {
				// Move & Activate
				$proper_destination = trailingslashit( get_theme_root() ) . $this->config['slug'];
				rename( $result['destination'], $proper_destination );
				$result['destination'] = $proper_destination;
				$result['destination_name'] = $this->config['slug'];
				update_option( 'stylesheet', $this->config['slug'] );
				switch_theme( $this->config['slug'] );
			}

			if ( isset( $hook_extra['type'] ) &&  $hook_extra['type'] == 'theme' && strrpos( $result['destination'], $this->config['slug'] ) ) {
				$proper_destination = trailingslashit( get_theme_root() ) . $this->config['slug'];
				rename( $result['destination'], $proper_destination );
				$result['destination'] = $proper_destination;
				$result['destination_name'] = $this->config['slug'];
			}

			return $result;

		}

		/**
		 * Add taxonomies as a notice
		 */
		public function add_missing_theme_notice() {

			global $pagenow;
			global $typenow;

			if ( $pagenow != 'update.php' ) {

				$current_user     = wp_get_current_user();
				$is_elevated_user = get_user_meta( $current_user->ID, 'elevated_user', TRUE ) == '1';

				// delete_user_meta( $current_user->ID, 'cpd_notice_install_theme_hide');

				if ( is_super_admin() || $is_elevated_user || user_can( $current_user, 'administrator' ) ) {

					$parent_installed = FALSE;
					$theme_installed  = FALSE;

					$slug             = $this->config['slug'];
					$parent_slug      = get_option( 'cpd_theme_parent' );
					$theme            = wp_get_theme( $slug );
					$parent           = wp_get_theme( $parent_slug );

					if ( $theme->exists() ) {
						$theme_installed  = TRUE;
					}

					if ( $parent->exists() ) {
						$parent_installed = TRUE;
					}

					if ( ( !empty( $parent_slug ) && !$parent_installed ) || !$theme_installed ) {
						if ( !get_user_meta( $current_user->ID, 'cpd_notice_install_theme_hide' ) ) {
?>
						<div class="error">
							<?php
							if ( !empty( $parent_slug ) && !$parent_installed ) {
?>
									<p>Aspire CPD requires a parent theme (<strong><?php echo $parent_slug;?></strong>) to be installed for your compatible theme to work correctly.</p>
									<p><a href="<?php echo wp_nonce_url( network_admin_url( 'update.php?action=install-theme&theme=' . $parent_slug ), 'install-theme_' . $parent_slug );?>">Install Theme</a> | <a href="?cpd_notice_install_theme_hide=true">Hide this Notice</a></p>
									<?php
							} else if ( !$theme_installed ) {
?>
									<p>Aspire CPD works better with a compatible theme (<strong><?php echo $slug;?></strong>).</p>
									<p><a href="<?php echo wp_nonce_url( network_admin_url( 'update.php?action=install-theme&theme=' . $slug ), 'install-theme_' . $slug );?>">Install Theme</a> | <a href="?cpd_notice_install_theme_hide=true">Hide this Notice</a></p>
									<?php
								}
?>

						</div>
						<?php
						}
					}
				}

			}
		}

		public function hide_missing_theme_notice() {
			global $current_user;
			$user_id = $current_user->ID;
			/* If user clicks to ignore the notice, add that to their user meta */
			if ( isset( $_GET['cpd_notice_install_theme_hide'] ) && 'true' == $_GET['cpd_notice_install_theme_hide'] ) {
				add_user_meta( $user_id, 'cpd_notice_install_theme_hide', 'true', true );
			}
		}

		/**
		 * Get Plugin info
		 *
		 * @param bool    $false  always false
		 * @param string  $action the API function being performed
		 * @param object  $args   plugin arguments
		 * @return object $response the plugin info
		 */
		public function get_theme_info( $false, $action, $response ) {

			$slug = $this->config['slug'];

			// Legacy support
			if( $slug == 'aspire-cpd' ) {
				$slug = 'cpd-theme';
			}

			// Check if this call API is for the right plugin
			if ( !isset( $response->slug ) || $response->slug != $slug ) {
				return false;
			}

			$response->slug           = $this->config['slug'];
			$response->name           = $this->config['name'];
			$response->version        = $this->config['new_version'];
			$response->author         = $this->config['author'];
			$response->homepage       = $this->config['homepage'];
			$response->requires       = $this->config['requires'];
			$response->tested         = $this->config['tested'];
			$response->downloaded     = 0;
			$response->last_updated   = $this->config['last_updated'];
			if ( isset( $this->config['description'] ) ) {
				$response->sections       = array( 'description' => $this->config['description'] );
			}
			$response->download_link  = $this->config['zip_url'];
			$response->preview_url    = $this->config['theme_uri'];
			$response->sections       = $this->config['sections'];
			$response->description    = implode( "\n", $this->config['sections'] );
			$response->requires       = null;
			$response->tested         = null;
			$response->rating         = 0;
			$response->num_ratings    = 0;

			return $response;
		}

	}
}
