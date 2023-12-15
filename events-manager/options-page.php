<?php
/**
 * Options page for Events Manager with ACF.
 *
 * Source: https://www.advancedcustomfields.com/resources/options-page/#advanced-usage
 */

if ( function_exists( 'acf_add_options_page' ) ) {
	acf_add_options_sub_page(
		[
			'page_title'  => 'Events Manager Options',
			'menu_title'  => 'Events Manager Options',
			'parent_slug' => 'options-general.php',
		]
	);
}