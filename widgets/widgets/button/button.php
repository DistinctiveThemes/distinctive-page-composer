<?php

class DistinctiveThemes_Panels_Widget_Button extends DistinctiveThemes_Panels_Widget  {
	function __construct() {
		parent::__construct(
			__('Button (PB)', 'distinctivethemes-panels'),
			array(
				'description' => __('A simple button', 'distinctivethemes-panels'),
				'default_style' => 'simple',
			),
			array(),
			array(
				'text' => array(
					'type' => 'text',
					'label' => __('Text', 'distinctivethemes-panels'),
				),
				'url' => array(
					'type' => 'text',
					'label' => __('Destination URL', 'distinctivethemes-panels'),
				),
				'new_window' => array(
					'type' => 'checkbox',
					'label' => __('Open In New Window', 'distinctivethemes-panels'),
				),
				'align' => array(
					'type' => 'select',
					'label' => __('Button Alignment', 'distinctivethemes-panels'),
					'options' => array(
						'left' => __('Left', 'distinctivethemes-panels'),
						'right' => __('Right', 'distinctivethemes-panels'),
						'center' => __('Center', 'distinctivethemes-panels'),
						'justify' => __('Justify', 'distinctivethemes-panels'),
					)
				),
			)
		);
	}

	function widget_classes($classes, $instance) {
		$classes[] = 'align-'.(empty($instance['align']) ? 'none' : $instance['align']);
		return $classes;
	}
}