<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!DOCTYPE html><html <?php language_attributes( 'html' ); ?><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta charset="<?php esc_attr( get_bloginfo( 'charset' ) ); ?>">
<?php 
do_action( 'wp_enqueue_scripts' );
global $wp_scripts, $wp_styles;
$wp_styles->queue = array_intersect( array( 'twitter-wall-css' ), $wp_styles->queue );
do_action( 'wp_print_styles' );
$wp_scripts->queue = array_intersect( array( 'isotope', 'twitter-wall', 'jquery', 'imagesloaded' ), $wp_scripts->queue );
do_action( 'wp_print_head_scripts' );
printf( '<style type="text/css" media="all">
    .twitterwall-full,
	body{background:%1$s !important;}
	.twitterwall-full .twitter-wall-2 li{border-color:%1$s !important;}
	.twitterwall-full .avatar{border-color:%2$s !important;}
	.twitterwall-full .rp-button,
	.twitterwall-full .rt-button{color:%3$s !important;}
	.twitterwall-full .text > a{color:%1$s !important;}
	</style>',
	esc_attr( get_option( 'twitterwall_bgcolor', '#F52F57' ) ),
	esc_attr( get_option( 'twitterwall_avatar_bdcolor', '#ffffff' ) ),
	esc_attr( \Twitterwall\adjustBrightness( get_option( 'twitterwall_bgcolor', '#F52F57' ), -100 ) )
); ?>
</head><body class="twitterwall-full">
<?php
wp_enqueue_script( 'isotope' );
echo do_shortcode( '[twitter-wall]' );
$wp_styles->queue = array_intersect( array( 'twitter-wall-css' ), $wp_styles->queue );
$wp_scripts->queue = array_intersect( array( 'isotope', 'twitter-wall', 'jquery', 'imagesloaded' ), $wp_scripts->queue );
do_action( 'wp_print_footer_scripts' );
?>
</body></html>
