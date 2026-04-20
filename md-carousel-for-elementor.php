<?php
/**
 * Plugin Name: MD Nested Carousel for Elementor
 * Description: Standalone Nested Carousel widget for Elementor Free. Zero dependency on Elementor Pro.
 * Version:     1.0.0
 * Author:      Mazhenov.kz
 * Author URI:  https://mazhenov.kz/
 * Text Domain: md-nested-carousel
 * Requires Plugins: elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MD_NESTED_CAROUSEL_VERSION', '1.0.0' );
define( 'MD_NESTED_CAROUSEL_FILE', __FILE__ );
define( 'MD_NESTED_CAROUSEL_PATH', plugin_dir_path( __FILE__ ) );
define( 'MD_NESTED_CAROUSEL_URL', plugin_dir_url( __FILE__ ) );

final class MD_Nested_Carousel_Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	public function init(): void {
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'elementor/loaded', [ $this, 'on_elementor_loaded' ] );
			return;
		}
		$this->register_hooks();
	}

	public function on_elementor_loaded(): void {
		$this->register_hooks();
	}

	private function register_hooks(): void {
		add_action( 'elementor/widgets/register',                [ $this, 'register_widgets' ] );
		add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_scripts' ] );
		add_action( 'elementor/frontend/after_register_styles',  [ $this, 'register_styles' ] );
		add_action( 'elementor/editor/before_enqueue_scripts',   [ $this, 'enqueue_editor_assets' ] );
	}

	public function register_widgets( \Elementor\Widgets_Manager $manager ): void {
		if ( ! class_exists( '\Elementor\Modules\NestedElements\Base\Widget_Nested_Base' ) ) {
			return;
		}
		require_once MD_NESTED_CAROUSEL_PATH . 'widgets/carousel-widget.php';
		$manager->register( new \MD_Nested_Carousel\Widget() );
	}

	public function register_scripts(): void {
		// Use filemtime as version so browsers always get fresh JS after edits.
		$js_ver  = filemtime( MD_NESTED_CAROUSEL_PATH . 'assets/js/frontend.js' ) ?: MD_NESTED_CAROUSEL_VERSION;
		$ed_ver  = filemtime( MD_NESTED_CAROUSEL_PATH . 'assets/js/editor.js' )   ?: MD_NESTED_CAROUSEL_VERSION;

		wp_register_script(
			'md-nested-carousel',
			MD_NESTED_CAROUSEL_URL . 'assets/js/frontend.js',
			[ 'elementor-frontend', 'swiper' ],
			$js_ver,
			true
		);

		wp_register_script(
			'md-nested-carousel-editor',
			MD_NESTED_CAROUSEL_URL . 'assets/js/editor.js',
			[ 'elementor-common', 'elementor-editor' ],
			$ed_ver,
			true
		);
	}

	public function register_styles(): void {
		$css_ver = filemtime( MD_NESTED_CAROUSEL_PATH . 'assets/css/carousel.css' ) ?: MD_NESTED_CAROUSEL_VERSION;

		wp_register_style(
			'md-nested-carousel',
			MD_NESTED_CAROUSEL_URL . 'assets/css/carousel.css',
			[ 'e-swiper' ],
			$css_ver
		);
	}

	public function enqueue_editor_assets(): void {
		// Register + enqueue directly here. `register_scripts()` is hooked to
		// `elementor/frontend/after_register_scripts`, which does NOT fire in the
		// editor — so the handle wouldn't exist yet at this point.
		$ed_ver = filemtime( MD_NESTED_CAROUSEL_PATH . 'assets/js/editor.js' ) ?: MD_NESTED_CAROUSEL_VERSION;
		wp_enqueue_script(
			'md-nested-carousel-editor',
			MD_NESTED_CAROUSEL_URL . 'assets/js/editor.js',
			[ 'elementor-common', 'elementor-editor' ],
			$ed_ver,
			true
		);

		// CSS must be loaded in the editor too (not just frontend).
		$css_ver = filemtime( MD_NESTED_CAROUSEL_PATH . 'assets/css/carousel.css' ) ?: MD_NESTED_CAROUSEL_VERSION;
		wp_enqueue_style(
			'md-nested-carousel',
			MD_NESTED_CAROUSEL_URL . 'assets/css/carousel.css',
			[ 'e-swiper' ],
			$css_ver
		);
	}
}

MD_Nested_Carousel_Plugin::instance();
