<?php
/**
 * Plugin Name: Apprenticeship Connect
 * Plugin URI: https://wordpress.org/plugins/apprenticeship-connect
 * Description: Apprenticeship Connect is a WordPress plugin that seamlessly integrates with the official UK Government's Find an Apprenticeship service. Easily display the latest apprenticeship vacancies on your website, keeping your audience informed and engaged with up-to-date opportunities.
 * Version: 1.1.0
 * Author: ePark Team
 * Author URI: https://e-park.uk
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: apprenticeship-connect
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 *
 * @package ApprenticeshipConnect
 * @version 1.1.0
 * @author ePark Team
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure WordPress functions are loaded
if ( ! function_exists( 'add_action' ) ) {
    require_once ABSPATH . 'wp-includes/pluggable.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Define plugin constants
define( 'APRCN_PLUGIN_VERSION', '1.1.0' );
define( 'APRCN_PLUGIN_FILE', __FILE__ );
define( 'APRCN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'APRCN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'APRCN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Include required files
require_once APRCN_PLUGIN_DIR . 'includes/class-apprenticeship-connect.php';
require_once APRCN_PLUGIN_DIR . 'includes/class-apprenticeship-connect-admin.php';
require_once APRCN_PLUGIN_DIR . 'includes/class-apprenticeship-connect-setup-wizard.php';

/**
 * Main plugin class
 */
class ApprenticeshipConnect {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize admin
        if ( is_admin() ) {
            new ApprenticeshipConnectAdmin();
            new ApprenticeshipConnectSetupWizard();
        }
        
        // Register custom post type
        $this->register_vacancy_cpt();
        
        // Schedule cron job
        $this->schedule_cron_job();
        
        // Add shortcode
        add_shortcode( 'apprenticeship_vacancies', array( $this, 'vacancies_shortcode' ) );

    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        // Removed load_plugin_textdomain() as it is discouraged since WordPress 4.6
    }
    
    /**
     * Allow overriding options for a one-off sync (used by Test & Sync without saving)
     */
    public function override_options_for_sync( array $overrides ): void {
        ApprenticeshipConnectCore::get_instance()->override_options_for_sync($overrides);
    }

    /**
     * Manual sync function
     */
    public function manual_sync() {
        return ApprenticeshipConnectCore::get_instance()->manual_sync();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option( 'ac_plugin_activated', true );
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear cron schedule
        $timestamp = wp_next_scheduled( 'aprcn_daily_fetch_vacancies' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'aprcn_daily_fetch_vacancies' );
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'api_base_url' => 'https://api.apprenticeships.education.gov.uk/vacancies',
            'api_subscription_key' => '',
            'api_ukprn' => '',
            'vacancy_page_url' => '',
            'auto_create_page' => true,
            'display_count' => 10,
            'show_employer' => true,
            'show_location' => true,
            'show_closing_date' => true,
            'show_apply_button' => true,
        );
        
        add_option( 'aprcn_plugin_options', $default_options );
    }
    
    /**
     * Register Custom Post Type for Vacancies
     */
    public function register_vacancy_cpt() {
        $labels = array(
            'name'                  => _x( 'Vacancies', 'Post Type General Name', 'apprenticeship-connect' ),
            'singular_name'         => _x( 'Vacancy', 'Post Type Singular Name', 'apprenticeship-connect' ),
            'menu_name'             => __( 'Vacancies', 'apprenticeship-connect' ),
            'name_admin_bar'        => __( 'Vacancy', 'apprenticeship-connect' ),
            'archives'              => __( 'Vacancy Archives', 'apprenticeship-connect' ),
            'attributes'            => __( 'Vacancy Attributes', 'apprenticeship-connect' ),
            'parent_item_colon'     => __( 'Parent Vacancy:', 'apprenticeship-connect' ),
            'all_items'             => __( 'All Vacancies', 'apprenticeship-connect' ),
            'add_new_item'          => __( 'Add New Vacancy', 'apprenticeship-connect' ),
            'add_new'               => __( 'Add New', 'apprenticeship-connect' ),
            'new_item'              => __( 'New Vacancy', 'apprenticeship-connect' ),
            'edit_item'             => __( 'Edit Vacancy', 'apprenticeship-connect' ),
            'update_item'           => __( 'Update Vacancy', 'apprenticeship-connect' ),
            'view_item'             => __( 'View Vacancy', 'apprenticeship-connect' ),
            'view_items'            => __( 'View Vacancies', 'apprenticeship-connect' ),
            'search_items'          => __( 'Search Vacancy', 'apprenticeship-connect' ),
            'not_found'             => __( 'Not found', 'apprenticeship-connect' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'apprenticeship-connect' ),
            'featured_image'        => __( 'Featured Image', 'apprenticeship-connect' ),
            'set_featured_image'    => __( 'Set featured image', 'apprenticeship-connect' ),
            'remove_featured_image' => __( 'Remove featured image', 'apprenticeship-connect' ),
            'use_featured_image'    => __( 'Use as featured image', 'apprenticeship-connect' ),
            'insert_into_item'      => __( 'Insert into vacancy', 'apprenticeship-connect' ),
            'uploaded_to_this_item' => __( 'Uploaded to this vacancy', 'apprenticeship-connect' ),
            'items_list'            => __( 'Vacancies list', 'apprenticeship-connect' ),
            'items_list_navigation' => __( 'Vacancies list navigation', 'apprenticeship-connect' ),
            'filter_items_list'     => __( 'Filter vacancies list', 'apprenticeship-connect' ),
        );
        
        $args = array(
            'label'                 => __( 'Vacancy', 'apprenticeship-connect' ),
            'description'           => __( 'Apprenticeship Vacancies from external API', 'apprenticeship-connect' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'custom-fields', 'author' ),
            'taxonomies'            => array(),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => false,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-welcome-learn-more',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'rewrite'               => array( 'slug' => 'vacancies-archive' ),
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        
        register_post_type( 'vacancy', $args );
    }
    
    /**
     * Schedule the API fetch function using WP-Cron
     */
    public function schedule_cron_job() {
        $event_hook = 'aprcn_daily_fetch_vacancies';
        
        if ( ! wp_next_scheduled( $event_hook ) ) {
            wp_schedule_event( time(), 'daily', $event_hook );
        }
    }
    
    /**
     * Shortcode to display vacancies
     */
    public function vacancies_shortcode( $atts ) {
        $options = get_option( 'aprcn_plugin_options', array() );
        
        // Use only settings, no shortcode parameters
        $display_settings = array(
            'count' => isset( $options['display_count'] ) ? $options['display_count'] : 10,
            'show_employer' => isset( $options['show_employer'] ) ? $options['show_employer'] : true,
            'show_location' => isset( $options['show_location'] ) ? $options['show_location'] : true,
            'show_closing_date' => isset( $options['show_closing_date'] ) ? $options['show_closing_date'] : true,
            'show_apply_button' => isset( $options['show_apply_button'] ) ? $options['show_apply_button'] : true,
        );
        
        $args = array(
            'post_type'      => 'vacancy',
            'post_status'    => 'publish',
            'posts_per_page' => absint( $display_settings['count'] ),
            'orderby'        => 'date', // Use native post date for sorting
            'order'          => 'DESC',
        );
        
        $vacancies_query = new WP_Query( $args );
        
        ob_start();
        
        if ( $vacancies_query->have_posts() ) {
            echo '<div class="aprcn-vacancies-list">';
            while ( $vacancies_query->have_posts() ) : $vacancies_query->the_post();
                $this->display_vacancy_item( $display_settings );
            endwhile;
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>' . esc_html__( 'No vacancies found at the moment.', 'apprenticeship-connect' ) . '</p>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Display individual vacancy item
     */
    private function display_vacancy_item( $display_settings ) {
        $title = get_the_title();
        $vacancy_url = get_post_meta( get_the_ID(), '_vacancy_url', true );
        $short_description = get_post_meta( get_the_ID(), '_vacancy_description_short', true );
        $employer_name = get_post_meta( get_the_ID(), '_employer_name', true );
        $postcode = get_post_meta( get_the_ID(), '_postcode', true );
        $closing_date = get_post_meta( get_the_ID(), '_closing_date', true );
        
        echo '<div class="aprcn-vacancy-item">';
        echo '<h3><a href="' . esc_url( $vacancy_url ) . '" target="_blank">' . esc_html( $title ) . '</a></h3>';
        if ( $display_settings['show_employer'] && $employer_name ) {
            echo '<p class="aprcn-employer"><strong>' . esc_html__( 'Employer:', 'apprenticeship-connect' ) . '</strong> ' . esc_html( $employer_name );
            if ( $display_settings['show_location'] && $postcode ) {
                echo ' - ' . esc_html( $postcode );
            }
            echo '</p>';
        }
        
        if ( $short_description ) {
            echo '<p class="aprcn-description">' . wp_kses_post( $short_description ) . '</p>';
        }
        
        if ( $display_settings['show_closing_date'] && $closing_date ) {
            echo '<p class="aprcn-closing-date"><strong>' . esc_html__( 'Closing Date:', 'apprenticeship-connect' ) . '</strong> ' . esc_html( gmdate( 'F j, Y', strtotime( $closing_date ) ) ) . '</p>';
        }
        if ( $display_settings['show_apply_button'] ) {
            echo '<p class="aprcn-apply-link"><a href="' . esc_url( $vacancy_url ) . '" target="_blank" class="aprcn-apply-button">' . esc_html__( 'Apply on Apprenticeships Website &raquo;', 'apprenticeship-connect' ) . '</a></p>';
        }
        echo '</div>';
    }
}

// Initialize the plugin
ApprenticeshipConnect::get_instance();
