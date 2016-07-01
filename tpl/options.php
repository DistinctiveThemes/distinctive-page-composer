<?php $settings = distinctivethemes_panels_setting(); ?>

<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h2><?php _e('Distinctive Themes Widget Page Builder - based on DistinctiveThemess', 'distinctivethemes-panels') ?></h2>

	<form action="<?php echo admin_url( 'options-general.php?page=distinctivethemes_panels' ) ?>" method="POST">

		<pre><?php //var_dump($settings) ?></pre>

		<h3><?php _e('General', 'distinctivethemes-panels') ?></h3>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><strong><?php _e('Post Types', 'distinctivethemes-panels') ?></strong></th>
					<td>
						<?php distinctivethemes_panels_options_field_post_types($settings['post-types']) ?>
					</td>
				</tr>

				<?php
				distinctivethemes_panels_options_field(
					'copy-content',
					$settings['copy-content'],
					__('Copy Content', 'distinctivethemes-panels'),
					__('Copy content from Page Builder into the standard content editor.', 'distinctivethemes-panels')
				);

				distinctivethemes_panels_options_field(
					'animations',
					$settings['animations'],
					__('Animations', 'distinctivethemes-panels'),
					__('Disable animations for improved performance.', 'distinctivethemes-panels')
				);

				distinctivethemes_panels_options_field(
					'bundled-widgets',
					$settings['bundled-widgets'],
					__('Bundled Widgets', 'distinctivethemes-panels'),
					__('Include the bundled widgets.', 'distinctivethemes-panels')
				);

				?>

			</tbody>
		</table>

		<h3><?php _e('Display', 'distinctivethemes-panels') ?></h3>
		<table class="form-table">
			<tbody>

				<?php

				distinctivethemes_panels_options_field(
					'responsive',
					$settings['responsive'],
					__('Responsive Layout', 'distinctivethemes-panels'),
					__('Should the layout collapse for mobile devices.', 'distinctivethemes-panels')
				);

				distinctivethemes_panels_options_field(
					'mobile-width',
					$settings['mobile-width'],
					__('Mobile Width', 'distinctivethemes-panels')
				);

				distinctivethemes_panels_options_field(
					'margin-bottom',
					$settings['margin-bottom'],
					__('Row Bottom Margin', 'distinctivethemes-panels')
				);

				distinctivethemes_panels_options_field(
					'margin-sides',
					$settings['margin-sides'],
					__('Cell Side Margins', 'distinctivethemes-panels')
				);

				distinctivethemes_panels_options_field(
					'inline-css',
					$settings['inline-css'],
					__('Inline CSS', 'distinctivethemes-panels')
				);

				?>

			</tbody>
		</table>


		<?php wp_nonce_field('save_panels_settings'); ?>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php esc_attr_e('Save Settings', 'distinctivethemes-panels') ?>"/>
		</p>

	</form>
</div>