<?php

abstract class DistinctiveThemes_SiteOrigin_Widget_Base_Slider extends DistinctiveThemes_SiteOrigin_Widget {

	/**
	 * Register all the frontend scripts and styles for the base slider.
	 */
	function initialize() {

		$frontend_scripts = array();
		$frontend_scripts[] = array(
			'dapper-sow-slider-slider-cycle2',
			get_template_directory_uri() . '/page_builder_widgets/advanced-widgets/js/jquery.cycle' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION
		);
		if( function_exists('wp_is_mobile') && wp_is_mobile() ) {
			$frontend_scripts[] = array(
				'dapper-sow-slider-slider-cycle2-swipe',
				get_template_directory_uri() . '/page_builder_widgets/advanced-widgets/js/jquery.cycle.swipe' . SOW_BUNDLE_JS_SUFFIX . '.js',
				array( 'jquery' ),
				SOW_BUNDLE_VERSION
			);
		}
		$frontend_scripts[] = array(
			'dapper-sow-slider-slider',
			get_template_directory_uri() . '/page_builder_widgets/advanced-widgets/js/slider/jquery.slider' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION
		);

		$this->register_frontend_scripts( $frontend_scripts );
		$this->register_frontend_styles(
			array(
				array(
					'dapper-sow-slider-slider',
					get_template_directory_uri() . '/page_builder_widgets/advanced-widgets/' . 'css/slider/slider.css',
					array(),
					SOW_BUNDLE_VERSION
				)
			)
		);
	}

	/**
	 * The control array required for the slider
	 *
	 * @return array
	 */
	function control_form_fields(){
		return array(
			'speed' => array(
				'type' => 'number',
				'label' => __('Animation speed', 'dapper-pro'),
				'description' => __('Animation speed in milliseconds.', 'dapper-pro'),
				'default' => 800,
			),

			'timeout' => array(
				'type' => 'number',
				'label' => __('Timeout', 'dapper-pro'),
				'description' => __('How long each frame is displayed for in milliseconds.', 'dapper-pro'),
				'default' => 8000,
			),

			'nav_color_hex' => array(
				'type' => 'color',
				'label' => __('Navigation color', 'dapper-pro'),
				'default' => '#FFFFFF',
			),

			'nav_style' => array(
				'type' => 'select',
				'label' => __('Navigation style', 'dapper-pro'),
				'default' => 'thin',
				'options' => array(
					'ultra-thin' => __('Ultra thin', 'dapper-pro'),
					'thin' => __('Thin', 'dapper-pro'),
					'medium' => __('Medium', 'dapper-pro'),
					'thick' => __('Thick', 'dapper-pro'),
					'ultra-thin-rounded' => __('Rounded ultra thin', 'dapper-pro'),
					'thin-rounded' => __('Rounded thin', 'dapper-pro'),
					'medium-rounded' => __('Rounded medium', 'dapper-pro'),
					'thick-rounded' => __('Rounded thick', 'dapper-pro'),
				)
			),

			'nav_size' => array(
				'type' => 'number',
				'label' => __('Navigation size', 'dapper-pro'),
				'default' => '25',
			),

			'swipe' => array(
				'type' => 'checkbox',
				'label' => __( 'Swipe Control', 'dapper-pro' ),
				'description' => __( 'Allow users to swipe through frames on mobile devices.', 'dapper-pro' ),
				'default' => true,
			)
		);
	}

	function video_form_fields(){
		return array(
			'file' => array(
				'type' => 'media',
				'library' => 'video',
				'label' => __('Video file', 'dapper-pro'),
			),

			'url' => array(
				'type' => 'text',
				'sanitize' => 'url',
				'label' => __('Video URL', 'dapper-pro'),
				'optional' => 'true',
				'description' => __('An external URL of the video. Overrides video file.', 'dapper-pro')
			),

			'format' => array(
				'type' => 'select',
				'label' => __('Video format', 'dapper-pro'),
				'options' => array(
					'video/mp4' => 'MP4',
					'video/webm' => 'WebM',
					'video/ogg' => 'Ogg',
				),
			),

			'height' => array(
				'type' => 'number',
				'label' => __( 'Maximum height', 'dapper-pro' )
			),

		);
	}

	function slider_settings( $controls ){
		return array(
			'pagination' => true,
			'speed' => $controls['speed'],
			'timeout' => $controls['timeout'],
			'swipe' => $controls['swipe'],
		);
	}

	function render_template( $controls, $frames ){
		$this->render_template_part('before_slider', $controls, $frames);
		$this->render_template_part('before_slides', $controls, $frames);

		foreach( $frames as $i => $frame ) {
			$this->render_frame( $i, $frame );
		}

		$this->render_template_part('after_slides', $controls, $frames);
		$this->render_template_part('navigation', $controls, $frames);
		$this->render_template_part('after_slider', $controls, $frames);
	}

	function render_template_part( $part, $controls, $frames ) {
		switch( $part ) {
			case 'before_slider':
				?><div class="dapper-sow-slider-base <?php if( wp_is_mobile() ) echo 'dapper-sow-slider-is-mobile' ?>" style="display: none"><?php
				break;
			case 'before_slides':
				$settings = $this->slider_settings( $controls );
				?><ul class="dapper-sow-slider-images" data-settings="<?php echo esc_attr( json_encode($settings) ) ?>"><?php
				break;
			case 'after_slides':
				?></ul><?php
				break;
			case 'navigation':
				?>
				<ol class="dapper-sow-slider-pagination">
					<?php foreach($frames as $i => $frame) : ?>
						<li><a href="#" data-goto="<?php echo $i ?>"><?php echo $i+1 ?></a></li>
					<?php endforeach; ?>
				</ol>

				<div class="dapper-sow-slide-nav dapper-sow-slide-nav-next">
					<a href="#" data-goto="next" data-action="next">
						<em class="dapper-sow-sld-icon-<?php echo sanitize_html_class( $controls['nav_style'] ) ?>-right"></em>
					</a>
				</div>

				<div class="dapper-sow-slide-nav dapper-sow-slide-nav-prev">
					<a href="#" data-goto="previous" data-action="prev">
						<em class="dapper-sow-sld-icon-<?php echo sanitize_html_class( $controls['nav_style'] ) ?>-left"></em>
					</a>
				</div>
				<?php
				break;
			case 'after_slider':
				?></div><?php
				break;
		}
	}

	/**
	 * Get the frame background information from the frame. This can be overwritten by child classes.
	 *
	 * @param $frame
	 *
	 * @return array
	 */
	function get_frame_background( $i, $frame ) {
		return array( );
	}

	/**
	 * This is mainly for rendering the frame wrapper
	 *
	 * @param $i
	 * @param $frame
	 */
	function render_frame( $i, $frame ){
		$background = wp_parse_args( $this->get_frame_background( $i, $frame ), array(
			'color' => false,
			'image' => false,
			'image-width' => 0,
			'image-height' => 0,
			'opacity' => 1,
			'url' => false,
			'new_window' => false,
			'image-sizing' => 'cover',              // options for image sizing are cover and contain
			'videos' => false,
			'videos-sizing' => 'background',        // options for video sizing are background or full
		) );

		$wrapper_attributes = array(
			'class' => array( 'dapper-sow-slider-image' ),
			'style' => array(),
		);

		if( !empty($background['color']) ) {
			$wrapper_attributes['style'][] = 'background-color: ' . esc_attr($background['color']);
		}

		if( $background['opacity'] >= 1 ) {
			if( !empty($background['image']) ) {
				$wrapper_attributes['style'][] = 'background-image: url(' . esc_url($background['image']) . ')';
			}
		}

		if( ! empty( $background['url'] ) ) {
			$wrapper_attributes['style'][] = 'cursor: pointer;';
		}

		if( !empty($background['image']) && !empty($background['image-sizing']) ) {
			$wrapper_attributes['class'][] = ' ' . 'dapper-sow-slider-image-' . $background['image-sizing'];
		}
		if( !empty( $background['url'] ) ) {
			$wrapper_attributes['data-url'] = json_encode( array(
				'url' => sow_esc_url($background['url']),
				'new_window' => !empty( $background['new_window'] )
			) );
		}
		$wrapper_attributes = apply_filters( 'distinctivethemes_widgets_slider_wrapper_attributes', $wrapper_attributes, $frame, $background );

		$wrapper_attributes['class'] = implode( ' ', $wrapper_attributes['class'] );
		$wrapper_attributes['style'] = implode( ';', $wrapper_attributes['style'] );

		?>
		<li <?php foreach( $wrapper_attributes as $attr => $val ) echo $attr . '="' . esc_attr( $val ) . '" '; ?>>
			<?php
			$this->render_frame_contents( $i, $frame );
			if( !empty( $background['videos'] ) ) {
				$this->video_code( $background['videos'], array('dapper-sow-' . $background['video-sizing'] . '-element') );
			}

			if( $background['opacity'] < 1 && !empty($background['image']) ) {
				$overlay_attributes = array(
					'class' => array( 'dapper-sow-slider-image-overlay', 'dapper-sow-slider-image-' . $background['image-sizing'] ),
					'style' => array(
						'background-image: url(' . $background['image'] . ')',
						'opacity: ' . floatval( $background['opacity'] ),
					)
				);
				$overlay_attributes = apply_filters( 'distinctivethemes_widgets_slider_overlay_attributes', $overlay_attributes, $frame, $background );

				$overlay_attributes['class'] = implode( ' ', $overlay_attributes['class'] );
				$overlay_attributes['style'] = implode( ';', $overlay_attributes['style'] );

				?><div <?php foreach( $overlay_attributes as $attr => $val ) echo $attr . '="' . esc_attr( $val ) . '" '; ?> ></div><?php
			}

			?>
		</li>
		<?php

	}

	/**
	 * Render the actual content of the frame.
	 *
	 * @param $i
	 * @param $frame
	 */
	abstract function render_frame_contents( $i, $frame );

	/**
	 * Render the background videos
	 *
	 * @param $videos
	 * @param array $classes
	 */
	function video_code( $videos, $classes = array() ){
		if(empty($videos)) return;
		$video_element = '<video class="' . esc_attr( implode(',', $classes) ) . '" autoplay loop muted>';

		foreach($videos as $video) {
			if( empty( $video['file'] ) && empty ( $video['url'] ) ) continue;

			if( empty( $video['url'] ) ) {
				$video_file = wp_get_attachment_url($video['file']);
				$video_element .= '<source src="' . sow_esc_url( $video_file ) . '" type="' . esc_attr( $video['format'] ) . '">';
			}
			else {
				$args = '';
				if ( ! empty( $video['height'] ) ) {
					$args['height'] = $video['height'];
				}

				echo wp_oembed_get( $video['url'], $args );
			}
		}
		if ( strpos( $video_element, 'source' ) !== false ) {
			$video_element .= '</video>';
			echo $video_element;
		}
	}

}
