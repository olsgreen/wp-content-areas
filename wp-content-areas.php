<?php
/*
Plugin Name: WP Content Areas
Plugin URI:  http://wp.builddigital.uk
Description: Allows you to create content fragments which can then be embeded within other posts and pages.
Version:     1.0.0
Author:      builddigital.uk
Author URI:  http://builddigital.uk
Textdomain:  wp-content-areas
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class.
 *
 * @since 1.0.0
 *
 * @package WP_Content_Areas
 * @author  Oliver Green
 */
class WP_Content_Areas
{
    /**
     * Singleton instance reference.
     *
     * @since 1.0.0
     *
     * @var WP_Content_Areas
     */
    protected static $instance = null;

    /**
     * IDs of pages to force Visual Composer 
     * to output custom CSS for.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected static $ids = array();

    /**
     * Returns the singleton instance 
     * of the this class.
     *
     * @since 1.0.0
     *
     * @return WP_Content_Areas
     */
    public static function instance()
    {
        if (null === static::$instance) {
            $instance = new static();
            $instance->boot();
            static::$instance = $instance;
        }
        return static::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    protected function __construct()
    {
        define( 'WPCA_LANG', 'wp-content-areas' );
        define( 'WPCA_PLUGIN', __FILE__ );
        define( 'WPCA_LANG_DIR', dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
    }

    /**
     * Sets up the class by registering the 
     * hooks and calling the post type registration.
     *
     * @since 1.0.0
     */
    protected function boot()
    {
        // Localization
        $this->translate();

        // Register hooks
        add_filter( 'manage_edit-wp_content_area_columns', array( $this, 'columns' ) );
        add_filter( 'manage_wp_content_area_posts_custom_column', array( $this, 'custom_columns' ), 10, 2 );
        add_shortcode( 'content_area', array( $this, 'register_shortcode' ) );
        add_action( 'wp_footer', array( $this, 'add_vc_custom_css'), 100);

        // Register the post type
        $this->register_post_type();
    }

    /**
     * Sets the plugin text domain for translation
     *
     * @since 1.0.0
     */
    public function translate()
    {
        load_plugin_textdomain( WPCA_LANG , false, WPCA_LANG_DIR );
    }

    /**
     * Registers the custom post type for the content areas
     *
     * @since 1.0.0
     */
    protected function register_post_type()
    {
        register_post_type( 'wp_content_area',
            array(
                'labels' => array(
                    'name' => __( 'Content Areas', WPCA_LANG ),
                    'singular_name' => __( 'Content Area', WPCA_LANG )
                ),
                'public' => true,
                'has_archive' => false,
                'menu_icon'           => 'dashicons-schedule',

            )
        );
    }

    /**
     * Sets up the custom post type admin area columns
     *
     * @since 1.0.0
     */
    public function columns( $columns ) {

        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'title'     => __( 'Title', WPCA_LANG ),
            'shortcode' => __( 'Shortcode', WPCA_LANG ),
            'modified'  => __( 'Last Modified', WPCA_LANG ),
            'date'      => __( 'Date', WPCA_LANG )
        );

        return $columns;
    }

    /**
     * Gets the custom column type markup
     *
     * @since 1.0.0
     */
    public function custom_columns( $column, $post_id ) {

        global $post;
        $post_id = absint( $post_id );

        switch ( $column ) {
            case 'shortcode' :
                echo '<code>[content_area id="' . $post_id . '"]</code>';
                break;
            case 'modified' :
                the_modified_date();
                break;
        }
    }

    /**
     * Registers the [content_area] short code
     *
     * @since 1.0.0
     */
    public function register_shortcode( $atts, $content = null ) {
        $a = shortcode_atts( array(
            'id' => '0',
        ), $atts );

        $post = get_post( intval($a['id']) );

        static::$ids[] = $a['id'];

        return do_shortcode( $post->post_content );
    }

    /**
     * Forces Visual Composer to output the content 
     * areas custom CSS on the correct page
     *
     * @since 1.0.0
     */
    public function add_vc_custom_css()
    {
        global $vc_manager;
        
        if (isset($vc_manager)) {
            $vc = $vc_manager->vc();
            foreach (static::$ids as $id) {
                // Force Visual Composer to spit out the right CSS
                $vc->addShortcodesCustomCss(intval($id));
                $vc->addPageCustomCss(intval($id));  
            }
        }
    }
}

add_action( 'init', array('WP_Content_Areas', 'instance') );
