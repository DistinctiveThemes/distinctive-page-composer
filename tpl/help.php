<p>
	<?php _e('You can use Distinctive Themes Widget Page Builder - based on DistinctiveThemess to create home and sub pages, filled your own widgets.', 'distinctivethemes-panels') ?>
	<?php _e('The page layouts are responsive and fully customizable.', 'distinctivethemes-panels') ?>
</p>
<p>
	<?php
	preg_replace(
		array(
			'/1\{ *(.*?) *\}/',
			'/2\{ *(.*?) *\}/',
			'/3\{ *(.*?) *\}/',
		),
		array(
			'<a href="http://distinctivethemes.com/page-builder/documentation/" target="_blank">$1</a>',
			'<a href="http://distinctivethemes.com/threads/plugin-page-builder/" target="_blank">$1</a>',
			'<a href="http://distinctivethemes.com/#newsletter" target="_blank">$1</a>',
		),
		__('Read the 1{full documentation} on DistinctiveThemes. Ask a question on our 2{support forum} if you need help and sign up to 3{our newsletter} to stay up to date with future developments.', 'distinctivethemes-panels')
	);
	?>
</p>