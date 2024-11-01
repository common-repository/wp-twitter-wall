<?php

namespace Twitterwall;

/**
 * Plugin Name: WP Twitter Wall
 * Description: Display a live Twitter wall at your event, using your WordPress website!
 * Version: 1.3.1
 * Stable tag: 1.3.1
 * Contributors: thierrypigot, willybahuaud
 * Author: <a href="http://www.thierry-pigot.fr">Thierry Pigot</a>, <a href="https://wabeo.fr/">Willy Bahuaud</a>
 * Text Domain: wp-twitter-wall
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

define( 'TWITTERWALL_VERSION', '1.3.1' );


/**
 * Load languages
 */
add_action( 'plugins_loaded',		__NAMESPACE__ . '\load_languages' );
function load_languages() {
	load_plugin_textdomain( 'wp-twitter-wall', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}


/**
 * Register scripts
 */
add_action( 'wp_enqueue_scripts',		__NAMESPACE__ . '\register_scripts' );
function register_scripts() {
	wp_register_script( 'imagesloaded',		plugins_url( '/js/imagesloaded.pkgd.min.js', __FILE__ ),					array( 'jquery' ),					1, true );
	wp_register_script( 'isotope',			plugins_url( '/js/isotope.pkgd.min.js', __FILE__ ),							array( 'jquery', 'imagesloaded' ),	1, true );
	wp_register_script( 'twitter-wall',		plugins_url( '/js/twitter-wall.js', __FILE__ ),								array( 'jquery' ),					TWITTERWALL_VERSION, true );

	wp_register_style( 'twitter-wall-css',	plugins_url( '/css/twitter-wall.css', __FILE__ ),							false,								TWITTERWALL_VERSION, 'all' );
	wp_enqueue_style( 'twitter-wall-css' );
}


/**
 * Register admin scripts
 */
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_enqueue_scripts' );
function admin_enqueue_scripts() {
	wp_register_script( 'twitterwall-color-picker', plugins_url( 'js/admin-twitterwall.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
}


/**
 * Get tweets
 */
add_shortcode( 'twitter-wall',		__NAMESPACE__ . '\get_tweets' );
function get_tweets( $since = false ) {

	require_once( 'classes/TokenToMe.class.php' );

	$get_spam_account = get_spam_account();

	$out = array();

	$consumer_key		= get_option( 'twitterwall_consumer_key' );
	$consumer_secret	= get_option( 'twitterwall_consumer_secret' );
	$count				= get_option( 'twitterwall_count', 25 );

	$query = array(
			'q'				=> urlencode( get_option( 'twitterwall_query', 'wordpress' ) ),
			'result_type'	=> 'recent',
			'count'			=> $count,
			'since_id'		=> $since,
		);

	if ( $lang = get_option( 'twitterwall_lang' ) ) {
		$query['lang'] = esc_attr( $lang );
	}

	$query = apply_filters( 'twitterwall_query_args', $query );

	$init = new \TokenToMe\WP_Twitter_Oauth(
		$consumer_key,
		$consumer_secret,
		'search/tweets',
		$query,
		60
	);
	$infos = $init->get_infos();

	if ( isset( $infos->statuses[0] ) ) {
		if ( ! $since ) {
			$out[] = '<ul class="twitter-wall-2" id="twitter-wall-2">';
			wp_enqueue_script( 'twitter-wall' );
			wp_localize_script( 'twitter-wall', 'ajaxUrl', admin_url( 'admin-ajax.php' ) );
			if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
				wp_localize_script( 'twitter-wall', 'TWActions', array(
					'nonce'   => wp_create_nonce( 'report-spam-user-' . $_SERVER['REMOTE_ADDR'] ),
					'confirm' => __( 'Report %s as spam?', 'wp-twitter-wall' ),
				) );
			}
		}
		foreach ( $infos->statuses as $status ) {

			$text = $status->text;

			$nb_hastag = substr_count( $text, '#' );

			if ( ! in_array( $status->user->name, $get_spam_account ) && $nb_hastag < 3 ) {

				$rt = ( preg_match( '/^RT\s@(\S*):/', $text, $matches ) ) ? true : false;
				if ( $rt ) {
					$text = $status->retweeted_status->text;
				}

				$time = $status->created_at;
				$timestamp = strtotime( $time );

				$time = sprintf( __( '%s ago', 'wp-twitter-wall' ), human_time_diff( $timestamp ) );

				$entities = array();
				if ( isset( $status->entities->media[0] ) ) {
					foreach ( $status->entities->media as $entity ) {
						if ( 'photo' == $entity->type ) {
							$entities[] = '<div class="image-link"><a href="' . esc_url( $entity->media_url ) . '"><img src="' . esc_attr( $entity->media_url ) . ':%s" class="twitter-picture"/></a></div>';
						}
					}
				}

				if ( count( $entities ) > 1 ) {
					$images = array();
					foreach ( $entities as $entity ) {
						$images[] = wp_sprintf( $entity, 'thumb' );
					}
					$entities = implode( PHP_EOL, $images );
				} elseif ( 1 == count( $entities ) ) {
					$entities = sprintf( $entities[0], 'medium' );
				} else {
					$entities = '';
				}

				$out[] = '<li' . ($rt ? ' class="rt" data-rt="' . esc_attr( $matches[1] ) . '"' : '') . ' data-id="' . esc_attr( $status->id_str ) . '" data-time="' . esc_attr( $timestamp ) . '">';
				$out[] = '<div class="buttons">';
				$out[] = '<button class="rt-button">' . ( $rt ? $matches[1] : $status->user->screen_name ) . '</button>';
				$out[] = '<button class="rp-button">' . $status->user->screen_name . '</button>';
				$out[] = '</div>';
				$out[] = '<div class="author">';

					$out[] = sprintf( '<span data-user="%1$s"><img src="%2$s" class="avatar"/></span>',
						esc_attr( $status->user->name ),
						esc_attr( $status->user->profile_image_url )
					);

					$out[] = '<span class="name">' . esc_html( $status->user->name ) . '</span>';
					$out[] = '<span class="date"> – ' . esc_html( $time ) . '</span>';
				$out[] = '</div>';
              	$out[] = '<div class="text">'. make_clickable( $text ) . $entities . '</div>';
				$out[] = '</li>';
			}
		}
		if ( ! $since ) {
			$out[] = '</ul>';
		}
	}
	return implode( PHP_EOL, $out );
}


/**
 * Get tweets since a defined time (ajax call)
 */
add_action( 'wp_ajax_twitterwall.get-tweets',			__NAMESPACE__ . '\get_tweets_since' );
add_action( 'wp_ajax_nopriv_twitterxal.get-tweets',		__NAMESPACE__ . '\get_tweets_since' );
function get_tweets_since() {
	if( isset( $_POST['since_id'] ) ) {
		$out = array(
			'tweets' => get_tweets( esc_html( $_POST['since_id'] ) ),
			);
		if( isset( $_POST['dates'] ) ) {
			$out['times'] = convert_dates( sanitize_text_field( $_POST['dates'] ) );
		}
		wp_send_json_success( $out );
	} else {
		wp_send_json_error();
	}
}


/**
 * Convert date to human date
 *
 * @param  [string] $dates date returned by twitter api
 * @return [string]        date in human time diff
 */
function convert_dates( $dates ) {
	$out = array();
	foreach ( $dates as $id => $time ) {
		$out[ $id ] = sprintf( __( 'There are %s', 'wp-twitter-wall' ), human_time_diff( $time ) );
	}
	return $out;
}


/**
 * Get spam account
 *
 * @return [array] array of Twitter Account reported as spam
 */
function get_spam_account() {
	$spam = array();
	$args = array(
		'post_type'        => 'twitterwall-spam',
		'post_status'      => 'publish',
		'posts_per_page'   => 9999,
		'suppress_filters' => false,
		'no_found_rows'    => 1,
	);

	$posts = get_posts( $args );
	foreach ( $posts as $post ) {
		$spam[] = $post->post_title;
	}

	return $spam;
}


/**
 * Register Span CPT
 */
add_action( 'init', __NAMESPACE__ . '\twitter_spam', 0 );
function twitter_spam() {
	$labels = array(
		'name'                  => _x( 'spam', 'Post Type General Name', 'wp-twitter-wall' ),
		'singular_name'         => _x( 'spams', 'Post Type Singular Name', 'wp-twitter-wall' ),
		'menu_name'             => __( 'WP Twitter Wall', 'wp-twitter-wall' ),
		'name_admin_bar'        => __( 'Twitter spams', 'wp-twitter-wall' ),
		'archives'              => __( 'Spam Archives', 'wp-twitter-wall' ),
		'parent_item_colon'     => __( 'Parent Item:', 'wp-twitter-wall' ),
		'all_items'             => __( 'All Spams', 'wp-twitter-wall' ),
		'add_new_item'          => __( 'Add New Spam', 'wp-twitter-wall' ),
		'add_new'               => __( 'Add Spam', 'wp-twitter-wall' ),
		'new_item'              => __( 'New Spam', 'wp-twitter-wall' ),
		'edit_item'             => __( 'Edit Item', 'wp-twitter-wall' ),
		'update_item'           => __( 'Update Item', 'wp-twitter-wall' ),
		'view_item'             => __( 'View Item', 'wp-twitter-wall' ),
		'search_items'          => __( 'Search Item', 'wp-twitter-wall' ),
		'not_found'             => __( 'Not found', 'wp-twitter-wall' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'wp-twitter-wall' ),
		'featured_image'        => __( 'Featured Image', 'wp-twitter-wall' ),
		'set_featured_image'    => __( 'Set featured image', 'wp-twitter-wall' ),
		'remove_featured_image' => __( 'Remove featured image', 'wp-twitter-wall' ),
		'use_featured_image'    => __( 'Use as featured image', 'wp-twitter-wall' ),
		'insert_into_item'      => __( 'Insert into item', 'wp-twitter-wall' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'wp-twitter-wall' ),
		'items_list'            => __( 'Items list', 'wp-twitter-wall' ),
		'items_list_navigation' => __( 'Items list navigation', 'wp-twitter-wall' ),
		'filter_items_list'     => __( 'Filter items list', 'wp-twitter-wall' ),
	);
	$args = array(
		'label'                 => __( 'spams', 'wp-twitter-wall' ),
		'description'           => __( 'Twitter spams', 'wp-twitter-wall' ),
		'labels'                => $labels,
		'supports'              => array( 'title' ),
		'hierarchical'          => false,
		'public'                => false,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 102,
		'menu_icon'             => 'dashicons-twitter',
		'show_in_admin_bar'     => false,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'rewrite'               => false,
		'capability_type'       => 'page',
	);
	register_post_type( 'twitterwall-spam', $args );
}


/**
 * Report as spam in ajax
 */
add_action( 'wp_ajax_twitterwall.report_as_spam', __NAMESPACE__ . '\report_as_spam' );
function report_as_spam() {
	if ( wp_verify_nonce( $_POST['nonce'], 'report-spam-user-' . $_SERVER['REMOTE_ADDR'] )
	  && isset( $_POST['toSpam'] )
	  && $spam = trim( sanitize_text_field( $_POST['toSpam'] ) ) ) {
		if ( ! get_page_by_title( $spam, 'OBJECT', 'spam' ) ) {

			$new_spam = array(
				'post_type'     => 'twitterwall-spam',
				'post_title'    => esc_html( $spam ),
				'post_status'   => 'publish',
			);

			$result = wp_insert_post( $new_spam );
		}
		wp_send_json_success( array( sprintf( __( '%s reported as spam', 'wp-twitter-wall' ), $spam ), $result ) );
	}
	wp_send_json_error();
}


/**
 * Title placeholder into spam CPT
 */
add_filter( 'enter_title_here', __NAMESPACE__ . '\change_title_text' );
function change_title_text( $title ) {
	$screen = get_current_screen();

	if ( 'twitterwall-spam' == $screen->post_type ) {
		$title = __( 'Twitter username to add to spams', 'wp-twitter-wall' );
	}

	return $title;
}


/**
 * Add setting page
 */
add_action( 'admin_menu', __NAMESPACE__ . '\add_setting_page' );
function add_setting_page() {
	add_submenu_page( 'edit.php?post_type=twitterwall-spam', __( 'Settings', 'wp-twitter-wall' ), __( 'Settings', 'wp-twitter-wall' ), 'manage_options', 'twitterwall', __NAMESPACE__ . '\setting_page' );
}


/**
 * Register Twitterwall settings
 */
add_action( 'admin_init', __NAMESPACE__ . '\register_settings' );
function register_settings() {
	register_setting( 'twitterwall', 'twitterwall_consumer_key' );
	register_setting( 'twitterwall', 'twitterwall_consumer_secret' );
	register_setting( 'twitterwall', 'twitterwall_query' );
	register_setting( 'twitterwall', 'twitterwall_lang' );
	register_setting( 'twitterwall', 'twitterwall_count' );
	register_setting( 'twitterwall', 'twitterwall_avatar_bdcolor' );
	register_setting( 'twitterwall', 'twitterwall_bgcolor' );
	register_setting( 'twitterwall', 'twitterwall_url' );
}


/**
 * Do setting page
 */
function setting_page() {
	echo '<div class="wrap">';
		echo '<h2>' . __( 'WP Twitter Wall settings', 'wp-twitter-wall' ) . '</h2>';

		add_settings_section( 'api_setting_section',
			__( 'Twitter API settings', 'wp-twitter-wall' ),
			__NAMESPACE__ . '\setting_section_desc',
		'twitterwall' );

		add_settings_field( 'twitterwall_consumer_key',
			__( 'Consumer key', 'wp-twitter-wall' ),
			__NAMESPACE__ . '\setting_callback_function',
			'twitterwall',
			'api_setting_section',
			array(
			    'type' => 'text',
				'name' => 'twitterwall_consumer_key',
			)
		);

		add_settings_field( 'twitterwall_consumer_secret',
			__( 'Consumer secret', 'wp-twitter-wall' ),
			__NAMESPACE__ . '\setting_callback_function',
			'twitterwall',
			'api_setting_section',
			array(
			    'type' => 'text',
				'name' => 'twitterwall_consumer_secret',
			)
		);

		add_settings_section( 'search_setting_section',
			__( 'Twitterwall settings', 'wp-twitter-wall' ),
			'__return_false',
		'twitterwall' );

		add_settings_field( 'twitterwall_url',
			__( 'Relative URL of twitterwall', 'wp-twitter-wall' ),
			__NAMESPACE__ . '\setting_callback_function',
			'twitterwall',
			'search_setting_section',
			array(
				'type'        => 'text',
				'placeholder' => 'twitterwall',
				'name'        => 'twitterwall_url',
				'default'     => 'twitterwall',
			)
		);

		add_settings_field( 'twitterwall_query',
			__( 'Search query', 'wp-twitter-wall' ),
			__NAMESPACE__ . '\setting_callback_function',
			'twitterwall',
			'search_setting_section',
			array(
				'type'        => 'text',
				'placeholder' => 'wordpress OR wp',
				'name'        => 'twitterwall_query',
			)
		);

		add_settings_field( 'twitterwall_lang',
			__( 'Search query language', 'wp-twitter-wall' ),
			__NAMESPACE__ . '\setting_callback_function',
			'twitterwall',
			'search_setting_section',
			array(
				'type'        => 'text',
				'placeholder' => 'en',
				'name'        => 'twitterwall_lang',
			)
		);

		add_settings_field( 'twitterwall_count',
			__( 'Number of tweets to display', 'wp-twitter-wall' ),
			__NAMESPACE__ . '\setting_callback_function',
			'twitterwall',
			'search_setting_section',
			array(
				'type'        => 'number',
				'placeholder' => '25',
				'name'        => 'twitterwall_count',
				'default'     => '25',
			)
		);

		add_settings_field( 'twitterwall_bgcolor',
			__( 'Twitterwall background color', 'wp-twitter-wall' ),
			__NAMESPACE__ . '\setting_callback_function',
			'twitterwall',
			'search_setting_section',
			array(
				'type'        => 'colorfield',
				'name'        => 'twitterwall_bgcolor',
				'default'     => '#F52F57',
			)
		);

		add_settings_field( 'twitterwall_avatar_bdcolor',
			__( 'Avatar border color', 'wp-twitter-wall' ),
			__NAMESPACE__ . '\setting_callback_function',
			'twitterwall',
			'search_setting_section',
			array(
				'type'        => 'colorfield',
				'name'        => 'twitterwall_avatar_bdcolor',
				'default'     => '#FFFFFF',
			)
		);

		echo '<form method="post" action="options.php">';
			settings_fields( 'twitterwall' );
			do_settings_sections( 'twitterwall' );

			echo '<p class="submit">';
				submit_button( '', 'primary large', 'submit', false );
			echo '</p>';
		echo '</form>';

	echo '</div>';
}

/**
 * Setting section description callback
 */
function setting_section_desc() {
	echo __( 'In order to make authorized calls to Twitter’s APIs, your website must first obtain an OAuth access token:', 'wp-twitter-wall' )
		. ' <a href="https://apps.twitter.com/" target="_blank">'
		. __( 'Create a Twitter Apps to get my access token', 'wp-twitter-wall' )
		. '</a>';
}


/**
 * Setting callback function for settings
 *
 * @param  [array] $args settings field args
 */
function setting_callback_function( $args ) {

	extract( $args );
	$value_old = get_option( $name, '' );
	switch ( $type ) {
		case 'url' :
		case 'number' :
		case 'text' :
			printf( '<input class="widefat" type="%4$s" name="%1$s" id="%1$s" value="%2$s"%3$s/>',
			    esc_attr( $name ),
			    ( $value_old ? esc_attr( $value_old ) : esc_attr( $default ) ),
				( isset( $placeholder ) ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' ),
				esc_attr( $type )
			);
			break;
		case 'colorfield':
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'twitterwall-color-picker' );
			printf( '<input class="widefat colorpicker" type="text" name="%1$s" id="%1$s" value="%2$s"/>',
			    esc_attr( $name ),
			    ( $value_old ? esc_attr( $value_old ) : esc_attr( $default ) )
			);
			break;
		default :
			printf( '<select name="%s" id="%s">',
			    esc_attr( $name )
			);
			foreach ( $options as $key => $option ) {
				printf( '<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( $key ),
					selected( $value_old == $key, true, false ),
					esc_html( $option )
				);
			}
			echo '</select>';
	}
}


/**
 * Reorder twitterwall menu
 */
add_filter( 'custom_menu_order', __NAMESPACE__ . '\menu_order', PHP_INT_MAX );
function menu_order( $order ) {
	global $submenu;
	$arr = array(
			$submenu['edit.php?post_type=twitterwall-spam'][11],
			array(
			    __( 'Display Twitterwall', 'wp-twitter-wall' ),
			    'manage_options',
			    home_url( get_option( 'twitterwall_url', 'twitterwall' ) ),
			    ),
			$submenu['edit.php?post_type=twitterwall-spam'][5],
			$submenu['edit.php?post_type=twitterwall-spam'][10],
		);
	$submenu['edit.php?post_type=twitterwall-spam'] = $arr;
	return $order;
}


/**
 * Query notification on save options
 */
add_action( 'updated_option', __NAMESPACE__ . '\on_update_option' );
function on_update_option( $option ) {
	if ( in_array( $option, array( 
			'twitterwall_consumer_key',
			'twitterwall_consumer_secret',
			'twitterwall_query',
			'twitterwall_avatar_bdcolor',
			'twitterwall_bgcolor',
			'twitterwall_url',
			'twitterwall_lang',
			'twitterwall_count' ) ) ) {
		update_option( 'twitterwall_options_updated', 'yes' );
	}
}


/**
 * Notify on save options
 */
add_action( 'admin_notices', __NAMESPACE__ . '\admin_notices' );
function admin_notices() {
	if ( 'yes' == get_option( 'twitterwall_options_updated' ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>'
		. __( 'Twitterwall options saved', 'wp-twitter-wall' )
        . '</p></div>';
        flush_rewrite_rules();
		delete_option( 'twitterwall_options_updated' );
	}
}

/**
 * Twitterwall rewrite rule
 */
add_action( 'init', __NAMESPACE__ . '\rewrite_rule' );
function rewrite_rule() {
	add_rewrite_rule( '^' . get_option( 'twitterwall_url', 'twitterwall' ) . '/?$', 'index.php?displaytwitterwall=1', 'top' );
}


/**
 * Register a twitterwall query var
 */
add_filter( 'query_vars', __NAMESPACE__ . '\query_vars' );
function query_vars( $vars ) {
	$vars[] = 'displaytwitterwall';
	return $vars;
}

/**
 * Display Twitterwall
 */
add_action( 'template_include', __NAMESPACE__ . '\template_twitterwall' );
function template_twitterwall( $template ) {
	if ( 1 == get_query_var( 'displaytwitterwall' ) ) {
		if ( $new_template = locate_template( 'twitter-wall-template.php' ) ) {
			return $new_template;
		} else {
			return plugin_dir_path( __FILE__ ) . 'twitter-wall-template.php';
		}
	}
	return $template;
}


/**
 * Hide admin bar on twitterwall
 */
add_filter( 'show_admin_bar', __NAMESPACE__ . '\show_admin_bar' );
function show_admin_bar( $show ) {
	if ( 1 == get_query_var( 'displaytwitterwall' ) ) {
		return false;
	}
	return $show;
}


/**
 * Flush rewrite rules on activation and desactivation
 */
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
register_activation_hook( __FILE__, __NAMESPACE__ . '\do_flush_rewrites' );
function do_flush_rewrites() {
	rewrite_rule();
	flush_rewrite_rules();
}


/**
 * Adjust an hexacolor brigtness (used on template side)
 *
 * @param  [string] $hex   hexadecimal color
 * @param  [number] $steps brigtness variation
 * @return [sting]        hexadecimal color
 */
function adjustBrightness( $hex, $steps ) {
	$steps = max( -255, min( 255, $steps ) );

	$hex = str_replace( '#', '', $hex );
	if ( 3 == strlen( $hex ) ) {
		$hex = str_repeat( substr( $hex, 0, 1 ), 2 )
			. str_repeat( substr( $hex, 1, 1 ), 2 )
			. str_repeat( substr( $hex, 2, 1 ), 2 );
	}

	$color_parts = str_split( $hex, 2 );
	$return = '#';

	foreach ( $color_parts as $color ) {
		$color   = hexdec( $color );
		$color   = max( 0, min( 255, $color + $steps ) );
		$return .= str_pad( dechex( $color ), 2, '0', STR_PAD_LEFT );
	}

	return $return;
}
