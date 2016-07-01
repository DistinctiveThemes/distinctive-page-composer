<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

define('DAPPERCOMPOSER_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DAPPERCOMPOSER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Global variables for plugin usage (global declaration is needed here for WP CLI compatibility)
global $dapper_cl_file, $dapper_cl_dir, $dapper_cl_uri, $dapper_cl_version;
$dapper_cl_file = plugin_dir_path(__FILE__) . '/codelights.php';;
$dapper_cl_dir = plugin_dir_path(__FILE__) . '/';
$dapper_cl_uri = plugin_dir_url(__FILE__) . '/';
$dapper_cl_version = '1.0';
unset( $dapper_cl_matches );

require $dapper_cl_dir . 'functions/helpers.php';

// Widgets
require $dapper_cl_dir . 'functions/class-cl-widget.php';

add_action( 'plugins_loaded', 'dapper_cl_plugins_loaded' );
function dapper_cl_plugins_loaded() {
	// Editors support
	global $dapper_cl_dir;
	require $dapper_cl_dir . 'editors-support/native/native.php';
	require $dapper_cl_dir . 'editors-support/siteorigin/siteorigin.php';
	// I18n support
	dapper_cl_maybe_load_plugin_textdomain();
}

// Ajax requests
if ( is_admin() AND isset( $_POST['action'] ) AND substr( $_POST['action'], 0, 3 ) == 'dapper_cl_' ) {
	require $dapper_cl_dir . 'functions/ajax.php';
}

add_action( 'wp_enqueue_scripts', 'dapper_cl_register_assets', 8 );
function dapper_cl_register_assets() {
	// Registering front-end assets from config/assets.php
	foreach ( array( 'style', 'script' ) as $type ) {
		foreach ( dapper_cl_config( 'assets.' . $type . 's', array() ) as $handle => $params ) {
			array_unshift( $params, $handle );
			call_user_func_array( 'wp_register_' . $type, $params );
		}
	}
}

// Load admin scripts and styles
add_action( 'admin_enqueue_scripts', 'dapper_cl_admin_enqueue_scripts', 5 );
function dapper_cl_admin_enqueue_scripts() {
	global $dapper_cl_uri, $post_type, $wp_scripts, $dapper_cl_version;

	wp_register_script( 'wp-color-picker-alpha', plugin_dir_url(__FILE__) . '/vendor/wp-color-picker-alpha/wp-color-picker-alpha.min.js', array( 'wp-color-picker' ), $dapper_cl_version, '1.2.1' );
	wp_register_style( 'cl-editor', plugin_dir_url(__FILE__) . '/admin/css/editor.css', array( 'wp-color-picker' ), $dapper_cl_version );
	wp_register_script( 'cl-editor', plugin_dir_url(__FILE__) . '/admin/js/editor.js', array(
		'jquery-ui-sortable',
		'wp-color-picker-alpha',
	), $dapper_cl_version, TRUE );

	$screen = get_current_screen();
	$is_widgets = ( $screen->base == 'widgets' );
	$is_customizer = ( $screen->base == 'customize' );
	$is_content_editor = ( isset( $post_type ) AND post_type_supports( $post_type, 'editor' ) );

	// Extra JavaScript data
	$extra_js_data = 'if (window.$cl === undefined) window.$cl = {}; $cl.ajaxUrl = ' . wp_json_encode( admin_url( 'admin-ajax.php' ) ) . ";";
	if ( $is_content_editor ) {
		$extra_js_data .= '$cl.elements = ' . wp_json_encode( dapper_cl_config( 'elements', array() ) ) . ";\n";
	}
	$wp_scripts->add_data( 'cl-editor', 'data', $extra_js_data );

	if ( $is_widgets OR $is_customizer OR $is_content_editor ) {
		dapper_cl_enqueue_forms_assets();
	}

	if ( $is_customizer ) {
		wp_enqueue_style( 'cl-customizer', plugin_dir_url(__FILE__) . '/admin/css/customizer.css', array(), $dapper_cl_version );
	}
}

function dapper_cl_enqueue_forms_assets() {
	wp_enqueue_style( 'cl-editor' );
	wp_enqueue_script( 'cl-editor' );

	if ( ! did_action( 'wp_enqueue_media' ) ) {
		wp_enqueue_media();
	}

	dapper_cl_maybe_load_wysiwyg();

	// TODO Remove when onDemand load will be ready
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker-alpha' );
	wp_enqueue_script( 'wplink' );
	wp_enqueue_style( 'editor-buttons' );
}

define('SOW_BUNDLE_VERSION', '1.6.1');
define('SOW_BUNDLE_BASE_FILE', plugin_dir_path(__FILE__));

// Allow JS suffix to be pre-set
if( !defined( 'SOW_BUNDLE_JS_SUFFIX' ) ) {
	define('SOW_BUNDLE_JS_SUFFIX', '.min');
}

if( !function_exists('siteorigin_widget_get_plugin_path') ) {
	require plugin_dir_path(__FILE__) . 'base/base.php';
	require plugin_dir_path(__FILE__) . 'icons/icons.php';
	//include get_template_directory().'/widgets/advanced-widgets/base/base.php';
	//include get_template_directory().'/widgets/advanced-widgets/icons/icons.php';
}

class DistinctiveThemes_SiteOrigin_Widgets_Bundle {

	private $widget_folders;

	/**
	 * @var array The array of default widgets.
	 */
	static $default_active_widgets = array(
		'button' => true,
		'google-map' => true,
		'image' => true,
		'slider' => false,
		'post-carousel' => false,
		'editor' => true,
	);

	function __construct(){
		add_action('admin_init', array($this, 'admin_activate_widget') );
		add_action('wp_ajax_so_widgets_bundle_manage', array($this, 'admin_ajax_manage_handler') );
		add_action('wp_ajax_sow_get_javascript_variables', array($this, 'admin_ajax_get_javascript_variables') );

		// Initialize the widgets, but do it fairly late
		add_action( 'plugins_loaded', array($this, 'set_plugin_textdomain'), 1 );
		add_action( 'after_setup_theme', array($this, 'get_widget_folders'), 11 );
		add_action( 'after_setup_theme', array($this, 'load_widget_plugins'), 11 );

		// Add the plugin_action_links links.
		add_action( 'plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links') );

		add_action( 'admin_init', array($this, 'plugin_version_check') );
		add_action( 'distinctivethemes_widgets_version_update', array( $this, 'handle_update' ), 10, 2 );

		// Actions for clearing widget cache
		add_action( 'switch_theme', array($this, 'clear_widget_cache') );
		add_action( 'activated_plugin', array($this, 'clear_widget_cache') );
		add_action( 'upgrader_process_complete', array($this, 'clear_widget_cache') );

		// These filters are used to activate any widgets that are missing.
		add_filter( 'distinctivethemes_panels_data', array($this, 'load_missing_widgets') );
		add_filter( 'distinctivethemes_panels_prebuilt_layout', array($this, 'load_missing_widgets') );
		add_filter( 'distinctivethemes_panels_widget_object', array($this, 'load_missing_widget'), 10, 2 );

		add_filter( 'wp_enqueue_scripts', array($this, 'enqueue_active_widgets_scripts') );
	}

	/**
	 * Get the single of this plugin
	 *
	 * @return DistinctiveThemes_SiteOrigin_Widgets_Bundle
	 */
	static function single() {
		static $single;

		if( empty($single) ) {
			$single = new DistinctiveThemes_SiteOrigin_Widgets_Bundle();
		}

		return $single;
	}

	/**
	 * Set the text domain for the plugin
	 *
	 * @action plugins_loaded
	 */
	function set_plugin_textdomain(){
		load_plugin_textdomain('dapper-pro', false, dirname( plugin_basename( __FILE__ ) ). '/languages/');
	}

	/**
	 * This clears the file cache.
	 *
	 * @action admin_init
	 */
	function plugin_version_check(){

		$active_version = get_option( 'siteorigin_widget_bundle_version' );

		$is_new = empty($active_version) || version_compare( $active_version, SOW_BUNDLE_VERSION, '<' );
		$is_new = apply_filters( 'distinctivethemes_widgets_is_new_version', $is_new );

		if( $is_new ) {

			update_option( 'siteorigin_widget_bundle_version', SOW_BUNDLE_VERSION );
			// If this is a new version, then trigger an action to let widgets handle the updates.
			do_action( 'distinctivethemes_widgets_version_update', SOW_BUNDLE_VERSION, $active_version );
			$this->clear_widget_cache();
		}

	}

	/**
	 * This should call any necessary functions when the plugin has been updated.
	 *
	 * @action distinctivethemes_widgets_version_update
	 */
	function handle_update($old_version, $new_version) {
		//Always check for new widgets.
		$this->check_for_new_widgets();
	}

	/**
	 * Deletes any CSS generated by/for the widgets.
	 * Called on 'upgrader_process_complete', 'switch_theme', and 'activated_plugin' actions.
	 * Can also be called directly on the `DistinctiveThemes_SiteOrigin_Widgets_Bundle` singleton class.
	 *
	 * @action upgrader_process_complete Occurs after any theme, plugin or the WordPress core is updated to a new version.
	 * @action switch_theme Occurs after switching to a different theme.
	 * @action activated_plugin Occurs after a plugin has been activated.
	 *
	 */
	function clear_widget_cache() {
		// Remove all cached CSS for SiteOrigin Widgets
		if( function_exists('WP_Filesystem') && WP_Filesystem() ) {
			global $wp_filesystem;
			$upload_dir = wp_upload_dir();

			// Remove any old widget cache files, if they exist.
			$list = $wp_filesystem->dirlist( $upload_dir['basedir'] . '/dapper-widgets/' );
			if( !empty($list) ) {
				foreach($list as $file) {
					// Delete the file
					$wp_filesystem->delete( $upload_dir['basedir'] . '/dapper-widgets/' . $file['name'] );
				}
			}
		}
	}

	/**
	 * Setup and return the widget folders
	 */
	function check_for_new_widgets() {
		// get list of available widgets
		$widgets = array_keys( $this->get_widgets_list() );
		// get option for previously installed widgets
		$old_widgets = get_option( 'distinctivethemes_widgets_old_widgets' );
		// if this has never been set before, it's probably a new installation so we don't want to notify for all the widgets
		if ( empty( $old_widgets ) ) {
			update_option( 'distinctivethemes_widgets_old_widgets', implode( ',', $widgets ) );
			return;
		}
		$old_widgets = explode( ',', $old_widgets );
		$new_widgets = array_diff( $widgets, $old_widgets );
		if ( ! empty( $new_widgets ) ) {
			update_option( 'distinctivethemes_widgets_new_widgets', $new_widgets );
			update_option( 'distinctivethemes_widgets_old_widgets', implode( ',', $widgets ) );
		}
	}

	/**
	 * Setup and return the widget folders
	 */
	function get_widget_folders(){
		if( empty($this->widget_folders) ) {
			// We can use this filter to add more folders to use for widgets
			$this->widget_folders = apply_filters('distinctivethemes_widgets_widget_folders', array(
				plugin_dir_path(__FILE__).'widgets/'
			) );
		}

		return $this->widget_folders;
	}

	/**
	 * Load all the widgets if their plugins are not already active.
	 *
	 * @action plugins_loaded
	 */
	function load_widget_plugins(){

		// Load all the widget we currently have active and filter them
		$active_widgets = $this->get_active_widgets();
		$widget_folders = $this->get_widget_folders();

		foreach( $active_widgets as $widget_id => $active ) {
			if( empty($active) ) continue;

			foreach( $widget_folders as $folder ) {
				if ( !file_exists($folder . $widget_id.'/'.$widget_id.'.php') ) continue;

				// Include this widget file
				include_once $folder . $widget_id.'/'.$widget_id.'.php';
			}

		}
	}

	/**
	 * Get a list of currently active widgets.
	 *
	 * @param bool $filter
	 *
	 * @return mixed|void
	 */
	function get_active_widgets( $filter = true ){
		// Basic caching of the current active widgets
		$active_widgets = wp_cache_get( 'active_widgets', 'distinctivethemes_widgets' );

		if( empty($active_widgets) ) {
			$active_widgets = get_option( 'distinctivethemes_widgets_active', array() );
			$active_widgets = wp_parse_args( $active_widgets, apply_filters( 'distinctivethemes_widgets_default_active', self::$default_active_widgets ) );

			// Migrate any old names
			foreach ( $active_widgets as $widget_name => $is_active ) {
				if ( substr( $widget_name, 0, 3 ) !== 'dapper-so-' ) {
					continue;
				}
				if ( preg_match( '/dapper-so-([a-z\-]+)-widget/', $widget_name, $matches ) && ! isset( $active_widgets[ $matches[1] ] ) ) {
					unset( $active_widgets[ $widget_name ] );
					$active_widgets[ $matches[1] ] = $is_active;
				}
			}

			if ( $filter ) {
				$active_widgets = apply_filters( 'distinctivethemes_widgets_active_widgets', $active_widgets );
			}

			wp_cache_add( 'active_widgets', $active_widgets, 'distinctivethemes_widgets' );
		}

		return $active_widgets;
	}

	/**
	 * Enqueue the admin page stuff.
	 */
	function admin_enqueue_scripts($prefix) {
		if( $prefix != 'plugins_page_dapper-so-widgets-plugins' ) return;
		wp_enqueue_style( 'dapper-widgets-manage-admin', plugin_dir_path(__FILE__) . 'admin/admin.css', array(), SOW_BUNDLE_VERSION );
		wp_enqueue_script( 'dapper-widgets-trianglify', plugin_dir_path(__FILE__) . 'admin/trianglify' . SOW_BUNDLE_JS_SUFFIX . '.js', array(), SOW_BUNDLE_VERSION );
		wp_enqueue_script( 'dapper-widgets-manage-admin', plugin_dir_path(__FILE__) . 'admin/admin' . SOW_BUNDLE_JS_SUFFIX . '.js', array(), SOW_BUNDLE_VERSION );

		wp_localize_script( 'dapper-widgets-manage-admin', 'soWidgetsAdmin', array(
			'toggleUrl' => wp_nonce_url( admin_url('admin-ajax.php?action=so_widgets_bundle_manage'), 'manage_so_widget' )
		) );
	}

	/**
	 * The fallback (from ajax) URL handler for activating or deactivating a widget
	 */
	function admin_activate_widget() {
		if(
			!empty($_GET['page'])
			&& $_GET['page'] == 'dapper-so-widgets-plugins'
			&& !empty( $_GET['widget_action'] ) && !empty( $_GET['widget'] )
			&& isset($_GET['_wpnonce'])
			&& wp_verify_nonce($_GET['_wpnonce'], 'siteorigin_widget_action')
		) {

			switch($_GET['widget_action']) {
				case 'activate':
					$this->activate_widget( $_GET['widget'] );
					break;

				case 'deactivate':
					$this->deactivate_widget( $_GET['widget'] );
					break;
			}

			// Redirect and clear all the args
			wp_redirect( add_query_arg( array(
				'_wpnonce' => false,
				'widget_action_done' => 'true',
			) ) );

		}
	}

	/**
	 * Handler for activating and deactivating widgets.
	 *
	 * @action wp_ajax_so_widgets_bundle_manage
	 */
	function admin_ajax_manage_handler(){
		if( !wp_verify_nonce($_GET['_wpnonce'], 'manage_so_widget') ) exit();
		if( ! current_user_can( apply_filters( 'distinctivethemes_widgets_admin_menu_capability', 'manage_options' ) ) ) exit();
		if( empty($_POST['widget']) ) exit();

		if( !empty($_POST['active']) ) $this->activate_widget($_POST['widget']);
		else $this->deactivate_widget( $_POST['widget'] );

		// Send a kind of dummy response.
		header('content-type: application/json');
		echo json_encode( array('done' => true) );
		exit();
	}

	function activate_widget( $widget_id, $include = true ){
		$exists = false;
		foreach( $this->widget_folders as $folder ) {
			if( !file_exists($folder . $widget_id . '/' . $widget_id . '.php') ) continue;
			$exists = true;
		}

		if( !$exists ) return false;

		// There are times when we activate several widgets at once, so clear the cache.
		wp_cache_delete( 'siteorigin_widgets_active', 'options' );
		$active_widgets = $this->get_active_widgets();
		$active_widgets[$widget_id] = true;
		update_option( 'siteorigin_widgets_active', $active_widgets );
		wp_cache_delete( 'active_widgets', 'siteorigin_widgets' );

		// If we don't want to include the widget files, then our job here is done.
		if( !$include ) return;

		// Now, lets actually include the files
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		foreach( $this->widget_folders as $folder ) {
			if( !file_exists($folder . $widget_id . '/' . $widget_id . '.php') ) continue;
			include_once $folder . $widget_id . '/' . $widget_id . '.php';

			if( has_action('widgets_init') ) {
				DistinctiveThemes_SiteOrigin_Widgets_Widget_Manager::single()->widgets_init();
			}
		}

		return true;
	}
	/**
	 * Include a widget that might not have been registered.
	 *
	 * @param $widget_id
	 *
	 * @return bool
	 */
	function include_widget( $widget_id ) {
		$folders = $this->get_widget_folders();

		foreach( $folders as $folder ) {
			if( !file_exists($folder . $widget_id . '/' . $widget_id . '.php') ) continue;
			include_once $folder . $widget_id . '/' . $widget_id . '.php';
			return true;
		}

		return false;
	}

	/**
	 * Gets a list of all available widgets
	 */
	function get_widgets_list(){
		$active = $this->get_active_widgets();
		$folders = $this->get_widget_folders();

		$default_headers = array(
			'Name' => 'Widget Name',
			'Description' => 'Description',
			'Author' => 'Author',
			'AuthorURI' => 'Author URI',
			'WidgetURI' => 'Widget URI',
			'VideoURI' => 'Video URI',
		);

		$widgets = array();
		foreach( $folders as $folder ) {

			$files = glob( $folder.'*/*.php' );
			foreach($files as $file) {
				$widget = get_file_data( $file, $default_headers, 'siteorigin-widget' );
				//skip the file if it's missing a name
				if ( empty( $widget['Name'] ) ) {
					continue;
				}
				$f = pathinfo($file);
				$id = $f['filename'];

				$widget['ID'] = $id;
				$widget['Active'] = !empty($active[$id]);
				$widget['File'] = $file;

				$widgets[$file] = $widget;
			}

		}

		// Sort the widgets alphabetically
		uasort( $widgets, array($this, 'widget_uasort') );
		return $widgets;
	}

	/**
	 * Sorting function to sort widgets by name
	 *
	 * @param $widget_a
	 * @param $widget_b
	 *
	 * @return int
	 */
	function widget_uasort($widget_a, $widget_b) {
		return $widget_a['Name'] > $widget_b['Name'] ? 1 : -1;
	}

	/**
	 * Look in Page Builder data for any missing widgets.
	 *
	 * @param $data
	 *
	 * @return mixed
	 *
	 * @action distinctivethemes_panels_data
	 */
	function load_missing_widgets($data){
		if(empty($data['widgets'])) return $data;

		global $wp_widget_factory;

		foreach($data['widgets'] as $widget) {
			if( empty($widget['panels_info']['class']) ) continue;
			if( !empty($wp_widget_factory->widgets[$widget['panels_info']['class']] ) ) continue;

			$class = $widget['panels_info']['class'];
			if( preg_match('/DistinctiveThemes_SiteOrigin_Widget_([A-Za-z]+)_Widget/', $class, $matches) ) {
				$name = $matches[1];
				$id = strtolower( implode( '-', array_filter( preg_split( '/(?=[A-Z])/', $name ) ) ) );
				$this->activate_widget($id, true);
			}
		}

		return $data;
	}

	/**
	 * Attempt to load a single missing widget.
	 *
	 * @param $the_widget
	 * @param $class
	 *
	 * @return
	 */
	function load_missing_widget($the_widget, $class){
		// We only want to worry about missing widgets
		if( !empty($the_widget) ) return $the_widget;

		if( preg_match('/DistinctiveThemes_SiteOrigin_Widget_([A-Za-z]+)_Widget/', $class, $matches) ) {
			$name = $matches[1];
			$id = strtolower( implode( '-', array_filter( preg_split( '/(?=[A-Z])/', $name ) ) ) );
			$this->activate_widget($id, true);
			global $wp_widget_factory;
			if( !empty($wp_widget_factory->widgets[$class]) ) return $wp_widget_factory->widgets[$class];
		}

		return $the_widget;
	}

	/**
	 * Add action links.
	 */
	function plugin_action_links($links){
		unset( $links['edit'] );
		$links['manage'] = '<a href="' . admin_url('plugins.php?page=dapper-so-widgets-plugins') . '">'.__('Manage Widgets', 'dapper-pro').'</a>';
		$links['support'] = '<a href="https://siteorigin.com/thread/" target="_blank">'.__('Support', 'dapper-pro').'</a>';
		return $links;
	}

	/**
	 * Ensure active widgets' scripts are enqueued at the right time.
	 */
	function enqueue_active_widgets_scripts() {
		global $wp_registered_widgets;
		$sidebars_widgets = wp_get_sidebars_widgets();
		if( empty($sidebars_widgets) ) return;
		foreach( $sidebars_widgets as $sidebar => $widgets ) {
			if ( ! empty( $widgets ) && $sidebar !== "wp_inactive_widgets") {
				foreach ( $widgets as $i => $id ) {
					if ( ! empty( $wp_registered_widgets[$id] ) ) {
						$widget = $wp_registered_widgets[$id]['callback'][0];
						if ( !empty($widget) && is_object($widget) && is_subclass_of($widget, 'DistinctiveThemes_SiteOrigin_Widget') && is_active_widget( false, false, $widget->id_base ) ) {
							$opt_wid = get_option( 'widget_' . $widget->id_base );
							preg_match( '/-([0-9]+$)/', $id, $num_match );
							$widget_instance = $opt_wid[ $num_match[1] ];
							$widget->enqueue_frontend_scripts( $widget_instance);
							$widget->generate_and_enqueue_instance_styles( $widget_instance );
						}
					}
				}
			}
		}
	}
}

// create the initial single
DistinctiveThemes_SiteOrigin_Widgets_Bundle::single();

// Initialize the Meta Box Manager
global $sow_meta_box_manager;
$sow_meta_box_manager = DistinctiveThemes_SiteOrigin_Widget_Meta_Box_Manager::single();


// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

if (!class_exists('DistinctiveThemes_Livemesh_SiteOrigin_Widgets')) :

    /**
     * Main DistinctiveThemes_Livemesh_SiteOrigin_Widgets Class
     *
     */
    final class DistinctiveThemes_Livemesh_SiteOrigin_Widgets {

        /** Singleton *************************************************************/

        private static $instance;

        /**
         * Main DistinctiveThemes_Livemesh_SiteOrigin_Widgets Instance
         *
         * Insures that only one instance of DistinctiveThemes_Livemesh_SiteOrigin_Widgets exists in memory at any one
         * time. Also prevents needing to define globals all over the place.
         */
        public static function instance() {
            if (!isset(self::$instance) && !(self::$instance instanceof DistinctiveThemes_Livemesh_SiteOrigin_Widgets)) {
                self::$instance = new DistinctiveThemes_Livemesh_SiteOrigin_Widgets;
                self::$instance->setup_constants();

                add_action('plugins_loaded', array(self::$instance, 'load_plugin_textdomain'));

                self::$instance->includes();

                self::$instance->hooks();


            }
            return self::$instance;
        }

        /**
         * Throw error on object clone
         *
         * The whole idea of the singleton design pattern is that there is a single
         * object therefore, we don't want the object to be cloned.
         */
        public function __clone() {
            // Cloning instances of the class is forbidden
            _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'dapper-pro'), '1.6');
        }

        /**
         * Disable unserializing of the class
         *
         */
        public function __wakeup() {
            // Unserializing instances of the class is forbidden
            _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'dapper-pro'), '1.6');
        }

        /**
         * Setup plugin constants
         *
         */
        private function setup_constants() {

            // Plugin version
            if (!defined('DistinctiveThemes_LSOW_VERSION')) {
                define('DistinctiveThemes_LSOW_VERSION', '1.3');
            }

            // Plugin Folder Path
            if (!defined('DistinctiveThemes_LSOW_PLUGIN_DIR')) {
                define('DistinctiveThemes_LSOW_PLUGIN_DIR', plugin_dir_path(__FILE__));
            }

            // Plugin Folder URL
            if (!defined('DistinctiveThemes_LSOW_PLUGIN_URL')) {
                define('DistinctiveThemes_LSOW_PLUGIN_URL', plugin_dir_url(__FILE__));
            }

            // Plugin Root File
            if (!defined('DistinctiveThemes_LSOW_PLUGIN_FILE')) {
                define('DistinctiveThemes_LSOW_PLUGIN_FILE', __FILE__);
            }
        }

        /**
         * Include required files
         *
         */
        private function includes() {

            require_once DistinctiveThemes_LSOW_PLUGIN_DIR . 'includes/class-lsow-setup.php';
            require_once DistinctiveThemes_LSOW_PLUGIN_DIR . 'includes/helper-functions.php';

        }

        /**
         * Load Plugin Text Domain
         *
         * Looks for the plugin translation files in certain directories and loads
         * them to allow the plugin to be localised
         */
        public function load_plugin_textdomain() {

            $lang_dir = apply_filters('lsow_so_widgets_lang_dir', trailingslashit(DistinctiveThemes_LSOW_PLUGIN_DIR . 'languages'));

            // Traditional WordPress plugin locale filter
            $locale = apply_filters('plugin_locale', get_locale(), 'dapper-pro');
            $mofile = sprintf('%1$s-%2$s.mo', 'dapper-pro', $locale);

            // Setup paths to current locale file
            $mofile_local = $lang_dir . $mofile;

            if (file_exists($mofile_local)) {
                // Look in the /wp-content/plugins/livemesh-so-widgets/languages/ folder
                load_textdomain('dapper-pro', $mofile_local);
            }
            else {
                // Load the default language files
                load_plugin_textdomain('dapper-pro', false, $lang_dir);
            }

            return false;
        }

        /**
         * Setup the default hooks and actions
         */
        private function hooks() {

            add_action('admin_enqueue_scripts', array($this, 'load_admin_scripts'), 100);
        }

        /**
         * Load Admin Scripts/Styles
         *
         */
        public function load_admin_scripts() {

            // Use minified libraries if SCRIPT_DEBUG is turned off
            $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

            wp_register_style('lsow-admin-styles', plugin_dir_url(__FILE__) . 'assets/css/lsow-admin.css', array(), DistinctiveThemes_LSOW_VERSION);
            wp_enqueue_style('lsow-admin-styles');

            wp_register_script('lsow-admin-scripts', plugin_dir_url(__FILE__) . 'assets/js/lsow-admin' . $suffix . '.js', array(), DistinctiveThemes_LSOW_VERSION, true);
            wp_enqueue_script('lsow-admin-scripts');

            wp_enqueue_script('jquery-ui-datepicker');
        }

    }

endif; // End if class_exists check


/**
 * The main function responsible for returning the one true DistinctiveThemes_Livemesh_SiteOrigin_Widgets
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $lsow = DistinctiveThemes_LSOW(); ?>
 */
function DistinctiveThemes_LSOW() {
    return DistinctiveThemes_Livemesh_SiteOrigin_Widgets::instance();
}

// Get DistinctiveThemes_LSOW Running
DistinctiveThemes_LSOW();