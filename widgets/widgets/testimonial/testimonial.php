<?php

class DistinctiveThemes_Panels_Widget_Testimonial extends DistinctiveThemes_Panels_Widget  {
	function __construct() {
		parent::__construct(
			__('Testimonial (PB)', 'distinctivethemes-panels'),
			array(
				'description' => __('Displays a bullet list of points', 'distinctivethemes-panels'),
				'default_style' => 'simple',
			),
			array(),
			array(
				'name' => array(
					'type' => 'text',
					'label' => __('Name', 'distinctivethemes-panels'),
				),
				'location' => array(
					'type' => 'text',
					'label' => __('Location', 'distinctivethemes-panels'),
				),
				'image' => array(
					'type' => 'text',
					'label' => __('Image', 'distinctivethemes-panels'),
				),
				'text' => array(
					'type' => 'textarea',
					'label' => __('Text', 'distinctivethemes-panels'),
				),
				'url' => array(
					'type' => 'text',
					// TRANSLATORS: Uniform Resource Locator
					'label' => __('URL', 'distinctivethemes-panels'),
				),
				'new_window' => array(
					'type' => 'checkbox',
					'label' => __('Open In New Window', 'distinctivethemes-panels'),
				),
			)
		);
	}
}