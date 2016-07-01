<?php
/*
Plugin Name: Distinctive Page Composer
Plugin URI: https://distinctivethemes.com/
Description: A drag and drop, responsive page builder that simplifies building your website. A fork of http://distinctivethemes.com/page-builder/
Version: 1.0.0
Author: DistinctiveThemes
Author URI: https://distinctivethemes.com
License: GPL3
License URI: http://www.gnu.org/licenses/gpl.html
*/

define('DISTINCTIVETHEMES_PANELS_VERSION', '2.4.9');
if ( ! defined('DISTINCTIVETHEMES_PANELS_JS_SUFFIX' ) ) {
	define('DISTINCTIVETHEMES_PANELS_JS_SUFFIX', '.min');
}
define('DISTINCTIVETHEMES_PANELS_VERSION_SUFFIX', '-24');
define('DISTINCTIVETHEMES_PANELS_BASE_FILE', __FILE__);
//define('SOW_BUNDLE_BASE_FILE', __FILE__);

define('DISTINCTIVETHEMES_PANELS_BASE_DIR', plugin_dir_path(__FILE__) . '/');
define('DISTINCTIVETHEMES_PANELS_BASE_DIR_NOSLASH', plugin_dir_path(__FILE__));

// All the basic settings
require_once plugin_dir_path(__FILE__) . 'settings/settings.php';

// Include all the basic widgets
require_once plugin_dir_path(__FILE__) . 'widgets/basic.php';
require_once plugin_dir_path(__FILE__) . 'widgets/migration.php';

require_once plugin_dir_path(__FILE__) . 'inc/css.php';
require_once plugin_dir_path(__FILE__) . 'inc/revisions.php';
require_once plugin_dir_path(__FILE__) . 'inc/styles.php';
require_once plugin_dir_path(__FILE__) . 'inc/default-styles.php';
require_once plugin_dir_path(__FILE__) . 'inc/widgets.php';
require_once plugin_dir_path(__FILE__) . 'inc/plugin-activation.php';
require_once plugin_dir_path(__FILE__) . 'inc/admin-actions.php';
require_once plugin_dir_path(__FILE__) . 'inc/sidebars-emulator.php';

require_once plugin_dir_path(__FILE__) . 'background-videos.php';
require_once plugin_dir_path(__FILE__) . 'advanced-widgets/advanced-widgets.php';


if( defined('DISTINCTIVETHEMES_PANELS_DEV') && DISTINCTIVETHEMES_PANELS_DEV ) include plugin_dir_path(__FILE__).'inc/debug.php';

/**
 * Hook for activation of Page Builder.
 */
function distinctivethemes_panels_activate(){
	add_option('distinctivethemes_panels_initial_version', DISTINCTIVETHEMES_PANELS_VERSION, '', 'no');
}
register_activation_hook(__FILE__, 'distinctivethemes_panels_activate');

/**
 * Initialize the Page Builder.
 */
function distinctivethemes_panels_init(){
	$bundled = distinctivethemes_panels_setting('bundled-widgets');
	if( !$bundled ) return;

	if( !defined('DISTINCTIVETHEMES_PANELS_LEGACY_WIDGETS_ACTIVE') && ( !is_admin() || basename($_SERVER["SCRIPT_FILENAME"]) != 'plugins.php') ) {
		// Include the bundled widgets if the Legacy Widgets plugin isn't active.
		include plugin_dir_path(__FILE__).'widgets/widgets.php';
	}
}
add_action('plugins_loaded', 'distinctivethemes_panels_init');

/**
 * Initialize the language files
 */
function distinctivethemes_panels_init_lang(){
	load_plugin_textdomain('distinctivethemes-panels', false, dirname( plugin_basename( __FILE__ ) ). '/lang/');
}
add_action('plugins_loaded', 'distinctivethemes_panels_init_lang');

/**
 * Add the admin menu entries
 */
function distinctivethemes_panels_admin_menu(){
	if( !distinctivethemes_panels_setting( 'home-page' ) ) return;

	add_theme_page(
		__( 'Custom Home Page Builder', 'distinctivethemes-panels' ),
		__( 'Home Page', 'distinctivethemes-panels' ),
		'edit_theme_options',
		'so_panels_home_page',
		'distinctivethemes_panels_render_admin_home_page'
	);
}
add_action('admin_menu', 'distinctivethemes_panels_admin_menu');

/**
 * Render the page used to build the custom home page.
 */
function distinctivethemes_panels_render_admin_home_page(){
	// We need a global post for some features in Page Builder (eg history)
	global $post;

	$home_page_id = get_option( 'page_on_front' );
	if( empty($home_page_id) ) $home_page_id = get_option( 'distinctivethemes_panels_home_page_id' );

	$home_page = get_post( $home_page_id );
	if( !empty($home_page) && get_post_meta( $home_page->ID, 'panels_data', true ) != '' ) {
		$post = $home_page;
	}

	$panels_data = distinctivethemes_panels_get_current_admin_panels_data();
	include plugin_dir_path(__FILE__).'tpl/admin-home-page.php';
}

/**
 * Callback to register the Page Builder Metaboxes
 */
function distinctivethemes_panels_metaboxes() {
	foreach( distinctivethemes_panels_setting( 'post-types' ) as $type ){
		add_meta_box(
			'so-panels-panels',
			__( 'Page Builder', 'distinctivethemes-panels' ),
			'distinctivethemes_panels_metabox_render',
			$type,
			'advanced',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'distinctivethemes_panels_metaboxes' );

/**
 * Save home page
 */
function distinctivethemes_panels_save_home_page(){
	if( !isset($_POST['_sopanels_home_nonce'] ) || !wp_verify_nonce($_POST['_sopanels_home_nonce'], 'save') ) return;
	if( !current_user_can('edit_theme_options') ) return;
	if( !isset( $_POST['panels_data'] ) ) return;

	// Check that the home page ID is set and the home page exists
	$page_id = get_option( 'page_on_front' );
	if( empty($page_id) ) $page_id = get_option( 'distinctivethemes_panels_home_page_id' );

	$post_content = wp_unslash( $_POST['post_content'] );

	if ( !$page_id || get_post_meta( $page_id, 'panels_data', true ) == '' ) {
		// Lets create a new page
		$page_id = wp_insert_post( array(
			// TRANSLATORS: This is the default name given to a user's home page
			'post_title' => __( 'Home Page', 'distinctivethemes-panels' ),
			'post_status' => !empty($_POST['distinctivethemes_panels_home_enabled']) ? 'publish' : 'draft',
			'post_type' => 'page',
			'post_content' => $post_content,
			'comment_status' => 'closed',
		) );
		update_option( 'page_on_front', $page_id );
		update_option( 'distinctivethemes_panels_home_page_id', $page_id );

		// Action triggered when creating a new home page through the custom home page interface
		do_action( 'distinctivethemes_panels_create_home_page', $page_id );
	} else {
		// `wp_insert_post` does it's own sanitization, but it seems `wp_update_post` doesn't.
		$post_content = sanitize_post_field( 'post_content', $post_content, $page_id, 'db' );

		// Update the post with changed content to save revision if necessary.
		wp_update_post( array( 'ID' => $page_id, 'post_content' => $post_content ) );
	}

	$page = get_post( $page_id );

	// Save the updated page data
	$panels_data = json_decode( wp_unslash( $_POST['panels_data'] ), true);
	$panels_data['widgets'] = distinctivethemes_panels_process_raw_widgets($panels_data['widgets']);
	$panels_data = distinctivethemes_panels_styles_sanitize_all( $panels_data );
	$panels_data = apply_filters( 'distinctivethemes_panels_data_pre_save', $panels_data, $page, $page_id );

	update_post_meta( $page_id, 'panels_data', $panels_data );

	$template = get_post_meta( $page_id, '_wp_page_template', true );
	$home_template = distinctivethemes_panels_setting( 'home-template' );
	if( ( $template == '' || $template == 'default' ) && !empty($home_template) ) {
		// Set the home page template
		update_post_meta( $page_id, '_wp_page_template', $home_template );
	}

	if( !empty( $_POST['distinctivethemes_panels_home_enabled'] ) ) {
		update_option('show_on_front', 'page');
		update_option('page_on_front', $page_id);
		update_option('distinctivethemes_panels_home_page_id', $page_id);
		wp_publish_post($page_id);
	}
	else {
		// We're disabling this home page
		update_option( 'show_on_front', 'posts' );

		// Change the post status to draft
		$post = get_post($page_id);
		if($post->post_status != 'draft') {
			global $wpdb;

			$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $post->ID ) );
			clean_post_cache( $post->ID );

			$old_status = $post->post_status;
			$post->post_status = 'draft';
			wp_transition_post_status( 'draft', $old_status, $post );

			do_action( 'edit_post', $post->ID, $post );
			do_action( "save_post_{$post->post_type}", $post->ID, $post, true );
			do_action( 'save_post', $post->ID, $post, true );
			do_action( 'wp_insert_post', $post->ID, $post, true );
		}
	}
}
add_action('admin_init', 'distinctivethemes_panels_save_home_page');

/**
 * After the theme is switched, change the template on the home page if the theme supports home page functionality.
 */
function distinctivethemes_panels_update_home_on_theme_change(){
	$page_id = get_option( 'page_on_front' );
	if( empty($page_id) ) $page_id = get_option( 'distinctivethemes_panels_home_page_id' );

	if( distinctivethemes_panels_setting( 'home-page' ) && distinctivethemes_panels_setting( 'home-template' ) && $page_id && get_post_meta( $page_id, 'panels_data', true ) !== '' ) {
		// Lets update the home page to use the home template that this theme supports
		update_post_meta( $page_id, '_wp_page_template', distinctivethemes_panels_setting( 'home-template' ) );
	}
}
add_action('after_switch_theme', 'distinctivethemes_panels_update_home_on_theme_change');

/**
 * @return mixed|void Are we currently viewing the home page
 */
function distinctivethemes_panels_is_home(){
	$home = ( is_front_page() && is_page() && get_option('show_on_front') == 'page' && get_option('page_on_front') == get_the_ID() && get_post_meta( get_the_ID(), 'panels_data' ) );
	return apply_filters('distinctivethemes_panels_is_home', $home);
}

/**
 * Check if we're currently viewing a page builder page.
 *
 * @param bool $can_edit Also check if the user can edit this page
 * @return bool
 */
function distinctivethemes_panels_is_panel($can_edit = false){
	// Check if this is a panel
	$is_panel =  ( distinctivethemes_panels_is_home() || ( is_singular() && get_post_meta(get_the_ID(), 'panels_data', false) ) );
	return $is_panel && (!$can_edit || ( (is_singular() && current_user_can('edit_post', get_the_ID())) || ( distinctivethemes_panels_is_home() && current_user_can('edit_theme_options') ) ));
}

/**
 * Render a panel metabox.
 *
 * @param $post
 */
function distinctivethemes_panels_metabox_render( $post ) {
	$panels_data = distinctivethemes_panels_get_current_admin_panels_data();
	include plugin_dir_path(__FILE__) . 'tpl/metabox-panels.php';
}

/**
 * Check if we should load the DistinctiveThemes: scripts and styles
 *
 * @return mixed|void
 */
function distinctivethemes_panels_is_admin_page(){
	$screen = get_current_screen();
	$is_panels_page = ( $screen->base == 'post' && in_array( $screen->id, distinctivethemes_panels_setting('post-types') ) ) || $screen->base == 'appearance_page_so_panels_home_page' || $screen->base == 'widgets' || $screen->base == 'customize';
	return apply_filters('distinctivethemes_panels_is_admin_page', $is_panels_page );
}

/**
 * Enqueue the panels admin scripts
 *
 * @param string $prefix
 * @param bool $force Should we force the enqueues
 *
 * @action admin_print_scripts-post-new.php
 * @action admin_print_scripts-post.php
 * @action admin_print_scripts-appearance_page_so_panels_home_page
 */
function distinctivethemes_panels_admin_enqueue_scripts( $prefix = '', $force = false ) {
	$screen = get_current_screen();
	if ( $force || distinctivethemes_panels_is_admin_page() ) {
		// Media is required for row styles
		wp_enqueue_media();
		wp_enqueue_script( 'so-panels-admin', plugin_dir_url(__FILE__) . 'js/distinctivethemes-panels' . DISTINCTIVETHEMES_PANELS_VERSION_SUFFIX . DISTINCTIVETHEMES_PANELS_JS_SUFFIX . '.js', array( 'jquery', 'jquery-ui-resizable', 'jquery-ui-sortable', 'jquery-ui-draggable', 'underscore', 'backbone', 'plupload', 'plupload-all' ), DISTINCTIVETHEMES_PANELS_VERSION, true );
		add_action( 'admin_footer', 'distinctivethemes_panels_js_templates' );

		$widgets = distinctivethemes_panels_get_widgets();

		$directory_enabled = get_user_meta( get_current_user_id(), 'so_panels_directory_enabled', true );

		wp_localize_script( 'so-panels-admin', 'panelsOptions', array(
			'ajaxurl' => wp_nonce_url( admin_url('admin-ajax.php'), 'panels_action', '_panelsnonce' ),
			'widgets' => $widgets,
			'widget_dialog_tabs' => apply_filters( 'distinctivethemes_panels_widget_dialog_tabs', array(
				0 => array(
					'title' => __('All Widgets', 'distinctivethemes-panels'),
					'filter' => array(
						'installed' => true,
						'groups' => ''
					)
				)
			) ),
			'row_layouts' => apply_filters( 'distinctivethemes_panels_row_layouts', array() ),
			'directory_enabled' => !empty( $directory_enabled ),
			'copy_content' => distinctivethemes_panels_setting( 'copy-content' ),

			// Settings for the contextual menu
			'contextual' => array(
				// Developers can change which widgets are displayed by default using this filter
				'default_widgets' => apply_filters( 'distinctivethemes_panels_contextual_default_widgets', array(
					'DistinctiveThemes_Widget_Editor_Widget',
					'DistinctiveThemes_Widget_Button_Widget',
					'DistinctiveThemes_Widget_Image_Widget',
					'DistinctiveThemes_Panels_Widgets_Layout',
				) )
			),

			// General localization messages
			'loc' => array(
				'missing_widget' => array(
					'title' => __('Missing Widget', 'distinctivethemes-panels'),
					'description' => __("Page Builder doesn't know about this widget.", 'distinctivethemes-panels'),
				),
				'time' => array(
					// TRANSLATORS: Number of seconds since
					'seconds' => __('%d seconds', 'distinctivethemes-panels'),
					// TRANSLATORS: Number of minutes since
					'minutes' => __('%d minutes', 'distinctivethemes-panels'),
					// TRANSLATORS: Number of hours since
					'hours' => __('%d hours', 'distinctivethemes-panels'),

					// TRANSLATORS: A single second since
					'second' => __('%d second', 'distinctivethemes-panels'),
					// TRANSLATORS: A single minute since
					'minute' => __('%d minute', 'distinctivethemes-panels'),
					// TRANSLATORS: A single hour since
					'hour' => __('%d hour', 'distinctivethemes-panels'),

					// TRANSLATORS: Time ago - eg. "1 minute before".
					'ago' => __('%s before', 'distinctivethemes-panels'),
					'now' => __('Now', 'distinctivethemes-panels'),
				),
				'history' => array(
					// History messages
					'current' => __('Current', 'distinctivethemes-panels'),
					'revert' => __('Original', 'distinctivethemes-panels'),
					'restore' => __('Version restored', 'distinctivethemes-panels'),
					'back_to_editor' => __('Converted to editor', 'distinctivethemes-panels'),

					// Widgets
					// TRANSLATORS: Message displayed in the history when a widget is deleted
					'widget_deleted' => __('Widget deleted', 'distinctivethemes-panels'),
					// TRANSLATORS: Message displayed in the history when a widget is added
					'widget_added' => __('Widget added', 'distinctivethemes-panels'),
					// TRANSLATORS: Message displayed in the history when a widget is edited
					'widget_edited' => __('Widget edited', 'distinctivethemes-panels'),
					// TRANSLATORS: Message displayed in the history when a widget is duplicated
					'widget_duplicated' => __('Widget duplicated', 'distinctivethemes-panels'),
					// TRANSLATORS: Message displayed in the history when a widget position is changed
					'widget_moved' => __('Widget moved', 'distinctivethemes-panels'),

					// Rows
					// TRANSLATORS: Message displayed in the history when a row is deleted
					'row_deleted' => __('Row deleted', 'distinctivethemes-panels'),
					// TRANSLATORS: Message displayed in the history when a row is added
					'row_added' => __('Row added', 'distinctivethemes-panels'),
					// TRANSLATORS: Message displayed in the history when a row is edited
					'row_edited' => __('Row edited', 'distinctivethemes-panels'),
					// TRANSLATORS: Message displayed in the history when a row position is changed
					'row_moved' => __('Row moved', 'distinctivethemes-panels'),
					// TRANSLATORS: Message displayed in the history when a row is duplicated
					'row_duplicated' => __('Row duplicated', 'distinctivethemes-panels'),

					// Cells
					'cell_resized' => __('Cell resized', 'distinctivethemes-panels'),

					// Prebuilt
					'prebuilt_loaded' => __('Prebuilt layout loaded', 'distinctivethemes-panels'),
				),

				// general localization
				'prebuilt_loading' => __('Loading prebuilt layout', 'distinctivethemes-panels'),
				'confirm_use_builder' => __("Would you like to copy this editor's existing content to Page Builder?", 'distinctivethemes-panels'),
				'confirm_stop_builder' => __("Would you like to clear your Page Builder content and revert to using the standard visual editor?", 'distinctivethemes-panels'),
				// TRANSLATORS: This is the title for a widget called "Layout Builder"
				'layout_widget' => __('Layout Builder Widget', 'distinctivethemes-panels'),
				// TRANSLATORS: A standard confirmation message
				'dropdown_confirm' => __('Are you sure?', 'distinctivethemes-panels'),
				// TRANSLATORS: When a layout file is ready to be inserted. %s is the filename.
				'ready_to_insert' => __('%s is ready to insert.', 'distinctivethemes-panels'),

				// Everything for the contextual menu
				'contextual' => array(
					'add_widget_below' => __('Add Widget Below', 'distinctivethemes-panels'),
					'add_widget_cell' => __('Add Widget to Cell', 'distinctivethemes-panels'),
					'search_widgets' => __('Search Widgets', 'distinctivethemes-panels'),

					'add_row' => __('Add Row', 'distinctivethemes-panels'),
					'column' => __('Column', 'distinctivethemes-panels'),

					'widget_actions' => __( 'Widget Actions', 'distinctivethemes-panels' ),
					'widget_edit' => __( 'Edit Widget', 'distinctivethemes-panels' ),
					'widget_duplicate' => __( 'Duplicate Widget', 'distinctivethemes-panels' ),
					'widget_delete' => __( 'Delete Widget', 'distinctivethemes-panels' ),

					'row_actions' => __( 'Row Actions', 'distinctivethemes-panels' ),
					'row_edit' => __( 'Edit Row', 'distinctivethemes-panels' ),
					'row_duplicate' => __( 'Duplicate Row', 'distinctivethemes-panels' ),
					'row_delete' => __( 'Delete Row', 'distinctivethemes-panels' ),
				),
				'draft' => __( 'Draft', 'distinctivethemes-panels' ),
			),
			'plupload' => array(
				'max_file_size' => wp_max_upload_size().'b',
				'url'  => wp_nonce_url( admin_url('admin-ajax.php'), 'panels_action', '_panelsnonce' ),
				'flash_swf_url' => includes_url('js/plupload/plupload.flash.swf'),
				'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
				'filter_title' => __('Page Builder layouts', 'distinctivethemes-panels'),
				'error_message' => __('Error uploading or importing file.', 'distinctivethemes-panels'),
			),
			'wpColorPickerOptions' => apply_filters('distinctivethemes_panels_wpcolorpicker_options', array()),
			'prebuiltDefaultScreenshot' => plugin_dir_url( __FILE__ ) . 'css/images/prebuilt-default.png',
		));

		if( $screen->base != 'widgets' ) {
			// Render all the widget forms. A lot of widgets use this as a chance to enqueue their scripts
			$original_post = isset($GLOBALS['post']) ? $GLOBALS['post'] : null; // Make sure widgets don't change the global post.
			foreach($GLOBALS['wp_widget_factory']->widgets as $class => $widget_obj){
				ob_start();
				$return = $widget_obj->form( array() );
				do_action_ref_array( 'in_widget_form', array( &$widget_obj, &$return, array() ) );
				ob_clean();
			}
			$GLOBALS['post'] = $original_post;
		}

		// This gives panels a chance to enqueue scripts too, without having to check the screen ID.
		if( $screen->base != 'widgets' && $screen->base != 'customize' ) {
			do_action( 'distinctivethemes_panel_enqueue_admin_scripts' );
			do_action( 'sidebar_admin_setup' );
		}
	}
}
add_action( 'admin_print_scripts-post-new.php', 'distinctivethemes_panels_admin_enqueue_scripts' );
add_action( 'admin_print_scripts-post.php', 'distinctivethemes_panels_admin_enqueue_scripts' );
add_action( 'admin_print_scripts-appearance_page_so_panels_home_page', 'distinctivethemes_panels_admin_enqueue_scripts' );
add_action( 'admin_print_scripts-widgets.php', 'distinctivethemes_panels_admin_enqueue_scripts' );

/**
 * Print templates when in customizer
 */
function distinctivethemes_panels_customize_controls_print_footer_scripts() {
	distinctivethemes_panels_js_templates();
}
add_action( 'customize_controls_print_footer_scripts', 'distinctivethemes_panels_customize_controls_print_footer_scripts' );

/**
 * Get an array of all the available widgets.
 *
 * @return array
 */
function distinctivethemes_panels_get_widgets(){
	global $wp_widget_factory;
	$widgets = array();
	foreach($wp_widget_factory->widgets as $class => $widget_obj) {
		$widgets[$class] = array(
			'class' => $class,
			'title' => !empty($widget_obj->name) ? $widget_obj->name : __('Untitled Widget', 'distinctivethemes-panels'),
			'description' => !empty($widget_obj->widget_options['description']) ? $widget_obj->widget_options['description'] : '',
			'installed' => true,
			'groups' => array(),
		);

		// Get Page Builder specific widget options
		if( isset($widget_obj->widget_options['panels_title']) ) {
			$widgets[$class]['panels_title'] = $widget_obj->widget_options['panels_title'];
		}
		if( isset($widget_obj->widget_options['panels_groups']) ) {
			$widgets[$class]['groups'] = $widget_obj->widget_options['panels_groups'];
		}
		if( isset($widget_obj->widget_options['panels_icon']) ) {
			$widgets[$class]['icon'] = $widget_obj->widget_options['panels_icon'];
		}

	}

	// Other plugins can manipulate the list of widgets. Possibly to add recommended widgets
	$widgets = apply_filters('distinctivethemes_panels_widgets', $widgets);

	// Sort the widgets alphabetically
	uasort($widgets, 'distinctivethemes_panels_widgets_sorter');

	return $widgets;
}

/**
 * @param $a
 * @param $b
 *
 * @return int
 */
function distinctivethemes_panels_widgets_sorter($a, $b){
	if( empty($a['title']) ) return -1;
	if( empty($b['title']) ) return 1;
	return $a['title'] > $b['title'] ? 1 : -1;
}

/**
 * Display the templates for JS in the footer
 */
function distinctivethemes_panels_js_templates(){
	include plugin_dir_path(__FILE__).'tpl/js-templates.php';
}

/**
 * Enqueue the admin panel styles
 *
 * @param string $prefix
 * @param bool $force Should we force the enqueue
 *
 * @action admin_print_styles-post-new.php
 * @action admin_print_styles-post.php
 */
function distinctivethemes_panels_admin_enqueue_styles( $prefix = '', $force = false ) {
	if ( $force || distinctivethemes_panels_is_admin_page() ) {
		wp_enqueue_style( 'so-panels-admin', plugin_dir_url(__FILE__) . 'css/admin.css', array( 'wp-color-picker' ), DISTINCTIVETHEMES_PANELS_VERSION );
		wp_enqueue_style( 'so-panels-font-awesome', plugin_dir_url(__FILE__) . 'css/font-awesome/css/font-awesome.min', array( 'wp-color-picker' ), DISTINCTIVETHEMES_PANELS_VERSION );
		do_action( 'distinctivethemes_panel_enqueue_admin_styles' );
	}
}
add_action( 'admin_print_styles-post-new.php', 'distinctivethemes_panels_admin_enqueue_styles' );
add_action( 'admin_print_styles-post.php', 'distinctivethemes_panels_admin_enqueue_styles' );
add_action( 'admin_print_styles-appearance_page_so_panels_home_page', 'distinctivethemes_panels_admin_enqueue_styles' );
add_action( 'admin_print_styles-widgets.php', 'distinctivethemes_panels_admin_enqueue_styles' );

/**
 * Add a help tab to pages with panels.
 */
function distinctivethemes_panels_add_help_tab($prefix) {
	$screen = get_current_screen();
	if(
		( $screen->base == 'post' && ( in_array( $screen->id, distinctivethemes_panels_setting( 'post-types' ) ) || $screen->id == '') )
		|| ($screen->id == 'appearance_page_so_panels_home_page')
	) {
		$screen->add_help_tab( array(
			'id' => 'panels-help-tab', //unique id for the tab
			'title' => __( 'Page Builder', 'distinctivethemes-panels' ), //unique visible title for the tab
			'callback' => 'distinctivethemes_panels_add_help_tab_content'
		) );
	}
}
add_action('load-page.php', 'distinctivethemes_panels_add_help_tab', 12);
add_action('load-post-new.php', 'distinctivethemes_panels_add_help_tab', 12);
add_action('load-appearance_page_so_panels_home_page', 'distinctivethemes_panels_add_help_tab', 12);

/**
 * Display the content for the help tab.
 */
function distinctivethemes_panels_add_help_tab_content(){
	include plugin_dir_path(__FILE__) . 'tpl/help.php';
}

/**
 * Save the panels data
 *
 * @param $post_id
 * @param $post
 *
 * @action save_post
 */
function distinctivethemes_panels_save_post( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( empty( $_POST['_sopanels_nonce'] ) || !wp_verify_nonce( $_POST['_sopanels_nonce'], 'save' ) ) return;
	if ( !current_user_can( 'edit_post', $post_id ) ) return;
	if ( !isset( $_POST['panels_data'] ) ) return;

	if ( !wp_is_post_revision($post_id) ) {
		$panels_data = json_decode( wp_unslash( $_POST['panels_data'] ), true);
		$panels_data['widgets'] = distinctivethemes_panels_process_raw_widgets($panels_data['widgets']);
		$panels_data = distinctivethemes_panels_styles_sanitize_all( $panels_data );
		$panels_data = apply_filters( 'distinctivethemes_panels_data_pre_save', $panels_data, $post, $post_id );

		if( !empty( $panels_data['widgets'] ) || !empty($panels_data['grids']) ) {
			update_post_meta( $post_id, 'panels_data', $panels_data );
		}
		else {
			// There are no widgets or rows, so delete the panels data
			delete_post_meta( $post_id, 'panels_data' );
		}
	}
	else {
		// When previewing, we don't need to wp_unslash the panels_data post variable.
		$panels_data = json_decode( wp_unslash( $_POST['panels_data'] ), true);
		$panels_data['widgets'] = distinctivethemes_panels_process_raw_widgets($panels_data['widgets']);
		$panels_data = distinctivethemes_panels_styles_sanitize_all( $panels_data );
		$panels_data = apply_filters( 'distinctivethemes_panels_data_pre_save', $panels_data, $post, $post_id );

		// Because of issue #20299, we are going to save the preview into a different variable so we don't overwrite the actual data.
		// https://core.trac.wordpress.org/ticket/20299
		if( !empty( $panels_data['widgets'] ) ) {
			update_post_meta( $post_id, '_panels_data_preview', $panels_data );
		}
		else {
			delete_post_meta( $post_id, '_panels_data_preview' );
		}
	}
}
add_action( 'save_post', 'distinctivethemes_panels_save_post', 10, 2 );

/**
 * @param $value
 * @param $post_id
 * @param $meta_key
 *
 * @return mixed
 */
function distinctivethemes_panels_view_post_preview($value, $post_id, $meta_key){
	if( $meta_key == 'panels_data' && is_preview() && current_user_can( 'edit_post', $post_id ) ) {
		$panels_preview = get_post_meta($post_id, '_panels_data_preview');
		return !empty($panels_preview) ? $panels_preview : $value;
	}

	return $value;
}
add_filter('get_post_metadata', 'distinctivethemes_panels_view_post_preview', 10, 3);

/**
 * Process raw widgets that have come from the Page Builder front end.
 *
 * @param $widgets
 *
 * @return array
 */
function distinctivethemes_panels_process_raw_widgets($widgets) {
	if( empty( $widgets ) || ! is_array( $widgets ) ) {
		return array();
	}

	global $wp_widget_factory;

	for($i = 0; $i < count($widgets); $i++) {
		if( !is_array( $widgets[$i] ) ) {
			continue;
		}

		if( is_array( $widgets[$i] ) ) {
			$info = (array) ( is_array( $widgets[$i]['panels_info'] ) ? $widgets[$i]['panels_info'] : $widgets[$i]['info'] );
		}
		else {
			$info = array();
		}
		unset($widgets[$i]['info']);

		if( !empty($info['raw']) ) {
			if ( isset( $wp_widget_factory->widgets[ $info['class'] ] ) && method_exists( $info['class'], 'update' ) ) {
				$the_widget = $wp_widget_factory->widgets[ $info['class'] ];
				$widgets[$i] = $the_widget->update( $widgets[$i], $widgets[$i] );
				unset($info['raw']);
			}
		}

		$info['class'] = addslashes( $info['class'] );
		$widgets[$i]['panels_info'] = $info;
	}

	return $widgets;
}

/**
 * Get the home page panels layout data.
 *
 * @return mixed|void
 */
function distinctivethemes_panels_get_home_page_data(){
	$page_id = get_option( 'page_on_front' );
	if( empty($page_id) ) $page_id = get_option( 'distinctivethemes_panels_home_page_id' );
	if( empty($page_id) ) return false;

	$panels_data = get_post_meta( $page_id, 'panels_data', true );
	if( is_null( $panels_data ) ){
		// Load the default layout
		$layouts = apply_filters( 'distinctivethemes_panels_prebuilt_layouts', array() );
		$panels_data = !empty($layouts['default_home']) ? $layouts['default_home'] : current($layouts);
	}

	return $panels_data;
}

/**
 * Get the Page Builder data for the current admin page.
 *
 * @return array
 */
function distinctivethemes_panels_get_current_admin_panels_data( ){
	$screen = get_current_screen();

	// Localize the panels with the panels data
	if($screen->base == 'appearance_page_so_panels_home_page'){
		$home_page_id = get_option( 'page_on_front' );
		if( empty($home_page_id) ) $home_page_id = get_option( 'distinctivethemes_panels_home_page_id' );

		$panels_data = !empty($home_page_id) ? get_post_meta( $home_page_id, 'panels_data', true ) : null;

		if( is_null( $panels_data ) ){
			// Load the default layout
			$layouts = apply_filters( 'distinctivethemes_panels_prebuilt_layouts', array() );

			$home_name = distinctivethemes_panels_setting('home-page-default') ? distinctivethemes_panels_setting('home-page-default') : 'home';
			$panels_data = !empty($layouts[$home_name]) ? $layouts[$home_name] : current($layouts);
		}
		elseif( empty( $panels_data ) ) {
			// The current page_on_front isn't using page builder
			return false;
		}

		$panels_data = apply_filters( 'distinctivethemes_panels_data', $panels_data, 'home');
	}
	else{
		global $post;
		$panels_data = get_post_meta( $post->ID, 'panels_data', true );
		$panels_data = apply_filters( 'distinctivethemes_panels_data', $panels_data, $post->ID );
	}

	if ( empty( $panels_data ) ) $panels_data = array();

	return $panels_data;
}

/**
 * Generate the CSS for the page layout.
 *
 * @param $post_id
 * @param $panels_data
 * @return string
 */
function distinctivethemes_panels_generate_css($post_id, $panels_data = false){
	// Exit if we don't have panels data
	if( empty($panels_data) ) {
		$panels_data = get_post_meta( $post_id, 'panels_data', true );
		$panels_data = apply_filters( 'distinctivethemes_panels_data', $panels_data, $post_id );
	}
	if ( empty( $panels_data ) || empty( $panels_data['grids'] ) ) return;

	// Get some of the default settings
	$settings = distinctivethemes_panels_setting();
	$panels_tablet_width = $settings['tablet-width'];
	$panels_mobile_width = $settings['mobile-width'];
	$panels_margin_bottom = $settings['margin-bottom'];
	$panels_margin_bottom_last_row = $settings['margin-bottom-last-row'];

	$css = new DistinctiveThemes_Panels_Css_Builder();

	$ci = 0;
	foreach ( $panels_data['grids'] as $gi => $grid ) {

		$cell_count = intval( $grid['cells'] );
		$grid_id = !empty( $grid['style']['id'] ) ? (string) sanitize_html_class( $grid['style']['id'] ) : intval( $gi );

		// Add the cell sizing
		for ( $i = 0; $i < $cell_count; $i++ ) {
			$cell = $panels_data['grid_cells'][$ci++];

			if ( $cell_count > 1 ) {
				$width = round( $cell['weight'] * 100, 3 ) . '%';
				$width = apply_filters('distinctivethemes_panels_css_cell_width', $width, $grid, $gi, $cell, $ci - 1, $panels_data, $post_id);

				// Add the width and ensure we have correct formatting for CSS.
				$css->add_cell_css($post_id, $grid_id, $i, '', array(
					'width' => str_replace(',', '.', $width)
				));
			}
		}

		// Add the bottom margin to any grids that aren't the last
		if($gi != count($panels_data['grids'])-1 || !empty($grid['style']['bottom_margin']) || !empty($panels_margin_bottom_last_row)){
			// Filter the bottom margin for this row with the arguments
			$css->add_row_css($post_id, $grid_id, '', array(
				'margin-bottom' => apply_filters('distinctivethemes_panels_css_row_margin_bottom', $panels_margin_bottom.'px', $grid, $gi, $panels_data, $post_id)
			));
		}

		$collapse_order = !empty( $grid['style']['collapse_order'] ) ? $grid['style']['collapse_order'] : ( !is_rtl() ? 'left-top' : 'right-top' );

		if ( $cell_count > 1 ) {
			$css->add_cell_css($post_id, $grid_id, false, '', array(
				// Float right for RTL
				'float' => $collapse_order == 'left-top' ? 'left' : 'right'
			) );
		}
		else {
			$css->add_cell_css($post_id, $grid_id, false, '', array(
				// Float right for RTL
				'float' => 'none'
			) );
		}

		if ( $settings['responsive'] ) {

			if( $settings['tablet-layout'] && $cell_count >= 3  && $panels_tablet_width > $panels_mobile_width ) {
				// Tablet Responsive
				$css->add_cell_css($post_id, $grid_id, false, '', array(
					'width' => '50%'
				), $panels_tablet_width);
			}

			// Mobile Responsive
			$css->add_cell_css($post_id, $grid_id, false, '', array(
				'float' => 'none',
				'width' => 'auto'
			), $panels_mobile_width);

			for ( $i = 0; $i < $cell_count; $i++ ) {
				if ( ( $collapse_order == 'left-top' && $i != $cell_count - 1 ) || ( $collapse_order == 'right-top' && $i !== 0 ) ) {
					$css->add_cell_css($post_id, $grid_id, $i, '', array(
						'margin-bottom' => $panels_margin_bottom . 'px',
					), $panels_mobile_width);
				}
			}
		}
	}

	if( $settings['responsive'] ) {
		// Add CSS to prevent overflow on mobile resolution.
		$css->add_row_css($post_id, false, '', array(
			'margin-left' => 0,
			'margin-right' => 0,
		), $panels_mobile_width);

		$css->add_cell_css($post_id, false, false, '', array(
			'padding' => 0,
		), $panels_mobile_width);
	}

	// Add the bottom margins
	$css->add_cell_css($post_id, false, false, '.so-panel', array(
		'margin-bottom' => apply_filters('distinctivethemes_panels_css_cell_margin_bottom', $panels_margin_bottom.'px', $grid, $gi, $panels_data, $post_id)
	));
	$css->add_cell_css($post_id, false, false, '.so-panel:last-child', array(
		'margin-bottom' => apply_filters('distinctivethemes_panels_css_cell_last_margin_bottom', '0px', $grid, $gi, $panels_data, $post_id)
	));

	// Let other plugins customize various aspects of the rows (grids)
	foreach ( $panels_data['grids'] as $gi => $grid ) {
		$grid_id = !empty( $grid['style']['id'] ) ? (string) sanitize_html_class( $grid['style']['id'] ) : intval( $gi );

		// Let other themes and plugins change the gutter.
		$gutter = apply_filters('distinctivethemes_panels_css_row_gutter', $settings['margin-sides'].'px', $grid, $gi, $panels_data);

		if( !empty($gutter) ) {
			// We actually need to find half the gutter.
			preg_match('/([0-9\.,]+)(.*)/', $gutter, $match);
			if( !empty( $match[1] ) ) {
				$margin_half = (floatval($match[1])/2) . $match[2];
				$css->add_row_css($post_id, $grid_id, '', array(
					'margin-left' => '-' . $margin_half,
					'margin-right' => '-' . $margin_half,
				) );
				$css->add_cell_css($post_id, $grid_id, false, '', array(
					'padding-left' => $margin_half,
					'padding-right' => $margin_half,
				) );

			}
		}
	}

	foreach ($panels_data['widgets'] as $widget_id => $widget) {
		if (!empty($widget['panels_info']['style']['link_color'])) {
			$selector = '#panel-' . $post_id . '-' . $widget['panels_info']['grid'] . '-' . $widget['panels_info']['cell'] . '-' . $widget['panels_info']['cell_index'] . ' a';
			$css->add_css( $selector, array(
				'color' => $widget['panels_info']['style']['link_color']
			) );
		}
	}

	// Let other plugins and components filter the CSS object.
	$css = apply_filters('distinctivethemes_panels_css_object', $css, $panels_data, $post_id);
	return $css->get_css();
}


/**
 * Filter the content of the panel, adding all the widgets.
 *
 * @param $content
 * @return string
 *
 * @filter the_content
 */
function distinctivethemes_panels_filter_content( $content ) {
	global $post;

	if ( empty( $post ) ) return $content;
	if ( !apply_filters( 'distinctivethemes_panels_filter_content_enabled', true ) ) return $content;

	// Check if this post has panels_data
	$panels_data = get_post_meta( $post->ID, 'panels_data', true );
	if ( !empty( $panels_data ) ) {
		$panel_content = distinctivethemes_panels_render( $post->ID );

		if ( !empty( $panel_content ) ) {
			$content = $panel_content;

			if( !is_singular() ) {
				// This is an archive page, so try strip out anything after the more text

				if ( preg_match( '/<!--more(.*?)?-->/', $content, $matches ) ) {
					$content = explode( $matches[0], $content, 2 );
					$content = $content[0];
					$content = force_balance_tags( $content );
					if ( ! empty( $matches[1] ) && ! empty( $more_link_text ) ) {
						$more_link_text = strip_tags( wp_kses_no_null( trim( $matches[1] ) ) );
					}
					else {
						$more_link_text = __('Read More', 'distinctivethemes-panels');
					}

					$more_link = apply_filters( 'the_content_more_link', ' <a href="' . get_permalink() . "#more-{$post->ID}\" class=\"more-link\">$more_link_text</a>", $more_link_text );
					$content .= '<p>' . $more_link . '</p>';
				}
			}
		}
	}

	return $content;
}
add_filter( 'the_content', 'distinctivethemes_panels_filter_content' );


/**
 * Render the panels
 *
 * @param int|string|bool $post_id The Post ID or 'home'.
 * @param bool $enqueue_css Should we also enqueue the layout CSS.
 * @param array|bool $panels_data Existing panels data. By default load from settings or post meta.
 * @return string
 */
function distinctivethemes_panels_render( $post_id = false, $enqueue_css = true, $panels_data = false ) {
	if( empty($post_id) ) $post_id = get_the_ID();

	global $distinctivethemes_panels_current_post;
	$old_current_post = $distinctivethemes_panels_current_post;
	$distinctivethemes_panels_current_post = $post_id;

	// Try get the cached panel from in memory cache.
	global $distinctivethemes_panels_cache;
	if(!empty($distinctivethemes_panels_cache) && !empty($distinctivethemes_panels_cache[$post_id]))
		return $distinctivethemes_panels_cache[$post_id];

	if( empty($panels_data) ) {
		if( strpos($post_id, 'prebuilt:') === 0) {
			list($null, $prebuilt_id) = explode(':', $post_id, 2);
			$layouts = apply_filters('distinctivethemes_panels_prebuilt_layouts', array());
			$panels_data = !empty($layouts[$prebuilt_id]) ? $layouts[$prebuilt_id] : array();
		}
		else if($post_id == 'home'){
			$page_id = get_option( 'page_on_front' );
			if( empty($page_id) ) $page_id = get_option( 'distinctivethemes_panels_home_page_id' );

			$panels_data = !empty($page_id) ? get_post_meta( $page_id, 'panels_data', true ) : null;

			if( is_null($panels_data) ){
				// Load the default layout
				$layouts = apply_filters('distinctivethemes_panels_prebuilt_layouts', array());
				$prebuilt_id = distinctivethemes_panels_setting('home-page-default') ? distinctivethemes_panels_setting('home-page-default') : 'home';

				$panels_data = !empty($layouts[$prebuilt_id]) ? $layouts[$prebuilt_id] : current($layouts);
			}
		}
		else{
			if ( post_password_required($post_id) ) return false;
			$panels_data = get_post_meta( $post_id, 'panels_data', true );
		}
	}

	$panels_data = apply_filters( 'distinctivethemes_panels_data', $panels_data, $post_id );
	if( empty( $panels_data ) || empty( $panels_data['grids'] ) ) return '';

	// Filter the widgets to add indexes
	if ( !empty( $panels_data['widgets'] ) ) {
		$last_gi = 0;
		$last_ci = 0;
		$last_wi = 0;
		foreach ( $panels_data['widgets'] as $wid => &$widget_info ) {

			if ( $widget_info['panels_info']['grid'] != $last_gi ) {
				$last_gi = $widget_info['panels_info']['grid'];
				$last_ci = 0;
				$last_wi = 0;
			}
			elseif ( $widget_info['panels_info']['cell'] != $last_ci ) {
				$last_ci = $widget_info['panels_info']['cell'];
				$last_wi = 0;
			}
			$widget_info['panels_info']['cell_index'] = $last_wi++;
		}
	}

	// Create the skeleton of the grids
	$grids = array();
	if( !empty( $panels_data['grids'] ) && !empty( $panels_data['grids'] ) ) {
		foreach ( $panels_data['grids'] as $gi => $grid ) {
			$gi = intval( $gi );
			$grids[$gi] = array();
			for ( $i = 0; $i < $grid['cells']; $i++ ) {
				$grids[$gi][$i] = array();
			}
		}
	}

	// We need this to migrate from the old $panels_data that put widget meta into the "info" key instead of "panels_info"
	if( !empty( $panels_data['widgets'] ) && is_array($panels_data['widgets']) ) {
		foreach ( $panels_data['widgets'] as $i => $widget ) {
			if( empty( $panels_data['widgets'][$i]['panels_info'] ) ) {
				$panels_data['widgets'][$i]['panels_info'] = $panels_data['widgets'][$i]['info'];
				unset($panels_data['widgets'][$i]['info']);
			}

			$panels_data['widgets'][$i]['panels_info']['widget_index'] = $i;
		}
	}

	if( !empty( $panels_data['widgets'] ) && is_array($panels_data['widgets']) ){
		foreach ( $panels_data['widgets'] as $widget ) {
			// Put the widgets in the grids
			$grids[ intval( $widget['panels_info']['grid']) ][ intval( $widget['panels_info']['cell'] ) ][] = $widget;
		}
	}

	ob_start();

	// Add the panel layout wrapper
	$panel_layout_classes = apply_filters( 'distinctivethemes_panels_layout_classes', array(), $post_id, $panels_data );
	$panel_layout_attributes = apply_filters( 'distinctivethemes_panels_layout_attributes', array(
		'class' => implode( ' ', $panel_layout_classes ),
		'id' => 'pl-' . $post_id
	),  $post_id, $panels_data );
	echo '<div';
	foreach ( $panel_layout_attributes as $name => $value ) {
		if ($value) {
			echo ' ' . $name . '="' . esc_attr($value) . '"';
		}
	}
	echo '>';

	global $distinctivethemes_panels_inline_css;
	if( empty($distinctivethemes_panels_inline_css) ) $distinctivethemes_panels_inline_css = array();

	if( $enqueue_css && !isset($distinctivethemes_panels_inline_css[$post_id]) ) {
		wp_enqueue_style('distinctivethemes-panels-front');
		$distinctivethemes_panels_inline_css[$post_id] = distinctivethemes_panels_generate_css($post_id, $panels_data);
	}

	echo apply_filters( 'distinctivethemes_panels_before_content', '', $panels_data, $post_id );

	foreach ( $grids as $gi => $cells ) {

		$grid_classes = apply_filters( 'distinctivethemes_panels_row_classes', array('panel-grid'), $panels_data['grids'][$gi] );
		$grid_id = !empty($panels_data['grids'][$gi]['style']['id']) ? sanitize_html_class( $panels_data['grids'][$gi]['style']['id'] ) : false;

		$grid_attributes = apply_filters( 'distinctivethemes_panels_row_attributes', array(
			'class' => implode( ' ', $grid_classes ),
			'id' => !empty($grid_id) ? $grid_id : 'pg-' . $post_id . '-' . $gi,
		), $panels_data['grids'][$gi] );

		// This allows other themes and plugins to add html before the row
		echo apply_filters( 'distinctivethemes_panels_before_row', '', $panels_data['grids'][$gi], $grid_attributes );

		echo '<div ';
		foreach ( $grid_attributes as $name => $value ) {
			echo $name.'="'.esc_attr($value).'" ';
		}
		echo '>';

		$style_attributes = array();
		if( !empty( $panels_data['grids'][$gi]['style']['class'] ) ) {
			$style_attributes['class'] = array('panel-row-style-'.$panels_data['grids'][$gi]['style']['class']);
		}

		// Themes can add their own attributes to the style wrapper
		$row_style_wrapper = distinctivethemes_panels_start_style_wrapper( 'row', $style_attributes, !empty($panels_data['grids'][$gi]['style']) ? $panels_data['grids'][$gi]['style'] : array() );
		if( !empty($row_style_wrapper) ) echo $row_style_wrapper;

		$collapse_order = !empty( $panels_data['grids'][$gi]['style']['collapse_order'] ) ? $panels_data['grids'][$gi]['style']['collapse_order'] : ( !is_rtl() ? 'left-top' : 'right-top' );

		if( $collapse_order == 'right-top' ) {
			$cells = array_reverse( $cells, true );
		}

		foreach ( $cells as $ci => $widgets ) {
			// Themes can add their own styles to cells
			$cell_classes = apply_filters( 'distinctivethemes_panels_row_cell_classes', array('panel-grid-cell'), $panels_data );
			$cell_attributes = apply_filters( 'distinctivethemes_panels_row_cell_attributes', array(
				'class' => implode( ' ', $cell_classes ),
				'id' => 'pgc-' . $post_id . '-' . ( !empty($grid_id) ? $grid_id : $gi )  . '-' . $ci
			), $panels_data );

			echo '<div ';
			foreach ( $cell_attributes as $name => $value ) {
				echo $name.'="'.esc_attr($value).'" ';
			}
			echo '>';

			$cell_style_wrapper = distinctivethemes_panels_start_style_wrapper( 'cell', array(), !empty($panels_data['grids'][$gi]['style']) ? $panels_data['grids'][$gi]['style'] : array() );
			if( !empty($cell_style_wrapper) ) echo $cell_style_wrapper;

			foreach ( $widgets as $pi => $widget_info ) {
				// TODO this wrapper should go in the before/after widget arguments
				$widget_style_wrapper = distinctivethemes_panels_start_style_wrapper( 'widget', array(), !empty( $widget_info['panels_info']['style'] ) ? $widget_info['panels_info']['style'] : array() );
				distinctivethemes_panels_the_widget( $widget_info['panels_info'], $widget_info, $gi, $ci, $pi, $pi == 0, $pi == count( $widgets ) - 1, $post_id, $widget_style_wrapper );
			}
			if ( empty( $widgets ) ) echo '&nbsp;';

			if( !empty($cell_style_wrapper) ) echo '</div>';
			echo '</div>';
		}

		echo '</div>';

		// Close the
		if( !empty($row_style_wrapper) ) echo '</div>';

		// This allows other themes and plugins to add html after the row
		echo apply_filters( 'distinctivethemes_panels_after_row', '', $panels_data['grids'][$gi], $grid_attributes );
	}

	echo apply_filters( 'distinctivethemes_panels_after_content', '', $panels_data, $post_id );

	echo '</div>';

	do_action( 'distinctivethemes_panels_after_render', $panels_data, $post_id );

	$html = ob_get_clean();

	// Reset the current post
	$distinctivethemes_panels_current_post = $old_current_post;

	return apply_filters( 'distinctivethemes_panels_render', $html, $post_id, !empty($post) ? $post : null );
}

/**
 * Echo the style wrapper and return if there was a wrapper
 *
 * @param $name
 * @param $style_attributes
 * @param array $style_args
 *
 * @return bool Is there a style wrapper
 */
function distinctivethemes_panels_start_style_wrapper($name, $style_attributes, $style_args = array()){

	$style_wrapper = '';

	if( empty($style_attributes['class']) ) $style_attributes['class'] = array();
	if( empty($style_attributes['style']) ) $style_attributes['style'] = '';

	$style_attributes = apply_filters('distinctivethemes_panels_' . $name . '_style_attributes', $style_attributes, $style_args );

	if( empty($style_attributes['class']) ) unset($style_attributes['class']);
	if( empty($style_attributes['style']) ) unset($style_attributes['style']);

	if( !empty($style_attributes) ) {
		if(empty($style_attributes['class'])) $style_attributes['class'] = array();
		$style_attributes['class'][] = 'panel-' . $name . '-style';
		$style_attributes['class'] = array_unique( $style_attributes['class'] );

		// Filter and sanitize the classes
		$style_attributes['class'] = apply_filters('distinctivethemes_panels_' . $name . '_style_classes', $style_attributes['class'], $style_attributes, $style_args);
		$style_attributes['class'] = array_map('sanitize_html_class', $style_attributes['class']);

		$style_wrapper = '<div ';
		foreach ( $style_attributes as $name => $value ) {
			if( is_array($value) ) {
				$style_wrapper .= $name.'="'.esc_attr( implode( " ", array_unique( $value ) ) ).'" ';
			}
			else {
				$style_wrapper .= $name.'="'.esc_attr($value).'" ';
			}
		}
		$style_wrapper .= '>';

		return $style_wrapper;
	}

	return $style_wrapper;
}

/**
 * Print inline CSS in the header and footer.
 */
function distinctivethemes_panels_print_inline_css(){
	global $distinctivethemes_panels_inline_css;
	if(!empty($distinctivethemes_panels_inline_css)) {
		$the_css = '';
		foreach( $distinctivethemes_panels_inline_css as $post_id => $css ) {
			if( empty($css) ) continue;

			$the_css .= '/* Layout ' . esc_attr($post_id) . ' */ ';
			$the_css .= $css;
			$distinctivethemes_panels_inline_css[$post_id] = '';
		}

		if( !empty($the_css) ) {
			?><style type="text/css" media="all" id="distinctivethemes-panels-grids-<?php echo esc_attr( current_filter() ) ?>"><?php echo $the_css ?></style><?php
		}
	}
}
add_action('wp_head', 'distinctivethemes_panels_print_inline_css', 12);
add_action('wp_footer', 'distinctivethemes_panels_print_inline_css');

/**
 * Render the widget.
 *
 * @param array $widget_info The widget info.
 * @param array $instance The widget instance
 * @param int $grid The grid number.
 * @param int $cell The cell number.
 * @param int $panel the panel number.
 * @param bool $is_first Is this the first widget in the cell.
 * @param bool $is_last Is this the last widget in the cell.
 * @param bool $post_id
 * @param string $style_wrapper The start of the style wrapper
 */
function distinctivethemes_panels_the_widget( $widget_info, $instance, $grid, $cell, $panel, $is_first, $is_last, $post_id = false, $style_wrapper = '' ) {

	global $wp_widget_factory;

	// Set widget class to $widget
	$widget = $widget_info['class'];

	// Load the widget from the widget factory and give themes and plugins a chance to provide their own
	$the_widget = !empty($wp_widget_factory->widgets[$widget]) ? $wp_widget_factory->widgets[$widget] : false;
	$the_widget = apply_filters( 'distinctivethemes_panels_widget_object', $the_widget, $widget, $instance );

	if( empty($post_id) ) $post_id = get_the_ID();

	$classes = array( 'so-panel' );
	if( distinctivethemes_panels_setting( 'add-widget-class' ) ) $classes[] = 'widget';
	if ( !empty( $the_widget ) && !empty( $the_widget->id_base ) ) $classes[] = 'widget_' . $the_widget->id_base;
	if ( !empty( $the_widget ) && is_array( $the_widget->widget_options ) && !empty( $the_widget->widget_options['classname'] ) ) $classes[] = $the_widget->widget_options['classname'];
	if ( $is_first ) $classes[] = 'panel-first-child';
	if ( $is_last ) $classes[] = 'panel-last-child';
	$id = 'panel-' . $post_id . '-' . $grid . '-' . $cell . '-' . $panel;

	// Filter and sanitize the classes
	$classes = apply_filters( 'distinctivethemes_panels_widget_classes', $classes, $widget, $instance, $widget_info );
	$classes = explode( ' ', implode( ' ', $classes ) );
	$classes = array_filter( $classes );
	$classes = array_unique( $classes );
	$classes = array_map( 'sanitize_html_class', $classes );

	$title_html = distinctivethemes_panels_setting( 'title-html' );
	if( strpos($title_html, '{{title}}') !== false ) {
		list( $before_title, $after_title ) = explode( '{{title}}', $title_html, 2 );
	}
	else {
		$before_title = '<h3 class="widget-title">';
		$after_title = '</h3>';
	}

	$args = array(
		'before_widget' => '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" id="' . $id . '" data-index="' . $widget_info['widget_index'] . '">',
		'after_widget' => '</div>',
		'before_title' => $before_title,
		'after_title' => $after_title,
		'widget_id' => 'widget-' . $grid . '-' . $cell . '-' . $panel
	);

	// Let other themes and plugins change the arguments that go to the widget class.
	$args = apply_filters('distinctivethemes_panels_widget_args', $args);

	// If there is a style wrapper, add it.
	if( !empty($style_wrapper) ) {
		$args['before_widget'] = $args['before_widget'] . $style_wrapper;
		$args['after_widget'] = '</div>' . $args['after_widget'];
	}

	if ( !empty($the_widget) && is_a($the_widget, 'WP_Widget')  ) {
		$the_widget->widget($args , $instance );
	}
	else {
		// This gives themes a chance to display some sort of placeholder for missing widgets
		echo apply_filters('distinctivethemes_panels_missing_widget', $args['before_widget'] . $args['after_widget'], $widget, $args , $instance);
	}
}

/**
 * Add the Edit Home Page item to the admin bar.
 *
 * @param WP_Admin_Bar $admin_bar
 * @return WP_Admin_Bar
 */
function distinctivethemes_panels_admin_bar_menu($admin_bar){
	// Ignore this unless the theme is using the home page feature.
	if( !distinctivethemes_panels_setting('home-page') ) return $admin_bar;
	if( !current_user_can('edit_theme_options') ) return $admin_bar;

	if( is_home() || is_front_page() ) {
		if( ( is_page() && get_post_meta( get_the_ID(), 'panels_data', true ) !== '' ) || !is_page() ) {
			$admin_bar->add_node( array(
				'id' => 'edit-home-page',
				'title' => __('Edit Home Page', 'distinctivethemes-panels'),
				'href' => admin_url('themes.php?page=so_panels_home_page')
			) );

			if( is_page() ) {
				// Remove the standard edit button
				$admin_bar->remove_node('edit');
			}
		}
	}

	return $admin_bar;
}
add_action('admin_bar_menu', 'distinctivethemes_panels_admin_bar_menu', 100);

/**
 * Is this a preview.
 *
 * @return bool
 */
function distinctivethemes_panels_is_preview(){
	global $distinctivethemes_panels_is_preview;
	return (bool) $distinctivethemes_panels_is_preview;
}

/**
 * Add all the necessary body classes.
 *
 * @param $classes
 * @return array
 */
function distinctivethemes_panels_body_class($classes){
	if( distinctivethemes_panels_is_panel() ) $classes[] = 'distinctivethemes-panels';
	if( distinctivethemes_panels_is_home() ) $classes[] = 'distinctivethemes-panels-home';

	return $classes;
}
add_filter('body_class', 'distinctivethemes_panels_body_class');

/**
 * Enqueue the required styles
 */
function distinctivethemes_panels_enqueue_styles(){
	// Register the style to support possible lazy loading
	wp_register_style('distinctivethemes-panels-front', plugin_dir_url(__FILE__) . 'css/front.css', array(), DISTINCTIVETHEMES_PANELS_VERSION );

	if( is_singular() && get_post_meta( get_the_ID(), true ) != '' ) {
		wp_enqueue_style('distinctivethemes-panels-front');

		// Enqueue the general layout CSS
		global $distinctivethemes_panels_inline_css;
		if( empty($distinctivethemes_panels_inline_css) ) $distinctivethemes_panels_inline_css = array();
		$distinctivethemes_panels_inline_css[ get_the_ID() ] = distinctivethemes_panels_generate_css( get_the_ID() );
	}
}
add_action('wp_enqueue_scripts', 'distinctivethemes_panels_enqueue_styles', 1);

/**
 * Render a widget form with all the Page Builder specific fields
 *
 * @param string $widget The class of the widget
 * @param array $instance Widget values
 * @param bool $raw
 * @param string $widget_number
 * @return mixed|string The form
 */
function distinctivethemes_panels_render_form($widget, $instance = array(), $raw = false, $widget_number = '{$id}' ){
	global $wp_widget_factory;

	// This is a chance for plugins to replace missing widgets
	$the_widget = !empty($wp_widget_factory->widgets[$widget]) ? $wp_widget_factory->widgets[$widget] : false;
	$the_widget = apply_filters( 'distinctivethemes_panels_widget_object', $the_widget, $widget );

	if ( empty($the_widget) || !is_a( $the_widget, 'WP_Widget' ) ) {
		$widgets = distinctivethemes_panels_get_widgets();

		if( !empty($widgets[$widget]) && !empty( $widgets[$widget]['plugin'] ) ) {
			// We know about this widget, show a form about installing it.
			$install_url = distinctivethemes_panels_plugin_activation_install_url($widgets[$widget]['plugin']['slug'], $widgets[$widget]['plugin']['name']);
			$form =
				'<div class="panels-missing-widget-form">' .
				'<p>' .
				preg_replace(
					array(
						'/1\{ *(.*?) *\}/',
						'/2\{ *(.*?) *\}/',
					),
					array(
						'<a href="'.$install_url.'" target="_blank">$1</a>',
						'<strong>$1</strong>'
					),
					sprintf(
						__('You need to install 1{%1$s} to use the widget 2{%2$s}.', 'distinctivethemes-panels') ,
						$widgets[$widget]['plugin']['name'],
						$widget
					)
				).
				'</p>' .
				'<p>' . __("Save and reload this page to start using the widget after you've installed it.", 'distinctivethemes-panels') . '</p>' .
				'</div>';
		}
		else {
			// This widget is missing, so show a missing widgets form.
			$form =
				'<div class="panels-missing-widget-form"><p>' .
				preg_replace(
					array(
						'/1\{ *(.*?) *\}/',
						'/2\{ *(.*?) *\}/',
					),
					array(
						'<strong>$1</strong>',
						'<a href="https://distinctivethemes.com/thread/" target="_blank">$1</a>'
					),
					sprintf(
						__('The widget 1{%1$s} is not available. Please try locate and install the missing plugin. Post on the 2{support forums} if you need help.', 'distinctivethemes-panels'),
						esc_html($widget)
					)
				).
				'</p></div>';
		}

		// Allow other themes and plugins to change the missing widget form
		return apply_filters('distinctivethemes_panels_missing_widget_form', $form, $widget, $instance);
	}

	if( $raw ) $instance = $the_widget->update($instance, $instance);

	$the_widget->id = 'temp';
	$the_widget->number = $widget_number;

	ob_start();
	$return = $the_widget->form($instance);
	do_action_ref_array( 'in_widget_form', array( &$the_widget, &$return, $instance ) );
	$form = ob_get_clean();

	// Convert the widget field naming into ones that Page Builder uses
	$exp = preg_quote( $the_widget->get_field_name('____') );
	$exp = str_replace('____', '(.*?)', $exp);
	$form = preg_replace( '/'.$exp.'/', 'widgets[' . preg_replace('/\$(\d)/', '\\\$$1', $widget_number) . '][$1]', $form );

	$form = apply_filters('distinctivethemes_panels_widget_form', $form, $widget, $instance);

	// Add all the information fields
	return $form;
}

function distinctivethemes_panels_live_edit_link( $wp_admin_bar ){
	// Add a Live Edit link if this is a Page Builder page that the user can edit
	if( is_singular() && current_user_can( 'edit_post', get_the_ID() ) && get_post_meta( get_the_ID(), 'panels_data', true ) ) {
		$wp_admin_bar->add_node( array(
			'id'    => 'so_live_editor',
			'title' => __( 'Live Editor', 'distinctivethemes-panels' ),
			'href'  => add_query_arg( 'so_live_editor', 1, get_edit_post_link( get_the_ID() ) ),
			'meta'  => array(
				'class' => 'live-edit-page'
			)
		) );
	}
}
add_action( 'admin_bar_menu', 'distinctivethemes_panels_live_edit_link', 100 );

function distinctivethemes_panels_live_edit_link_style(){
	if( is_singular() && current_user_can( 'edit_post', get_the_ID() ) && get_post_meta( get_the_ID(), 'panels_data', true ) ) {
		// Add the style for the eye icon before the Live Editor link
		$css = '#wpadminbar #wp-admin-bar-so_live_editor > .ab-item:before {
		    content: "\f177";
		    top: 2px;
		}';
		wp_add_inline_style( 'distinctivethemes-panels-front', $css );
	}
}
add_action( 'wp_enqueue_scripts', 'distinctivethemes_panels_live_edit_link_style' );

function distinctivethemes_panels_live_editor_preview_url(){
	global $post, $wp_post_types;

	if(
		empty( $post ) ||
		empty( $wp_post_types ) ||
		empty( $wp_post_types[ $post->post_type ] ) ||
		!$wp_post_types[ $post->post_type ]->public
	) {
		$preview_url = add_query_arg(
			'distinctivethemes_panels_live_editor',
			'true',
			admin_url( 'admin-ajax.php?action=so_panels_live_editor_preview' )
		);
		$preview_url = wp_nonce_url( $preview_url, 'live-editor-preview', '_panelsnonce' );
	}
	else {
		$preview_url = add_query_arg( 'distinctivethemes_panels_live_editor', 'true', set_url_scheme( get_permalink() ) );
	}

	return $preview_url;
}

/**
 * Process panels data to make sure everything is properly formatted
 *
 * @param array $panels_data
 *
 * @return array
 */
function distinctivethemes_panels_process_panels_data( $panels_data ){

	// Process all widgets to make sure that panels_info is properly represented
	if( !empty($panels_data['widgets']) && is_array($panels_data['widgets']) ) {

		$last_gi = 0;
		$last_ci = 0;
		$last_wi = 0;

		foreach( $panels_data['widgets'] as &$widget ) {
			// Transfer legacy content
			if( empty($widget['panels_info']) && !empty($widget['info']) ) {
				$widget['panels_info'] = $widget['info'];
				unset( $widget['info'] );
			}

			// Filter the widgets to add indexes
			if ( $widget['panels_info']['grid'] != $last_gi ) {
				$last_gi = $widget['panels_info']['grid'];
				$last_ci = 0;
				$last_wi = 0;
			}
			elseif ( $widget['panels_info']['cell'] != $last_ci ) {
				$last_ci = $widget['panels_info']['cell'];
				$last_wi = 0;
			}
			$widget['panels_info']['cell_index'] = $last_wi++;
		}

		foreach( $panels_data['grids'] as &$grid ) {
			if( !empty( $grid['style'] ) && is_string( $grid['style'] ) ) {
				$grid['style'] = array(

				);
			}
		}
	}

	// Process the IDs of the grids. Make sure that each is unique.

	if( !empty($panels_data['grids']) && is_array($panels_data['grids']) ) {
		$unique_grid_ids = array();
		foreach( $panels_data['grids'] as &$grid ) {
			// Make sure that the row ID is unique and non-numeric
			if( !empty( $grid['style']['id'] ) ) {
				if( is_numeric($grid['style']['id']) ) {
					// Numeric IDs will cause problems, so we'll ignore them
					$grid['style']['id'] = false;
				}
				else if( isset( $unique_grid_ids[ $grid['style']['id'] ] ) ) {
					// This ID already exists, so add a suffix to make sure it's unique
					$original_id = $grid['style']['id'];
					$i = 1;
					do {
						$grid['style']['id'] = $original_id . '-' . (++$i);
					} while( isset( $unique_grid_ids[ $grid['style']['id'] ] ) );
				}

				if( !empty( $grid['style']['id'] ) ) {
					$unique_grid_ids[ $grid['style']['id'] ] = true;
				}
			}
		}
	}

	return $panels_data;
}
add_filter( 'distinctivethemes_panels_data', 'distinctivethemes_panels_process_panels_data', 5 );

// Include the live editor file if we're in live editor mode.
if( !empty($_GET['distinctivethemes_panels_live_editor']) ) require_once plugin_dir_path(__FILE__) . 'inc/live-editor.php';
 
/* FROM THEME */
add_filter( 'mce_buttons', 'dapper_cl_mce_buttons' );
function dapper_cl_mce_buttons( $buttons ) {
	$index = array_search( 'wp_more', $buttons );
	if ( $index !== FALSE ) {
		array_splice( $buttons, $index + 1, 0, 'dapper-pro' );
	} else {
		$buttons[] = 'dapper-pro';
	}

	return $buttons;
}

add_filter( 'mce_external_plugins', 'dapper_cl_mce_external_plugins' );
function dapper_cl_mce_external_plugins( $mce_external_plugins ) {
	global $dapper_cl_uri;
	$mce_external_plugins['dapper-pro'] = $dapper_cl_uri . '/editors-support/native/tinymce.js';

	return $mce_external_plugins;
}

add_action( 'admin_print_footer_scripts', 'dapper_cl_quicktags_button' );
function dapper_cl_quicktags_button() {
	if ( wp_script_is( 'quicktags' ) ) {
		echo '<script id="dapper_cl_quicktags">' . file_get_contents( dirname( __FILE__ ) . '/quicktags.js' ) . '</script>';
	}
}


function siteorigin_widget_post_selector_enqueue_admin_scripts() {
	if ( ! wp_script_is( 'siteorigin-widget-admin-posts-selector' ) ) {

		wp_enqueue_style( 'siteorigin-widget-admin-posts-selector', get_template_directory_uri() . '/page_builder_widgets/advanced-widgets/'.'base/css/post-selector.css', array(), SOW_BUNDLE_VERSION );
		wp_enqueue_script( 'siteorigin-widget-admin-posts-selector', get_template_directory_uri() . '/page_builder_widgets/advanced-widgets/'.'base/js/posts-selector' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-autocomplete', 'underscore', 'backbone' ), SOW_BUNDLE_VERSION, true );

		wp_localize_script( 'siteorigin-widget-admin-posts-selector', 'sowPostsSelectorTpl', array(
			'ajaxurl' => wp_nonce_url( admin_url('admin-ajax.php'), 'widgets_action', '_widgets_nonce' ),
			'modal' => file_get_contents( get_template_directory_uri() . '/page_builder_widgets/advanced-widgets/'.'base/tpl/posts-selector/modal.html' ),
			'postSummary' => file_get_contents( get_template_directory_uri() . '/page_builder_widgets/advanced-widgets/'.'base/tpl/posts-selector/post.html' ),
			'foundPosts' => '<div class="dapper-sow-post-count-message">' . sprintf( __('This query returns <a href="#" class="preview-query-posts">%s posts</a>.', 'dapper-pro'), '<%= foundPosts %>') . '</div>',
			'fields' => siteorigin_widget_post_selector_form_fields(),
			'selector' => file_get_contents( get_template_directory_uri() . '/page_builder_widgets/advanced-widgets/'.'base/tpl/posts-selector/selector.html' ),
		) );

		wp_localize_script( 'siteorigin-widget-admin-posts-selector', 'sowPostsSelectorVars', array(
			'modalTitle' => __('Select posts', 'dapper-pro'),
		) );
	}
}

function siteorigin_widget_post_selector_admin_form_field( $value, $field_name ) {
	?>
	<input type="hidden" value="<?php echo esc_attr( $value ) ?>" name="<?php echo $field_name ?>" class="siteorigin-widget-input" />
	<a href="#" class="dapper-sow-select-posts button button-secondary">
		<span class="dapper-sow-current-count"><?php echo esc_html( siteorigin_widget_post_selector_count_posts( $value ) )?></span>
		<?php esc_html_e( 'Build posts query', 'dapper-pro' ) ?>
	</a>
	<?php
}

function siteorigin_widget_post_selector_process_query($query){
	$query = wp_parse_args($query,
		array(
			'post_status' => 'publish',
			'posts_per_page' => 10,
		)
	);

	if( !empty( $query['post_type'] ) ) {
		if($query['post_type'] == '_all') $query['post_type'] = siteorigin_widget_post_selector_all_post_types();
		$query['post_type'] = strpos( $query['post_type'], ',' ) !== false ? explode( ',', $query['post_type'] ) : $query['post_type'];
	}
	if( !empty( $query['post_type'] ) && $query['post_type'] == 'attachment' && $query['post_status'] == 'publish' ) {
		$query['post_status'] = 'inherit';
	}


	if(!empty($query['post__in'])) {
		$query['post__in'] = explode(',', $query['post__in']);
		array_map('intval', $query['post__in']);
	}

	if(!empty($query['tax_query'])) {
		$tax_queries = explode(',', $query['tax_query']);

		$query['tax_query'] = array();
		$query['tax_query']['relation'] = 'OR';
		foreach($tax_queries as $tq) {
			list($tax, $term) = explode(':', $tq);

			if( empty($tax) || empty($term) ) continue;
			$query['tax_query'][] = array(
				'taxonomy' => $tax,
				'field' => 'slug',
				'terms' => $term
			);
		}
	}

	if ( ! empty( $query['date_query'] ) ) {
		$query['date_query'] = json_decode( $query['date_query'], true );
	}

	if ( ! empty( $query['sticky'] ) ) {
		switch($query['sticky']){
			case 'ignore' :
				$query['ignore_sticky_posts'] = 1;
				break;
			//TODO: Revisit this. Not sure if it makes sense to have this as an option in a separate dropdown, but am
			//TODO: trying to stay as close as possible to Page Builder Post Loop widget post selection options.
			//TODO: It's probably better in the long run to make this work well and just cope with issues that come up in
			//TODO: Page Builder Post Loop migrations until it dies.
			case 'only' :
				$post_in = empty( $query['post__in'] ) ? array() : $query['post__in'];
				$query['post__in'] = array_merge( $post_in, get_option( 'sticky_posts' ) );
				break;
			case 'exclude' :
				$query['post__not_in'] = get_option( 'sticky_posts' );
				break;
		}
		unset( $query['sticky'] );
	}

	if ( ! empty( $query['additional'] ) ) {
		$query = wp_parse_args( $query['additional'], $query );
		unset( $query['additional'] );
	}

	return $query;
}

function siteorigin_widget_post_selector_form_fields(){
	$return = array();

	// The post type field
	$return['post_type'] = '';
	$return['post_type'] .= '<label><span>' . __('Post type', 'dapper-pro') . '</span>';
	$return['post_type'] .= '<select name="post_type">';
	$return['post_type'] .= '<option value="_all">' . __('All', 'dapper-pro') . '</option>';
	foreach( get_post_types( array( 'public' => true  ), 'objects' ) as $id => $type ) {
		if(!empty($type->labels->name)) {
			$post_types[$id] = $type->labels->name;
			$return['post_type'] .= '<option value="' . $id . '">' . $type->labels->name . '</option>';
		}
	}
	$return['post_type'] .= '</select></label>';

	// The field for specifying individual posts
	$return['post__in'] = '';
	$return['post__in'] .= '<label><span>' . __('Post in', 'dapper-pro') . '</span>';
	$return['post__in'] .= '<input type="text" name="post__in" class="" />';
	$return['post__in'] .= ' <a href="#" class="dapper-sow-select-posts button button-secondary">' . __('Select posts', 'dapper-pro') . '</a>';
	$return['post__in'] .= '</label>';

	// The taxonomy field
	$return['tax_query'] = '';
	$return['tax_query'] .= '<label><span>' . __('Taxonomies', 'dapper-pro') . '</span>';
	$return['tax_query'] .= '<input type="text" name="tax_query" class="" placeholder="search" />';
	$return['tax_query'] .= '</label>';

	$return['date_query'] = '';
	$return['date_query'] .= '<label><span>' . __('Date range', 'dapper-pro') . '</span>';
	$return['date_query'] .= __( 'From', 'dapper-pro' );
	$return['date_query'] .= '<input type="date" name="after" class="" />';
	$return['date_query'] .= __( 'To', 'dapper-pro' );
	$return['date_query'] .= '<input type="date" name="before" class="" />';
	$return['date_query'] .= '<small>' . __('In the format \'yyyy-mm-dd\'.', 'dapper-pro') . '</small>';
	$return['date_query'] .= '</label>';


	// The order by field
	$return['orderby'] = '';
	$return['orderby'] .= '<label><span>' . __('Order by', 'dapper-pro') . '</span>';
	$return['orderby'] .= '<select name="orderby">';
	$orderby = array(
		'none' => __('No order', 'dapper-pro'),
		'ID' => __('Post ID', 'dapper-pro'),
		'author' => __('Author', 'dapper-pro'),
		'title' => __('Title', 'dapper-pro'),
		'date' => __('Published date', 'dapper-pro'),
		'modified' => __('Modified date', 'dapper-pro'),
		'parent' => __('By parent', 'dapper-pro'),
		'rand' => __('Random order', 'dapper-pro'),
		'comment_count' => __('Comment count', 'dapper-pro'),
		'menu_order' => __('Menu order', 'dapper-pro'),
		'meta_value' => __('By meta value', 'dapper-pro'),
		'meta_value_num' => __('By numeric meta value', 'dapper-pro'),
		'post__in' => __('By include order', 'dapper-pro'),
	);
	foreach($orderby as $id => $v) {
		$return['orderby'] .= '<option value="' . $id . '">' . $v . '</option>';
	}
	$return['orderby'] .= '</select>';
	$return['orderby'] .= '<input type="hidden" name="order" />';
	$return['orderby'] .= '<span class="dapper-sow-order-button dapper-sow-order-button-desc"></span>';
	$return['orderby'] .= '</label>';

	$return['posts_per_page'] = '';
	$return['posts_per_page'] .= '<label><span>' . __('Posts per page', 'dapper-pro') . '</span>';
	$return['posts_per_page'] .= '<input type="number" name="posts_per_page" class="" />';
	$return['posts_per_page'] .= '</label>';


	$return['sticky'] = '';
	$return['sticky'] .= '<label><span>' . __('Sticky posts', 'dapper-pro') . '</span>';
	$return['sticky'] .= '<select name="sticky">';
	$sticky = array(
		'' => __('Default', 'dapper-pro'),
		'ignore' => __('Ignore sticky', 'dapper-pro'),
		'exclude' => __('Exclude sticky', 'dapper-pro'),
		'only' => __('Include sticky', 'dapper-pro'),
	);
	foreach($sticky as $id => $v) {
		$return['sticky'] .= '<option value="' . $id . '">' . $v . '</option>';
	}
	$return['sticky'] .= '</select></label>';

	$return['additional'] = '';
	$return['additional'] .= '<label><span>' . __('Additional', 'dapper-pro') . '</span>';
	$return['additional'] .= '<input type="text" name="additional" class="" />';
	$return['additional'] .= '<small>' . __('Additional query arguments. See <a href="http://codex.wordpress.org/Function_Reference/query_posts" target="_blank">query_posts</a>.', 'dapper-pro') . '</small>';
	$return['additional'] .= '</label>';

	return $return;
}

/**
 * Just return a comma separated list of all available post types.
 *
 * @return string
 */
function siteorigin_widget_post_selector_all_post_types(){
	$post_types = array();
	foreach( get_post_types( array( 'public' => true  ), 'objects' ) as $id => $type ) {
		$post_types[] = $id;
	}

	return implode(',', $post_types);
}

/**
 * Tells us how many posts this query has
 *
 * @param $query
 * @return int
 */
function siteorigin_widget_post_selector_count_posts($query){
	$query = siteorigin_widget_post_selector_process_query( $query );
	$posts = new WP_Query($query);
	return $posts->found_posts;
}

/**
 * The get posts ajax action
 */
function siteorigin_widget_post_selector_get_posts_action(){
	if ( empty( $_REQUEST['_widgets_nonce'] ) || !wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ) return;

	$query = siteorigin_widget_post_selector_process_query( stripslashes( $_POST['query'] ) );

	if(!empty($_POST['ignore_pagination'])) {
		$query['posts_per_page'] = 100;
	}

	$posts = new WP_Query($query);

	// Create the result
	$result = array(
		'found_posts' => $posts->found_posts,
		'posts' => array()
	);

	foreach($posts->posts as $post) {
		$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ) );

		$result['posts'][] = array(
			'title' => $post->post_title,
			'id' => $post->ID,
			'thumbnail' => !empty($thumbnail) ? $thumbnail[0] : plugin_dir_url(__FILE__).'../css/img/thumbnail-placeholder.png',
			'editUrl' => admin_url( 'post.php?post=' . $post->ID . '&action=edit')
		);
	}

	header('content-type: application/json');
	echo json_encode($result);

	exit();
}
add_action('wp_ajax_sow_get_posts', 'siteorigin_widget_post_selector_get_posts_action');

/**
 * The action handler for searching posts by title
 */
function siteorigin_widget_post_selector_post_search_action(){
	if ( empty( $_REQUEST['_widgets_nonce'] ) || !wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ) return;
	$term = !empty($_GET['term']) ? stripslashes($_GET['term']) : '';
	$type = !empty($_GET['type']) ? stripslashes($_GET['type']) : '_all';
	if($type == '_all') $type = explode(',', siteorigin_widget_post_selector_all_post_types());

	$results = array();
	$r = new WP_Query( array('s' => $term, 'post_status' => 'publish', 'posts_per_page' => 20, 'post_type' => $type) );
	foreach($r->posts as $post) {
		$results[] = array(
			'label' => $post->post_title,
			'value' => $post->ID,
		);
	}

	header('content-type:application/json');
	echo json_encode($results);
	exit();

}
add_action('wp_ajax_sow_search_posts', 'siteorigin_widget_post_selector_post_search_action');

/**
 * The action handler for searching taxonomy terms
 */
function siteorigin_widget_post_selector_search_taxonomy_terms(){
	if ( empty( $_REQUEST['_widgets_nonce'] ) || !wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ) return;
	global $wpdb;
	$term = !empty($_GET['term']) ? stripslashes($_GET['term']) : '';
	$term = trim($term, '%');

	$return = array();

	$query = $wpdb->prepare("
		SELECT terms.term_id, terms.slug, terms.name, termtaxonomy.taxonomy
		FROM $wpdb->terms AS terms
		JOIN $wpdb->term_taxonomy AS termtaxonomy ON terms.term_id = termtaxonomy.term_id
		WHERE
			terms.name LIKE '%s'
	", '%'.$term.'%');

	foreach($wpdb->get_results($query) as $result) {
		$return[] = array(
			'label' => $result->taxonomy.': '.$result->name,
			'value' => $result->taxonomy.':'.$result->slug,
		);
	}

	header('content-type:application/json');
	echo json_encode($return);
	exit();
}
add_action('wp_ajax_sow_search_terms', 'siteorigin_widget_post_selector_search_taxonomy_terms');
