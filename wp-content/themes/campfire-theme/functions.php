<?php
/**
 *	TODO
 * 
 *	Users -> belongsToPodcast
 *	Podcasts -> hasManyUsers, hasManyEpisodes, has archive
 *	Episodes -> belongsToPodcast, has archive 
 *	Posts -> belongsToPodcast?, has archive
 *
 *	User should be able to check what podcast they're associated with
 *	Any episode posted from the user should be associated with their podcast
 *	
 *	http://www.wpbeginner.com/plugins/how-to-limit-authors-to-their-own-posts-in-wordpress-admin/
 * 
 */

if ( ! class_exists( 'Timber' ) ) {
	add_action( 'admin_notices', function() {
		echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php') ) . '</a></p></div>';
	});
	
	add_filter('template_include', function($template) {
		return get_stylesheet_directory() . '/static/no-timber.html';
	});
	
	return;
}

Timber::$dirname = array('templates', 'views');

class StarterSite extends TimberSite {

	function __construct() {
		add_theme_support( 'post-formats' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );

		add_image_size( 'banner_wide', 2000, 440, true );
		add_image_size( 'featured_square', 600, 600, true );

		add_filter( 'timber_context', array( $this, 'add_to_context' ) );
		add_filter( 'get_twig', array( $this, 'add_to_twig' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'init', array( $this, 'register_options' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );

		parent::__construct();
	}

	function register_post_types() {
		//this is where you can register custom post types
	}

	function register_taxonomies() {
		//this is where you can register custom taxonomies
	}

	function register_options() {
		if( function_exists('acf_add_options_page') ) {
			acf_add_options_page(array(
				'page_title' 	=> 'Site General Settings',
				'menu_title'	=> 'Site Settings',
				'menu_slug' 	=> 'theme-general-settings',
				'capability'	=> 'edit_themes',
				'redirect'		=> false
			));
		}
	}

	function add_to_context( $context ) {
		$context['foo'] = 'bar';
		$context['stuff'] = 'I am a value set in your functions.php file';
		$context['notes'] = 'These values are available everytime you call Timber::get_context();';
		$context['menu'] = new TimberMenu();
		$context['site'] = $this;
		return $context;
	}

	function myfoo( $text ) {
		$text .= ' bar!';
		return $text;
	}

	function add_to_twig( $twig ) {
		/* this is where you can add your own functions to twig */
		$twig->addExtension( new Twig_Extension_StringLoader() );
		$twig->addFilter('myfoo', new Twig_SimpleFilter('myfoo', array($this, 'myfoo')));
		return $twig;
	}

	function load_styles() {
		wp_enqueue_style( 'owl-carousel', 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.2/assets/owl.carousel.min.css' );
		wp_enqueue_style( 'owl-carousel-theme', 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.2/assets/owl.theme.default.css' );

		// wp_enqueue_style('mediaelement', 'https://cdnjs.cloudflare.com/ajax/libs/mediaelement/4.2.8/mediaelementplayer.min.css');
		wp_enqueue_style('wp-mediaelement');
	}

	function load_scripts() {
		wp_enqueue_script('juery-infinitescroll', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-infinitescroll/3.0.4/infinite-scroll.pkgd.min.js', array('jquery'), '3.0.4', true);
		// wp_enqueue_script('mediaelement', 'https://cdnjs.cloudflare.com/ajax/libs/mediaelement/4.2.8/mediaelement.min.js', array('jquery'), '4.2.8', true);
		// wp_enqueue_script('mediaelement-and-player', 'https://cdnjs.cloudflare.com/ajax/libs/mediaelement/4.2.8/mediaelement-and-player.js', array('jquery'), '4.2.8', true);
		wp_enqueue_script('wp-mediaelement');
	}
}

new StarterSite();

/**
 * TODO: Hide episode and podcast admin post pages from other 
 * 		 unauthorized editors
 */
require get_template_directory() . '/inc/ImageUpload.php';
require get_template_directory() . '/inc/Podcasts.php';
require get_template_directory() . '/inc/Episodes.php';
require get_template_directory() . '/inc/Hosts.php';
