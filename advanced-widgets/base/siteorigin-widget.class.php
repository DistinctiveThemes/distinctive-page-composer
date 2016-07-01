<?php

/**
 * Class DistinctiveThemes_SiteOrigin_Widget
 *
 * @author SiteOrigin <support@siteorigin.com>
 */
abstract class DistinctiveThemes_SiteOrigin_Widget extends WP_Widget {
	protected $form_options;
	protected $base_folder;
	protected $field_ids;
	protected $fields;

	/**
	 * @var array The array of registered frontend scripts
	 */
	protected $frontend_scripts = array();

	/**
	 * @var array The array of registered frontend styles
	 */
	protected $frontend_styles = array();

	protected $current_instance;
	protected $instance_storage;

	/**
	 * @var int How many seconds a CSS file is valid for.
	 */
	static $css_expire = 604800; // 7 days

	/**
	 *
	 * @param string $id
	 * @param string $name
	 * @param array $widget_options Optional Normal WP_Widget widget options and a few extras.
	 *   - help: A URL which, if present, causes a help link to be displayed on the Edit Widget modal.
	 *   - instance_storage: Whether or not to temporarily store instances of this widget.
	 *   - has_preview: Whether or not this widget has a preview to display. If false, the form does not output a
	 *                  'Preview' button.
	 * @param array $control_options Optional Normal WP_Widget control options.
	 * @param array $form_options Optional An array describing the form fields used to configure SiteOrigin widgets.
	 * @param mixed $base_folder Optional
	 *
	 */
	function __construct($id, $name, $widget_options = array(), $control_options = array(), $form_options = array(), $base_folder = false) {
		$this->form_options = $form_options;
		$this->base_folder = $base_folder;
		$this->field_ids = array();
		$this->fields = array();

		$widget_options = wp_parse_args( $widget_options, array(
			'has_preview' => true,
		) );

		$control_options = wp_parse_args($widget_options, array(
			'width' => 600,
		) );

		parent::__construct($id, $name, $widget_options, $control_options);
		$this->initialize();

		// Let other plugins do additional initializing here
		do_action('distinctivethemes_widgets_initialize_widget_' . $this->id_base, $this);
	}

	/**
	 * Initialize this widget in whatever way we need to. Run before rendering widget or form.
	 */
	function initialize(){

	}

	/**
	 * Return the form array. Widgets should implement this if they don't have a form in the form array.
	 *
	 * @return array
	 */
	function initialize_form( ){
		return array();
	}

	/**
	 * Get the form options and allow child widgets to modify that form.
	 *
	 * @param bool|DistinctiveThemes_SiteOrigin_Widget $parent
	 *
	 * @return mixed
	 */
	function form_options( $parent = false ) {
		if( empty( $this->form_options ) ) {
			$this->form_options = $this->initialize_form();
		}

		$form_options = $this->modify_form( $this->form_options );
		if( !empty($parent) ) {
			$form_options = $parent->modify_child_widget_form( $form_options, $this );
		}

		// Give other plugins a way to modify this form.
		$form_options = apply_filters( 'distinctivethemes_widgets_form_options', $form_options, $this );
		$form_options = apply_filters( 'distinctivethemes_widgets_form_options_' . $this->id_base, $form_options, $this );
		return $form_options;
	}

	/**
	 * Display the widget.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		if( empty( $this->form_options ) ) {
			$this->form_options = $this->initialize_form();
		}

		$instance = $this->modify_instance($instance);

		// Filter the instance
		$instance = apply_filters( 'distinctivethemes_widgets_instance', $instance, $this );
		$instance = apply_filters( 'distinctivethemes_widgets_instance_' . $this->id_base, $instance, $this );

		$args = wp_parse_args( $args, array(
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '',
			'after_title' => '',
		) );

		// Add any missing default values to the instance
		$instance = $this->add_defaults( $this->form_options, $instance );
		$this->enqueue_frontend_scripts( $instance );

		$template_vars = $this->get_template_variables($instance, $args);
		$template_vars = apply_filters( 'distinctivethemes_widgets_template_variables_' . $this->id_base, $template_vars, $instance, $args, $this );
		extract( $template_vars );

		// Storage hash allows templates to get access to
		$storage_hash = '';
		if( !empty($this->widget_options['instance_storage']) ) {
			$stored_instance = $this->modify_stored_instance($instance);
			// We probably don't want panels_info
			unset($stored_instance['panels_info']);

			$storage_hash = substr( md5( serialize($stored_instance) ), 0, 8 );
			if( !empty( $stored_instance ) && !$this->is_preview( $instance ) ) {
				// Store this if we have a non empty instance and are not previewing
				set_transient('sow_inst[' . $this->id_base . '][' . $storage_hash . ']', $stored_instance, 7*86400);
			}
		}

		$template_file = siteorigin_widget_get_plugin_dir_path( $this->id_base ) . $this->get_template_dir( $instance ) . '/' . $this->get_template_name( $instance ) . '.php';
		$template_file = apply_filters('distinctivethemes_widgets_template_file_' . $this->id_base, $template_file, $instance, $this );
		$template_file = realpath($template_file);

		// Don't accept non PHP files
		if( substr($template_file, -4) != '.php' ) $template_file = false;

		echo $args['before_widget'];
		echo '<div class="dapper-so-widget-'.$this->id_base.'">';
		ob_start();
		if( !empty($template_file) && file_exists($template_file) ) {
			@ include $template_file;
		}
		$template_html = ob_get_clean();
		// This is a legacy, undocumented filter.
		$template_html = apply_filters( 'distinctivethemes_widgets_template', $template_html, get_class($this), $instance, $this );
		$template_html = apply_filters( 'distinctivethemes_widgets_template_html_' . $this->id_base, $template_html, $instance, $this );
		echo $template_html;
		echo '</div>';
		echo $args['after_widget'];
	}

	/**
	 * Get an array of variables to make available to templates. By default, just return an array. Should be overwritten by child widgets.
	 *
	 * @param $instance
	 * @param $args
	 *
	 * @return array
	 */
	public function get_template_variables( $instance, $args ){
		return array();
	}

	/**
	 * Render a sub widget. This should be used inside template files.
	 *
	 * @param $class
	 * @param $args
	 * @param $instance
	 */
	public function sub_widget($class, $args, $instance){
		if(!class_exists($class)) return;
		$widget = new $class;

		$args['before_widget'] = '';
		$args['after_widget'] = '';

		$widget->widget( $args, $instance );
	}

	/**
	 * Add default values to the instance.
	 *
	 * @param $form
	 * @param $instance
	 */
	function add_defaults($form, $instance, $level = 0){
		if( $level > 10 ) return $instance;

		foreach($form as $id => $field) {

			if( $field['type'] == 'repeater' ) {
				if( !empty($instance[$id]) ) {
					foreach( array_keys($instance[$id]) as $i ){
						$instance[$id][$i] = $this->add_defaults( $field['fields'], $instance[$id][$i], $level + 1 );
					}
				}
			}
			else if( $field['type'] == 'section' ) {
				if( empty($instance[$id]) ) {
					$instance[$id] = array();
				}
				$instance[$id] = $this->add_defaults( $field['fields'], $instance[$id], $level + 1 );
			}
			else {
				if( !isset( $instance[$id] ) ) {
					$instance[$id] = isset( $field['default'] ) ? $field['default'] : '';
				}
			}
		}

		return $instance;
	}

	/**
	 * Display the widget form.
	 *
	 * @param array $instance
	 * @return string|void
	 */
	public function form( $instance ) {
		$this->enqueue_scripts();
		$instance = $this->modify_instance($instance);
		$instance = $this->add_defaults( $this->form_options(), $instance );

		// Filter the instance specifically for the form
		$instance = apply_filters('distinctivethemes_widgets_form_instance_' . $this->id_base, $instance, $this);

		$form_id = 'siteorigin_widget_form_'.md5( uniqid( rand(), true ) );
		$class_name = str_replace( '_', '-', strtolower(get_class($this)) );

		if( empty( $instance['_sow_form_id'] ) ) {
			$instance['_sow_form_id'] = uniqid();
		}

		?>
		<div class="siteorigin-widget-form siteorigin-widget-form-main siteorigin-widget-form-main-<?php echo esc_attr($class_name) ?>" id="<?php echo $form_id ?>" data-class="<?php echo get_class($this) ?>" style="display: none">
			<?php
			/* @var $field_factory DistinctiveThemes_SiteOrigin_Widget_Field_Factory */
			$field_factory = DistinctiveThemes_SiteOrigin_Widget_Field_Factory::getInstance();
			$fields_javascript_variables = array();
			foreach( $this->form_options() as $field_name => $field_options ) {
				/* @var $field DistinctiveThemes_SiteOrigin_Widget_Field_Base */
				$field = $field_factory->create_field( $field_name, $field_options, $this );
				$field->render( isset( $instance[$field_name] ) ? $instance[$field_name] : null, $instance );
				$field_js_vars = $field->get_javascript_variables();
				if( ! empty( $field_js_vars ) ) {
					$fields_javascript_variables[$field_name] = $field_js_vars;
				}
				$field->enqueue_scripts();
				$this->fields[$field_name] = $field;
			}
			?>
			<input type="hidden" name="<?php echo $this->get_field_name('_sow_form_id') ?>" value="<?php echo esc_attr( $instance['_sow_form_id'] ) ?>" class="dapper-widgets-form-id" />
		</div>
		<div class="siteorigin-widget-form-no-styles">
			<?php $this->scripts_loading_message() ?>
		</div>

		<?php if( $this->widget_options['has_preview'] ) : ?>
			<div class="siteorigin-widget-preview" style="display: none">
				<a href="#" class="siteorigin-widget-preview-button button-secondary"><?php _e('Preview', 'dapper-pro') ?></a>
			</div>
		<?php endif; ?>

		<?php if( !empty( $this->widget_options['help'] ) ) : ?>
			<a href="<?php echo sow_esc_url($this->widget_options['help']) ?>" class="siteorigin-widget-help-link siteorigin-panels-help-link" target="_blank"><?php _e('Help', 'dapper-pro') ?></a>
		<?php endif; ?>

		<script type="text/javascript">
			( function($) {
				if(typeof window.sow_field_javascript_variables == 'undefined') window.sow_field_javascript_variables = {};
				window.sow_field_javascript_variables["<?php echo get_class($this) ?>"] = <?php echo json_encode( $fields_javascript_variables ) ?>;

				if(typeof $.fn.sowSetupForm != 'undefined') {
					$('#<?php echo $form_id ?>').sowSetupForm();
				}
				else {
					// Init once admin scripts have been loaded
					$( document).on('sowadminloaded', function(){
						$('#<?php echo $form_id ?>').sowSetupForm();
					});
				}
			} )( jQuery );
		</script>
		<?php
	}

	function scripts_loading_message(){
		?>
		<p><strong><?php _e('This widget has scripts and styles that need to be loaded before you can use it. Please save and reload your current page.', 'dapper-pro') ?></strong></p>
		<p><strong><?php _e('You will only need to do this once.', 'dapper-pro') ?></strong></p>
		<?php
	}

	/**
	 * Enqueue the admin scripts for the widget form.
	 */
	function enqueue_scripts(){

		if( !wp_script_is('siteorigin-widget-admin') ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'siteorigin-widget-admin', plugin_dir_url(__FILE__) . 'base/css/admin.css', array( 'media-views' ), SOW_BUNDLE_VERSION );


			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_media();
			wp_enqueue_script( 'siteorigin-widget-admin', plugin_dir_url(__FILE__) . 'base/js/admin.min.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-slider' ), SOW_BUNDLE_VERSION, true );

			wp_localize_script( 'siteorigin-widget-admin', 'soWidgets', array(
				'ajaxurl' => wp_nonce_url( admin_url('admin-ajax.php'), 'widgets_action', '_widgets_nonce' ),
				'sure' => __('Are you sure?', 'dapper-pro')
			) );

			global $wp_customize;
			if ( isset( $wp_customize ) ) {
				$this->footer_admin_templates();
			}
			else {
				add_action( 'admin_footer', array( $this, 'footer_admin_templates' ) );
			}
		}

		if( $this->using_posts_selector() ) {
			siteorigin_widget_post_selector_enqueue_admin_scripts();
		}

		// This lets the widget enqueue any specific admin scripts
		$this->enqueue_admin_scripts();
		do_action( 'distinctivethemes_widgets_enqueue_admin_scripts_' . $this->id_base, $this );
	}

	/**
	 * Display all the admin stuff for the footer
	 */
	function footer_admin_templates(){
		?>
		<script type="text/template" id="dapper-so-advanced-widgets-tpl-preview-dialog">
			<div class="siteorigin-widget-preview-dialog">
				<div class="dapper-widgets-preview-modal-overlay"></div>

				<div class="dapper-so-widget-toolbar">
					<h3><?php _e('Widget Preview', 'dapper-pro') ?></h3>
					<div class="close"><span class="dashicons dashicons-arrow-left-alt2"></span></div>
				</div>

				<div class="dapper-so-widget-iframe">
					<iframe name="siteorigin-widget-preview-iframe" id="siteorigin-widget-preview-iframe" style="visibility: hidden"></iframe>
				</div>

				<form target="siteorigin-widget-preview-iframe" action="<?php echo wp_nonce_url( admin_url('admin-ajax.php'), 'widgets_action', '_widgets_nonce' ) ?>" method="post">
					<input type="hidden" name="action" value="so_widgets_preview" />
					<input type="hidden" name="data" value="" />
					<input type="hidden" name="class" value="" />
				</form>

			</div>
		</script>
		<?php

		// Give other plugins a chance to add their own
		do_action('distinctivethemes_widgets_footer_admin_templates');
	}

	/**
	 * Checks if the current widget is using a posts selector
	 *
	 * @return bool
	 */
	function using_posts_selector(){
		if( empty( $this->form_options ) ) {
			$this->form_options = $this->initialize_form();
		}

		foreach($this->form_options as $field) {
			if(!empty($field['type']) && $field['type'] == 'posts') return true;
		}
		return false;
	}

	/**
	 * Update the widget instance.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array|void
	 */
	public function update( $new_instance, $old_instance ) {
		if( !class_exists('DistinctiveThemes_SiteOrigin_Widgets_Color_Object') ) require plugin_dir_path( __FILE__ ).'inc/color.php';

		$form_options = $this->form_options();
		if( ! empty( $form_options ) ) {
			/* @var $field_factory DistinctiveThemes_SiteOrigin_Widget_Field_Factory */
			$field_factory = DistinctiveThemes_SiteOrigin_Widget_Field_Factory::getInstance();
			foreach ( $form_options as $field_name => $field_options ) {
				/* @var $field DistinctiveThemes_SiteOrigin_Widget_Field_Base */
				if ( !empty( $this->fields ) && !empty( $this->fields[$field_name] ) ) {
					$field = $this->fields[$field_name];
				}
				else {
					$field = $field_factory->create_field( $field_name, $field_options, $this );
					$this->fields[$field_name] = $field;
				}
				$new_instance[$field_name] = $field->sanitize( isset( $new_instance[$field_name] ) ? $new_instance[$field_name] : null, $new_instance );
				$new_instance = $field->sanitize_instance( $new_instance );
			}

			// Let other plugins also sanitize the instance
			$new_instance = apply_filters( 'distinctivethemes_widgets_sanitize_instance', $new_instance, $form_options, $this );
			$new_instance = apply_filters( 'distinctivethemes_widgets_sanitize_instance_' . $this->id_base, $new_instance, $form_options, $this );
		}

		return $new_instance;
	}

	/**
	 * Get any font fields which may be used by this widget.
	 *
	 * @param $instance
	 *
	 * @return array
	 */
	function get_google_font_fields( $instance ) {
		return array();
	}

	/**
	 * Utility function to get a field name for a widget field.
	 *
	 * @param $field_name
	 * @param array $container
	 * @return mixed|string
	 */
	public function so_get_field_name( $field_name, $container = array() ) {
		if( empty($container) ) {
			return $this->get_field_name( $field_name );
		}
		else {
			// We also need to add the container fields
			$container_extras = '';
			foreach($container as $r) {
				$container_extras .= '[' . $r['name'] . ']';

				if( $r['type'] == 'repeater' ) {
					$container_extras .= '[#' . $r['name'] . '#]';
				}
			}

			$name = $this->get_field_name( '{{{FIELD_NAME}}}' );
			$name = str_replace('[{{{FIELD_NAME}}}]', $container_extras.'[' . esc_attr($field_name) . ']', $name);
			return $name;
		}
	}

	/**
	 * Get the ID of this field.
	 *
	 * @param $field_name
	 * @param array $container
	 * @param boolean $is_template
	 *
	 * @return string
	 */
	public function so_get_field_id( $field_name, $container = array(), $is_template = false ) {
		if( empty($container) ) {
			return $this->get_field_id($field_name);
		}
		else {
			$name = array();
			foreach ( $container as $container_item ) {
				$name[] = $container_item['name'];
			}
			$name[] = $field_name;
			$field_id_base = $this->get_field_id(implode('-', $name));
			if ( $is_template ) {
				return $field_id_base . '-_id_';
			}
			if ( ! isset( $this->field_ids[ $field_id_base ] ) ) {
				$this->field_ids[ $field_id_base ] = 1;
			}
			$curId = $this->field_ids[ $field_id_base ]++;
			return $field_id_base . '-' . $curId;
		}
	}

	/**
	 * Parse markdown
	 *
	 * @param $markdown
	 * @return string The HTML
	 */
	function parse_markdown( $markdown ){
		if( !class_exists('Markdown_Parser') ) include plugin_dir_path(__FILE__).'inc/markdown.php';
		$parser = new Markdown_Parser();

		return $parser->transform($markdown);
	}

	/**
	 * Get a hash that uniquely identifies this instance.
	 *
	 * @param $instance
	 * @return string
	 */
	function get_style_hash( $instance ) {
		$vars = method_exists($this, 'get_style_hash_variables') ? $this->get_style_hash_variables( $instance ) : $this->get_less_variables( $instance );
		$version = property_exists( $this, 'version' ) ? $this->version : '';

		return substr( md5( json_encode( $vars ) . $version ), 0, 12 );
	}

	/**
	 * Get the template name that we'll be using to render this widget.
	 *
	 * @param $instance
	 * @return mixed
	 */
	function get_template_name( $instance ) {
		return 'default';
	}

	/**
	 * Get the name of the directory in which we should look for the template. Relative to root of widget folder.
	 *
	 * @return mixed
	 */
	function get_template_dir( $instance ) {
		return 'tpl';
	}

	/**
	 * Get the LESS style name we'll be using for this widget.
	 *
	 * @param $instance
	 * @return mixed
	 */
	function get_style_name( $instance ) {
		return 'default';
	}

	/**
	 * Get any variables that need to be substituted by
	 *
	 * @param $instance
	 * @return array
	 */
	function get_less_variables( $instance ){
		return array();
	}

	/**
	 * Filter the variables we'll be storing in temporary storage for this instance if we're using `instance_storage`
	 *
	 * @param $instance
	 *
	 * @return mixed
	 */
	function modify_stored_instance( $instance ){
		return $instance;
	}

	/**
	 * Get the stored instance based on the hash.
	 *
	 * @param $hash
	 *
	 * @return object The instance
	 */
	function get_stored_instance( $hash ) {
		return get_transient('sow_inst[' . $this->id_base . '][' . $hash . ']');
	}

	/**
	 * This function can be overwritten to modify form values in the child widget.
	 *
	 * @param $form
	 * @return mixed
	 */
	function modify_form( $form ) {
		return $form;
	}


	/**
	 * This function can be overwritten to modify form values in the child widget.
	 *
	 * @param $child_widget_form
	 * @param $child_widget
	 * @return mixed
	 */
	function modify_child_widget_form($child_widget_form, $child_widget) {
		return $child_widget_form;
	}

	/**
	 * This function should be overwritten by child widgets to filter an instance. Run before rendering the form and widget.
	 *
	 * @param $instance
	 *
	 * @return mixed
	 */
	function modify_instance( $instance ){
		return $instance;
	}

	/**
	 * Can be overwritten by child widgets to make variables available to javascript via ajax calls. These are designed to be used in the admin.
	 */
	function get_javascript_variables(){

	}

	/**
	 * Used by child widgets to register scripts to be enqueued for the frontend.
	 *
	 * @param array $scripts an array of scripts. Each element is an array that corresponds to wp_enqueue_script arguments
	 */
	public function register_frontend_scripts( $scripts ){
		foreach ( $scripts as $script ) {
			if ( ! isset( $this->frontend_scripts[ $script[0] ] ) ) {
				$this->frontend_scripts[$script[0]] = $script;
			}
		}
	}

	/**
	 * Enqueue all the registered scripts
	 */
	function enqueue_registered_scripts() {
		foreach ( $this->frontend_scripts as $f_script ) {
			if ( ! wp_script_is( $f_script[0] ) ) {
				wp_enqueue_script(
					$f_script[0],
					isset( $f_script[1] ) ? $f_script[1] : false,
					isset( $f_script[2] ) ? $f_script[2] : array(),
					!empty( $f_script[3] ) ? $f_script[3] : SOW_BUNDLE_VERSION,
					isset( $f_script[4] ) ? $f_script[4] : false
				);
			}
		}
	}

	/**
	 * Used by child widgets to register styles to be enqueued for the frontend.
	 *
	 * @param array $styles an array of styles. Each element is an array that corresponds to wp_enqueue_style arguments
	 */
	public function register_frontend_styles( $styles ) {
		foreach ( $styles as $style ) {
			if ( ! isset( $this->frontend_styles[ $style[0] ] ) ) {
				$this->frontend_styles[$style[0]] = $style;
			}
		}
	}

	/**
	 * Enqueue any frontend styles that were registered
	 */
	function enqueue_registered_styles() {
		foreach ( $this->frontend_styles as $f_style ) {
			if ( ! wp_style_is( $f_style[0] ) ) {
				wp_enqueue_style(
					$f_style[0],
					isset( $f_style[1] ) ? $f_style[1] : false,
					isset( $f_style[2] ) ? $f_style[2] : array(),
					!empty( $f_script[3] ) ? $f_script[3] : SOW_BUNDLE_VERSION,
					isset( $f_style[4] ) ? $f_style[4] : "all"
				);
			}
		}
	}

	/**
	 * Can be overridden by child widgets to enqueue scripts and styles for the frontend, but child widgets should
	 * rather register scripts and styles using register_frontend_scripts() and register_frontend_styles(). This function
	 * will then ensure that the scripts are not enqueued more than once.
	 */
	function enqueue_frontend_scripts( $instance ){
		$this->enqueue_registered_scripts();
		$this->enqueue_registered_styles();

		// Give plugins a chance to enqueue additional frontend scripts
		do_action('distinctivethemes_widgets_enqueue_frontend_scripts_' . $this->id_base, $instance, $this);
	}

	/**
	 * Can be overwritten by child widgets to enqueue admin scripts and styles if necessary.
	 */
	function enqueue_admin_scripts(){ }

	/**
	 * Check if we're currently in a preview
	 *
	 * @param array $instance
	 *
	 * @return bool
	 */
	function is_preview( $instance = array() ){
		// Check if the instance is a preview
		if( !empty( $instance[ 'is_preview' ] ) ) return true;

		// Check if the general request is a preview
		return
			is_preview() ||  // is this a standard preview
			$this->is_customize_preview() ||    // Is this a customizer preview
			!empty( $_GET['distinctivethemes_panels_live_editor'] ) ||     // Is this a Page Builder live editor request
			( !empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'so_panels_builder_content' );    // Is this a Page Builder content ajax request
	}

}
