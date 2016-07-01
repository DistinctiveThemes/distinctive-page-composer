<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

global $dapper_cl_uri;

return array(

	'cl-counter' => array(
		'title' => __( 'DistinctiveThemes: Stats Counter', 'dapper-pro' ),
		'description' => __( 'Animated text with numbers', 'dapper-pro' ),
		'icon' => $dapper_cl_uri . '/admin/img/cl-counter.png',
		'widget_php_class' => 'CL_Widget_Counter',
		'params' => array(
			'initial' => array(
				'title' => __( 'Initial Counter value', 'dapper-pro' ),
				'description' => __( 'Initial string with all the prefixes, suffixes and decimal marks if needed.', 'dapper-pro' ),
				'type' => 'textfield',
				'std' => '0',
			),
			'final' => array(
				'title' => __( 'Final Counter value', 'dapper-pro' ),
				'description' => __( 'Final value the way it should look like, when the animation ends.', 'dapper-pro' ),
				'type' => 'textfield',
				'std' => '100',
			),
			'title' => array(
				'title' => __( 'Counter Title', 'dapper-pro' ),
				'type' => 'textfield',
				'std' => '',
			),
			'duration' => array(
				'title' => __( 'Animation Duration', 'dapper-pro' ),
				'description' => __( 'In milliseconds', 'dapper-pro' ),
				'type' => 'textfield',
				'std' => '3000',
				'group' => __( 'Custom', 'dapper-pro' ),
			),
			'value_size' => array(
				'title' => __( 'Value Font Size', 'dapper-pro' ),
				'type' => 'textfield',
				'std' => '50',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'group' => __( 'Custom', 'dapper-pro' ),
			),
			'title_size' => array(
				'title' => __( 'Title Font Size', 'dapper-pro' ),
				'type' => 'textfield',
				'std' => '20',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'group' => __( 'Custom', 'dapper-pro' ),
			),
			'value_color' => array(
				'title' => __( 'Value Color', 'dapper-pro' ),
				'type' => 'color',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'group' => __( 'Custom', 'dapper-pro' ),
			),
			'title_color' => array(
				'title' => __( 'Title Color', 'dapper-pro' ),
				'type' => 'color',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'group' => __( 'Custom', 'dapper-pro' ),
			),
			'el_class' => array(
				'title' => __( 'Extra class name', 'dapper-pro' ),
				'description' => __( 'If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'dapper-pro' ),
				'type' => 'textfield',
				'group' => __( 'Custom', 'dapper-pro' ),
			),
		),
	),
	'cl-itext' => array(
		'title' => __( 'DistinctiveThemes:Interactive Text', 'dapper-pro' ),
		'description' => __( 'Text with some dynamicatlly changing part', 'dapper-pro' ),
		'icon' => $dapper_cl_uri . '/admin/img/cl-itext.png',
		'widget_php_class' => 'CL_Widget_Itext',
		'params' => array(
			/**
			 * General
			 */
			'texts' => array(
				'title' => __( 'Text States', 'dapper-pro' ),
				'description' => __( 'Each state from a new line', 'dapper-pro' ),
				'type' => 'textarea',
				'std' => 'We create great design' . "\n" . 'We create great websites' . "\n" . 'We create great code',
			),
			'dynamic_bold' => array(
				'title' => '',
				'type' => 'checkboxes',
				'options' => array(
					TRUE => __( 'Bold Dynamic Text', 'dapper-pro' ),
				),
				'std' => TRUE,
			),
			'animation_type' => array(
				'title' => __( 'Animation Type', 'dapper-pro' ),
				'type' => 'select',
				'options' => array(
					'fadeIn' => __( 'Fade in the whole part', 'dapper-pro' ),
					'flipInX' => __( 'Flip the whole part', 'dapper-pro' ),
					'flipInXChars' => __( 'Flip character by character', 'dapper-pro' ),
					'zoomIn' => __( 'Zoom in the whole part', 'dapper-pro' ),
					'zoomInChars' => __( 'Zoom in character by character', 'dapper-pro' ),
				),
				'std' => 'fadeIn',
			),
			/**
			 * Custom
			 */
			'font_size' => array(
				'title' => __( 'Font Size', 'dapper-pro' ),
				'type' => 'textfield',
				'std' => '50px',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'group' => __( 'Custom', 'dapper-pro' ),
			),
			'font_size_mobile' => array(
				'title' => __( 'Font Size for Mobiles', 'dapper-pro' ),
				'description' => __( 'This value will be applied when screen width is less than 600px', 'dapper-pro' ),
				'type' => 'textfield',
				'std' => '30px',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'group' => __( 'Custom', 'dapper-pro' ),
			),
			'color' => array(
				'title' => __( 'Basic Text Color', 'dapper-pro' ),
				'type' => 'color',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'group' => __( 'Custom', 'dapper-pro' ),
			),
			'dynamic_color' => array(
				'title' => __( 'Dynamic Text Color', 'dapper-pro' ),
				'type' => 'color',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'group' => __( 'Custom', 'dapper-pro' ),
			),
			'align' => array(
				'title' => __( 'Text Alignment', 'dapper-pro' ),
				'type' => 'select',
				'options' => array(
					'left' => __( 'Left', 'dapper-pro' ),
					'center' => __( 'Center', 'dapper-pro' ),
					'right' => __( 'Right', 'dapper-pro' ),
				),
				'std' => 'center',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'group' => __( 'Custom', 'dapper-pro' ),
			),
			'tag' => array(
				'title' => __( 'Tag Name', 'dapper-pro' ),
				'type' => 'select',
				'options' => array(
					'div' => 'div',
					'h1' => 'h1',
					'h2' => 'h2',
					'h3' => 'h3',
					'h4' => 'h4',
					'h5' => 'h5',
					'h6' => 'h6',
					'p' => 'p',
				),
				'std' => 'h2',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'group' => __( 'Custom', 'dapper-pro' ),
			),
			'duration' => array(
				'title' => __( 'Animation Duration', 'dapper-pro' ),
				'description' => __( 'In milliseconds', 'dapper-pro' ),
				'type' => 'textfield',
				'std' => '300',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'group' => __( 'Custom', 'dapper-pro' ),
			),
			'delay' => array(
				'title' => __( 'Animation Delay', 'dapper-pro' ),
				'description' => __( 'In seconds', 'dapper-pro' ),
				'type' => 'textfield',
				'std' => '5',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'group' => __( 'Custom', 'dapper-pro' ),
			),
			'el_class' => array(
				'title' => __( 'Extra class name', 'dapper-pro' ),
				'description' => __( 'If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'dapper-pro' ),
				'type' => 'textfield',
				'group' => __( 'Custom', 'dapper-pro' ),
			),
		),
	),
	'cl-popup' => array(
		'title' => __( 'DistinctiveThemes: Modal Popup', 'dapper-pro' ),
		'description' => __( 'Dialog box displayed above the page content', 'dapper-pro' ),
		'icon' => $dapper_cl_uri . '/admin/img/cl-popup.png',
		'widget_php_class' => 'CL_Widget_Modal',
		'params' => array(
			/**
			 * General
			 */
			'title' => array(
				'title' => __( 'Popup Title', 'dapper-pro' ),
				'type' => 'textfield',
				'std' => '',
			),
			'content' => array(
				'title' => __( 'Popup Content', 'dapper-pro' ),
				'type' => 'html',
				'std' => '',
			),
			/**
			 * Trigger
			 */
			'show_on' => array(
				'title' => __( 'Show Popup On', 'dapper-pro' ),
				'type' => 'select',
				'options' => array(
					'btn' => __( 'Button Click', 'dapper-pro' ),
					'text' => __( 'Text Click', 'dapper-pro' ),
					'image' => __( 'Image Click', 'dapper-pro' ),
					'load' => __( 'Page Load', 'dapper-pro' ),
				),
				'std' => 'btn',
			),
			'btn_label' => array(
				'title' => __( 'Button / Text Label', 'dapper-pro' ),
				'type' => 'textfield',
				'std' => 'READ MORE', // Not translatable
				'show_if' => array( 'show_on', 'in', array( 'btn', 'text' ) ),
				
			),
			'btn_bgcolor' => array(
				'title' => __( 'Button Background Color', 'dapper-pro' ),
				'type' => 'color',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'show_if' => array( 'show_on', '=', 'btn' ),
			),
			'btn_color' => array(
				'title' => __( 'Button Text Color', 'dapper-pro' ),
				'type' => 'color',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'show_if' => array( 'show_on', '=', 'btn' ),
				
			),
			'image' => array(
				'title' => __( 'Image', 'dapper-pro' ),
				'type' => 'image',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'show_if' => array( 'show_on', '=', 'image' ),
			),
			'image_size' => array(
				'title' => __( 'Image Size', 'dapper-pro' ),
				'type' => 'select',
				'options' => dapper_cl_image_sizes_select_values(),
				'std' => 'large',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'show_if' => array( 'show_on', '=', 'image' ),
			),
			'text_size' => array(
				'title' => __( 'Text Size', 'dapper-pro' ),
				'type' => 'textfield',
				'std' => '',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'show_if' => array( 'show_on', '=', 'text' ),
				
			),
			'text_color' => array(
				'title' => __( 'Text Color', 'dapper-pro' ),
				'type' => 'color',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				'show_if' => array( 'show_on', '=', 'text' ),
				
			),
			'align' => array(
				'title' => __( 'Button / Image / Text Alignment', 'dapper-pro' ),
				'type' => 'select',
				'options' => array(
					'left' => __( 'Left', 'dapper-pro' ),
					'center' => __( 'Center', 'dapper-pro' ),
					'right' => __( 'Right', 'dapper-pro' ),
				),
				'std' => 'left',
				'show_if' => array( 'show_on', 'in', array( 'btn', 'image', 'text' ) ),
				
			),
			'show_delay' => array(
				'title' => __( 'Popup Show Delay', 'dapper-pro' ),
				'description' => __( 'In seconds', 'dapper-pro' ),
				'type' => 'textfield',
				'std' => '2',
				'show_if' => array( 'show_on', '=', 'load' ),
				
			),
			/**
			 * Popup Style
			 */
			'size' => array(
				'title' => __( 'Popup Size', 'dapper-pro' ),
				'type' => 'select',
				'options' => array(
					's' => __( 'Small', 'dapper-pro' ),
					'm' => __( 'Medium', 'dapper-pro' ),
					'l' => __( 'Large', 'dapper-pro' ),
					'xl' => __( 'Huge', 'dapper-pro' ),
					'f' => __( 'Fullscreen', 'dapper-pro' ),
				),
				
			),
			'paddings' => array(
				'type' => 'checkboxes',
				'options' => array(
					'none' => __( 'Remove white space around popup content', 'dapper-pro' ),
				),
				'std' => 'default',
				
			),
			'animation' => array(
				'title' => __( 'Appearance Animation', 'dapper-pro' ),
				'type' => 'select',
				'options' => array(
					// Inspired by http://tympanus.net/Development/ModalWindowEffects/
					'fadeIn' => __( 'Fade In', 'dapper-pro' ),
					'scaleUp' => __( 'Scale Up', 'dapper-pro' ),
					'scaleDown' => __( 'Scale Down', 'dapper-pro' ),
					'slideTop' => __( 'Slide from Top', 'dapper-pro' ),
					'slideBottom' => __( 'Slide from Bottom', 'dapper-pro' ),
					'flipHor' => __( '3D Flip (Horizontal)', 'dapper-pro' ),
					'flipVer' => __( '3D Flip (Vertical)', 'dapper-pro' ),
				),
				
			),
			'border_radius' => array(
				'title' => __( 'Border Radius', 'dapper-pro' ),
				'type' => 'textfield',
				'std' => '0',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				
			),
			'overlay_bgcolor' => array(
				'title' => __( 'Overlay Background Color', 'dapper-pro' ),
				'type' => 'color',
				'std' => 'rgba(0,0,0,0.75)',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				
			),
			'title_bgcolor' => array(
				'title' => __( 'Header Background Color', 'dapper-pro' ),
				'type' => 'color',
				'std' => '#f2f2f2',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				
			),
			'title_textcolor' => array(
				'title' => __( 'Header Text Color', 'dapper-pro' ),
				'type' => 'color',
				'std' => '#666666',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				
			),
			'content_bgcolor' => array(
				'title' => __( 'Content Background Color', 'dapper-pro' ),
				'type' => 'color',
				'std' => '#ffffff',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				
			),
			'content_textcolor' => array(
				'title' => __( 'Content Text Color', 'dapper-pro' ),
				'type' => 'color',
				'std' => '#333333',
				'classes' => 'dapper_cl_col-sm-6 dapper_cl_column',
				
			),
			'el_class' => array(
				'title' => __( 'Extra class name', 'dapper-pro' ),
				'description' => __( 'If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'dapper-pro' ),
				'type' => 'textfield',
				
			),
		),
	),
);
