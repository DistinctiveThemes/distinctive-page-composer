<?php

class DistinctiveThemes_Panels_Widget_Call_To_Action extends DistinctiveThemes_Panels_Widget  {
	function __construct() {
		parent::__construct(
			__('Call To Action (PB)', 'distinctivethemes-panels'),
			array(
				'description' => __('A Call to Action block', 'distinctivethemes-panels'),
				'default_style' => 'simple',
			),
			array(),
			array(
				'title' => array(
					'type' => 'text',
					'label' => __('Title', 'distinctivethemes-panels'),
				),
				'subtitle' => array(
					'type' => 'text',
					'label' => __('Sub Title', 'distinctivethemes-panels'),
				),
				'button_text' => array(
					'type' => 'text',
					'label' => __('Button Text', 'distinctivethemes-panels'),
				),
				'button_url' => array(
					'type' => 'text',
					'label' => __('Button URL', 'distinctivethemes-panels'),
				),
				'button_new_window' => array(
					'type' => 'checkbox',
					'label' => __('Open In New Window', 'distinctivethemes-panels'),
				),
			)
		);

		// We need the button style
		$this->add_sub_widget('button', __('Button', 'distinctivethemes-panels'), 'DistinctiveThemes_Panels_Widget_Button');
	}
}