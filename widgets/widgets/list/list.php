<?php

class DistinctiveThemes_Panels_Widget_List extends DistinctiveThemes_Panels_Widget  {
	function __construct() {
		parent::__construct(
			__('List (PB)', 'distinctivethemes-panels'),
			array(
				'description' => __('Displays a bullet list of elements', 'distinctivethemes-panels'),
				'default_style' => 'simple',
			),
			array(),
			array(
				'title' => array(
					'type' => 'text',
					'label' => __('Title', 'distinctivethemes-panels'),
				),
				'text' => array(
					'type' => 'textarea',
					'label' => __('Text', 'distinctivethemes-panels'),
					'description' => __('Start each new point with an asterisk (*)', 'distinctivethemes-panels'),
				),
			)
		);
	}

	static function create_list($text){
		// Add the list items
		$text = preg_replace( "/\*+(.*)?/i", "<ul><li>$1</li></ul>", $text );
		$text = preg_replace( "/(\<\/ul\>\n(.*)\<ul\>*)+/", "", $text );
		$text = wpautop( $text );

		// Return sanitized version of the list
		return wp_kses_post($text);
	}
}