<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Kntnt Fluentform Register Scripts Earlier
 * Plugin URI:        https://www.kntnt.com/
 * Description:       Fixes Fluentform too late registration of scripts.
 * Version:           1.0.0
 * Author:            Thomas Barregren
 * Author URI:        https://www.kntnt.com/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 */


namespace Kntnt\Fluentform_Register_Scripts_Earlier;

defined( 'ABSPATH' ) || die;

/*
 * Remember that a style or script must be registered before it's enqueued.
 * Registration can be done at any time before enqueuing. Enqueuing can be done
 * any time after the init action and before the wp_footer action, provided
 * that the enqueued scripts goes into the footer. Recommended, and necessarily
 * for scripts to go in the header, is to enqueue at the wp_enqueue_scripts
 * action.
 *
 * Fluentform doesn't enqueue styles and scripts at the wp_enqueue_scripts
 * action but wait till the components requiring them are rendered. The
 * enqueued styles and scripts are then registered. As said above, that can be
 * done at any time before enqueuing them. Fluentform does it at
 * wp_enqueue_scripts action, although it can be done earlier, e.g. at init
 * action.
 *
 * When Fluentform shortcodes are evaluated before the wp_enqueue_scripts
 * action, that creates issues since that enqueued styles and scripts that
 * are not yet registered. This happens for instance when Fluentform
 * shortcodes exists in content that is wrapped by a shortcode provided by
 * the Custom Content Shortcode plugin.
 *
 * The solution is to bring the registration forward in time. In my opinion,
 * Fluentform should have registered the styles and scripts at the init hook
 * (or possible as early as at the plugins_loaded action).
 *
 * Following code is a workaround. Here's how the solution works:
 *
 * The code runs early at the wp_loaded action. Likely, no plugin will
 * evaluate shortcodes before this hook. At this point, Fluentform has
 * registered callback functions that will run at the wp_enqueue_scripts
 * action. These callbacks do the registration (but not the enqueuing, which is
 * deferred as explained above). The code finds Fluentforms callbacks, execute
 * them directly, and remove them from the list of callbacks to be called at
 * the wp_enqueue_scripts action.
 */
add_action( 'wp_loaded', function () {
	global $wp_filter;
	foreach ( $wp_filter['wp_enqueue_scripts']->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $key => $callback ) {
			if ( is_array( $callback['function'] ) ) {
				$class = is_object( $callback['function'][0] ) ? get_class( $callback['function'][0] ) : $callback['function'][0];
				$method = $callback['function'][1];
				if ( substr( $class, 0, 10 ) == 'FluentForm' ) {
					call_user_func( $callback['function'] );
					remove_filter( 'wp_enqueue_scripts', $key, $priority );
				}
			}
		}
	}
}, 0 );
