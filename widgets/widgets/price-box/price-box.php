<?php

class DistinctiveThemes_Panels_Widget_Price_Box extends DistinctiveThemes_Panels_Widget  {
	function __construct() {
		parent::__construct(
			__('Price Box (PB)', 'distinctivethemes-panels'),
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
				'price' => array(
					'type' => 'text',
					'label' => __('Price', 'distinctivethemes-panels'),
				),
				'per' => array(
					'type' => 'text',
					'label' => __('Per', 'distinctivethemes-panels'),
				),
				'information' => array(
					'type' => 'text',
					'label' => __('Information Text', 'distinctivethemes-panels'),
				),
				'features' => array(
					'type' => 'textarea',
					'label' => __('Features Text', 'distinctivethemes-panels'),
					'description' => __('Start each new point with an asterisk (*)', 'distinctivethemes-panels'),
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

		$this->add_sub_widget('button', __('Button', 'distinctivethemes-panels'), 'DistinctiveThemes_Panels_Widget_Button');
		$this->add_sub_widget('list', __('Feature List', 'distinctivethemes-panels'), 'DistinctiveThemes_Panels_Widget_List');
	}
}