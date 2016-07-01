<?php

class DistinctiveThemes_Panels_Widget_Animated_Image extends DistinctiveThemes_Panels_Widget  {
	function __construct() {
		parent::__construct(
			__('Animated Image (PB)', 'distinctivethemes-panels'),
			array(
				'description' => __('An image that animates in when it enters the screen.', 'distinctivethemes-panels'),
				'default_style' => 'simple',
			),
			array(),
			array(
				'image' => array(
					'type' => 'text',
					'label' => __('Image URL', 'distinctivethemes-panels'),
				),
				'animation' => array(
					'type' => 'select',
					'label' => __('Animation', 'distinctivethemes-panels'),
					'options' => array(
						'fade' => __('Fade In', 'distinctivethemes-panels'),
						'slide-up' => __('Slide Up', 'distinctivethemes-panels'),
						'slide-down' => __('Slide Down', 'distinctivethemes-panels'),
						'slide-left' => __('Slide Left', 'distinctivethemes-panels'),
						'slide-right' => __('Slide Right', 'distinctivethemes-panels'),
					)
				),
			)
		);
	}

	function enqueue_scripts(){
		static $enqueued = false;
		if(!$enqueued) {
			wp_enqueue_script('distinctivethemes-widgets-'.$this->origin_id.'-onscreen', plugin_dir_url(__FILE__).'js/onscreen.js', array('jquery'), DISTINCTIVETHEMES_PANELS_VERSION);
			wp_enqueue_script('distinctivethemes-widgets-'.$this->origin_id, plugin_dir_url(__FILE__).'js/main.js', array('jquery'), DISTINCTIVETHEMES_PANELS_VERSION);
			$enqueued = true;
		}

	}
}