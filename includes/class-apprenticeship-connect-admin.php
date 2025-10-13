<?php
/**
 * Admin functionality class
 *
 * @package ApprenticeshipConnect
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Include the core class file to make it available for AJAX handlers
require_once APRCN_PLUGIN_DIR . 'includes/class-apprenticeship-connect.php';

/**
 * Admin functionality class
 */
class ApprenticeshipConnectAdmin {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Ensure admin menu is added only once
        if ( ! has_action( 'admin_menu', array( $this, 'add_admin_menu' ) ) ) {
            add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        }

        // Ensure duplicate submenu cleanup is added only once
        if ( ! has_action( 'admin_menu', array( $this, 'cleanup_duplicate_submenu' ) ) ) {
            add_action( 'admin_menu', array( $this, 'cleanup_duplicate_submenu' ), 999 );
        }

        add_action( 'admin_init', array( $this, 'migrate_options' ), 1 );
        add_action( 'admin_init', array( $this, 'init_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'wp_ajax_aprcn_manual_sync', array( $this, 'ajax_manual_sync' ) );
        add_action( 'wp_ajax_aprcn_test_api', array( $this, 'ajax_test_api' ) );
        add_action( 'wp_ajax_aprcn_test_and_sync', array( $this, 'ajax_test_and_sync' ) );
        add_action( 'wp_ajax_aprcn_save_api_settings', array( $this, 'ajax_save_api_settings' ) );

        // Ensure plugin action links are added only once
        if ( ! has_filter( 'plugin_action_links_' . APRCN_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) ) ) {
            add_filter( 'plugin_action_links_' . APRCN_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
        }

        // Ensure plugin row meta is added only once
        if ( ! has_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ) ) ) {
            add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
        }
    }

    /**
     * Add action links on the Plugins page
     */
    public function plugin_action_links( $links ) {
        $setup_completed = (bool) get_option( 'aprcn_setup_completed' );
        $url = $setup_completed
            ? admin_url( 'admin.php?page=apprenticeship-connect-settings' )
            : admin_url( 'admin.php?page=apprenticeship-connect-setup' );
        $label = $setup_completed
            ? __( 'Settings', 'apprenticeship-connect' )
            : __( 'Setup Wizard', 'apprenticeship-connect' );

        $custom_link = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
        array_unshift( $links, $custom_link );
        return $links;
    }

    /**
     * Add Buy Me a Coffee link on the Plugins page row meta
     */
    public function plugin_row_meta( $links, $file ) {
        if ( $file === APRCN_PLUGIN_BASENAME ) {
            $links[] = '<a href="https://buymeacoffee.com/epark" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Buy me a Coffee ☕️', 'apprenticeship-connect' ) . '</a>';
        }
        return $links;
    }

    /**
     * Migrate legacy options to new keys
     */
    public function migrate_options() {
        $old_options = get_option( 'ac_plugin_options', false );
        if ( $old_options && ! get_option( 'aprcn_plugin_options', false ) ) {
            update_option( 'aprcn_plugin_options', $old_options );
        }

        $old_setup = get_option( 'ac_setup_completed', null );
        if ( ! is_null( $old_setup ) && is_null( get_option( 'aprcn_setup_completed', null ) ) ) {
            update_option( 'aprcn_setup_completed', $old_setup );
        }

        $old_page_id = get_option( 'ac_vacancy_page_id', null );
        if ( ! is_null( $old_page_id ) && is_null( get_option( 'aprcn_vacancy_page_id', null ) ) ) {
            update_option( 'aprcn_vacancy_page_id', $old_page_id );
        }
    }
    
    /**
     * Remove duplicate submenu that mirrors the top-level link
     */
    public function cleanup_duplicate_submenu() {
        remove_submenu_page( 'apprenticeship-connect', 'apprenticeship-connect' );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __( 'Apprenticeship Connect', 'apprenticeship-connect' ),
            __( 'Apprenticeship Connect', 'apprenticeship-connect' ),
            'manage_options',
            'apprenticeship-connect',
            '__return_null', // This will be a placeholder; the first submenu item will be the default.
            'dashicons-welcome-learn-more',
            30
        );

        // Submenus
        add_submenu_page(
            'apprenticeship-connect',
            __( 'Add Vacancies', 'apprenticeship-connect' ),
            __( 'Add Vacancies', 'apprenticeship-connect' ),
            'manage_options',
            'post-new.php?post_type=vacancy'
        );

        add_submenu_page(
            'apprenticeship-connect',
            __( 'All Vacancies', 'apprenticeship-connect' ),
            __( 'All Vacancies', 'apprenticeship-connect' ),
            'manage_options',
            'edit.php?post_type=vacancy'
        );

        add_submenu_page(
            'apprenticeship-connect',
            __( 'Settings', 'apprenticeship-connect' ),
            __( 'Settings', 'apprenticeship-connect' ),
            'manage_options',
            'apprenticeship-connect-settings',
            array( $this, 'admin_page' ) // Use the existing admin_page method to render settings
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts( $hook ) {
        // Load assets on apprenticeship-connect pages and setup wizard
        if ( strpos( $hook, 'apprenticeship-connect' ) === false && strpos( $hook, 'apprenticeship-connect-setup' ) === false ) {
            return;
        }
        
        wp_enqueue_style( 'apprenticeship-connect-admin', APRCN_PLUGIN_URL . 'assets/css/apprenticeship-connect.css', array(), APRCN_PLUGIN_VERSION );
        wp_enqueue_script( 'apprenticeship-connect-admin', APRCN_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), APRCN_PLUGIN_VERSION, true );
        wp_localize_script( 'apprenticeship-connect-admin', 'aprcnAjax', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'aprcn_admin_nonce' ),
            'strings' => array(
                'syncing' => __( 'Syncing vacancies...', 'apprenticeship-connect' ),
                'testing' => __( 'Testing API connection...', 'apprenticeship-connect' ),
                'success' => __( 'Success!', 'apprenticeship-connect' ),
                'error' => __( 'Error occurred.', 'apprenticeship-connect' ),
            ),
        ) );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting( 'aprcn_plugin_options', 'aprcn_plugin_options', array( $this, 'sanitize_options' ) );
        
        add_settings_section(
            'ac_api_settings',
            __( 'API Configuration', 'apprenticeship-connect' ),
            array( $this, 'api_settings_section_callback' ),
            'apprenticeship-connect-settings'
        );
        
        add_settings_field(
            'api_base_url',
            __( 'API Base URL', 'apprenticeship-connect' ),
            array( $this, 'api_base_url_callback' ),
            'apprenticeship-connect-settings',
            'ac_api_settings'
        );
        
        add_settings_field(
            'api_subscription_key',
            __( 'API Subscription Key', 'apprenticeship-connect' ),
            array( $this, 'api_subscription_key_callback' ),
            'apprenticeship-connect-settings',
            'ac_api_settings'
        );
        
        add_settings_field(
            'api_ukprn',
            __( 'UKPRN (Optional)', 'apprenticeship-connect' ),
            array( $this, 'api_ukprn_callback' ),
            'apprenticeship-connect-settings',
            'ac_api_settings'
        );
        
        // Add Test & Sync button directly under API fields
        add_settings_field(
            'test_and_sync',
            __( 'Test & Sync', 'apprenticeship-connect' ),
            array( $this, 'test_and_sync_callback' ),
            'apprenticeship-connect-settings',
            'ac_api_settings'
        );
        
        // Shortcode section between API and Display for better UX
        add_settings_section(
            'ac_shortcode_info',
            __( 'Shortcode', 'apprenticeship-connect' ),
            array( $this, 'shortcode_section_callback' ),
            'apprenticeship-connect-settings'
        );
        
        add_settings_section(
            'ac_display_settings',
            __( 'Display & Settings', 'apprenticeship-connect' ),
            array( $this, 'display_settings_section_callback' ),
            'apprenticeship-connect-settings'
        );
        
        add_settings_field(
            'display_count',
            __( 'Default Display Count', 'apprenticeship-connect' ),
            array( $this, 'display_count_callback' ),
            'apprenticeship-connect-settings',
            'ac_display_settings'
        );
        
        add_settings_field(
            'show_employer',
            __( 'Show Employer', 'apprenticeship-connect' ),
            array( $this, 'show_employer_callback' ),
            'apprenticeship-connect-settings',
            'ac_display_settings'
        );
        
        add_settings_field(
            'show_location',
            __( 'Show Location', 'apprenticeship-connect' ),
            array( $this, 'show_location_callback' ),
            'apprenticeship-connect-settings',
            'ac_display_settings'
        );
        
        add_settings_field(
            'show_closing_date',
            __( 'Show Closing Date', 'apprenticeship-connect' ),
            array( $this, 'show_closing_date_callback' ),
            'apprenticeship-connect-settings',
            'ac_display_settings'
        );
        
        add_settings_field(
            'show_apply_button',
            __( 'Show Apply Button', 'apprenticeship-connect' ),
            array( $this, 'show_apply_button_callback' ),
            'apprenticeship-connect-settings',
            'ac_display_settings'
        );
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options( $input ) {
        $sanitized = array();
        
        $sanitized['api_base_url'] = esc_url_raw( $input['api_base_url'] );
        $sanitized['api_subscription_key'] = sanitize_text_field( $input['api_subscription_key'] );
        $sanitized['api_ukprn'] = sanitize_text_field( $input['api_ukprn'] );
        $sanitized['display_count'] = absint( $input['display_count'] );
        $sanitized['show_employer'] = isset( $input['show_employer'] ) ? true : false;
        $sanitized['show_location'] = isset( $input['show_location'] ) ? true : false;
        $sanitized['show_closing_date'] = isset( $input['show_closing_date'] ) ? true : false;
        $sanitized['show_apply_button'] = isset( $input['show_apply_button'] ) ? true : false;
        
        return $sanitized;
    }
    
    /**
     * API settings section callback
     */
    public function api_settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure your API credentials to connect to the UK Government Apprenticeship service.', 'apprenticeship-connect' ) . '</p>';
    }
    
    /**
     * Shortcode section callback
     */
    public function shortcode_section_callback() {
        echo '<div class="aprcn-shortcode-inline">';
        echo '<p>' . esc_html__( 'Use this shortcode to display vacancies on any page:', 'apprenticeship-connect' ) . '</p>';
        echo '<code>[apprenticeship_vacancies]</code>';
        echo '<p class="description">' . esc_html__( 'The shortcode uses the display settings configured below.', 'apprenticeship-connect' ) . '</p>';
        echo '</div>';
    }
    
    /**
     * Display settings section callback
     */
    public function display_settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure how vacancies are displayed on your website.', 'apprenticeship-connect' ) . '</p>';
    }
    
    /**
     * API base URL callback
     */
    public function api_base_url_callback() {
        $options = get_option( 'aprcn_plugin_options', array() );
        $value = isset( $options['api_base_url'] ) ? $options['api_base_url'] : 'https://api.apprenticeships.education.gov.uk/vacancies';
        echo '<input type="url" id="api_base_url" name="aprcn_plugin_options[api_base_url]" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'The base URL for the API endpoint.', 'apprenticeship-connect' ) . '</p>';
    }
    
    /**
     * API subscription key callback
     */
    public function api_subscription_key_callback() {
        $options = get_option( 'aprcn_plugin_options', array() );
        $value = isset( $options['api_subscription_key'] ) ? $options['api_subscription_key'] : '';
        echo '<input type="text" id="api_subscription_key" name="aprcn_plugin_options[api_subscription_key]" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Your API subscription key from the UK Government Apprenticeship service.', 'apprenticeship-connect' ) . '</p>';
    }
    
    /**
     * API UKPRN callback
     */
    public function api_ukprn_callback() {
        $options = get_option( 'aprcn_plugin_options', array() );
        $value = isset( $options['api_ukprn'] ) ? $options['api_ukprn'] : '';
        echo '<input type="text" id="api_ukprn" name="aprcn_plugin_options[api_ukprn]" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'UKPRN for your provider (optional).', 'apprenticeship-connect' ) . '</p>';
    }
    
    /**
     * Test & Sync callback
     */
    public function test_and_sync_callback() {
        $options = get_option( 'aprcn_plugin_options', array() );
        $sync_status = $this->get_sync_status();
        $is_configured = $sync_status['is_configured'];
        $last_sync = $sync_status['last_sync'];
        $total_vacancies = $sync_status['total_vacancies'];

        echo '<button type="button" id="aprcn-test-and-sync" class="button button-primary">';
        if ( $is_configured ) {
            echo esc_html__( 'Test & Sync Vacancies', 'apprenticeship-connect' );
        } else {
            echo esc_html__( 'Configure API to Test & Sync', 'apprenticeship-connect' );
        }
        echo '</button>';

        if ( $is_configured ) {
            echo '<p class="description">' . esc_html__( 'Last synced: ', 'apprenticeship-connect' ) . ( $last_sync ? esc_html( gmdate( 'Y-m-d H:i:s', $last_sync ) ) : esc_html__( 'Never', 'apprenticeship-connect' ) ) . '</p>';
            echo '<p class="description">' . esc_html__( 'Total vacancies in database: ', 'apprenticeship-connect' ) . esc_html( $total_vacancies ) . '</p>';
        }
    }
    
    /**
     * Display count callback
     */
    public function display_count_callback() {
        $options = get_option( 'aprcn_plugin_options', array() );
        $value = isset( $options['display_count'] ) ? $options['display_count'] : 10;
        echo '<input type="number" id="display_count" name="aprcn_plugin_options[display_count]" value="' . esc_attr( $value ) . '" min="1" max="100" />';
        echo '<p class="description">' . esc_html__( 'Default number of vacancies to display.', 'apprenticeship-connect' ) . '</p>';
    }
    
    /**
     * Show employer callback
     */
    public function show_employer_callback() {
        $options = get_option( 'aprcn_plugin_options', array() );
        $checked = isset( $options['show_employer'] ) ? $options['show_employer'] : true;
        echo '<input type="checkbox" id="show_employer" name="aprcn_plugin_options[show_employer]" ' . checked( $checked, true, false ) . ' />';
        echo '<label for="show_employer">' . esc_html__( 'Show employer name in vacancy listings', 'apprenticeship-connect' ) . '</label>';
    }
    
    /**
     * Show location callback
     */
    public function show_location_callback() {
        $options = get_option( 'aprcn_plugin_options', array() );
        $checked = isset( $options['show_location'] ) ? $options['show_location'] : true;
        echo '<input type="checkbox" id="show_location" name="aprcn_plugin_options[show_location]" ' . checked( $checked, true, false ) . ' />';
        echo '<label for="show_location">' . esc_html__( 'Show location in vacancy listings', 'apprenticeship-connect' ) . '</label>';
    }
    
    /**
     * Show closing date callback
     */
    public function show_closing_date_callback() {
        $options = get_option( 'aprcn_plugin_options', array() );
        $checked = isset( $options['show_closing_date'] ) ? $options['show_closing_date'] : true;
        echo '<input type="checkbox" id="show_closing_date" name="aprcn_plugin_options[show_closing_date]" ' . checked( $checked, true, false ) . ' />';
        echo '<label for="show_closing_date">' . esc_html__( 'Show closing date in vacancy listings', 'apprenticeship-connect' ) . '</label>';
    }
    
    /**
     * Show apply button callback
     */
    public function show_apply_button_callback() {
        $options = get_option( 'aprcn_plugin_options', array() );
        $checked = isset( $options['show_apply_button'] ) ? $options['show_apply_button'] : true;
        echo '<input type="checkbox" id="show_apply_button" name="aprcn_plugin_options[show_apply_button]" ' . checked( $checked, true, false ) . ' />';
        echo '<label for="show_apply_button">' . esc_html__( 'Show apply button in vacancy listings', 'apprenticeship-connect' ) . '</label>';
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        $options = get_option( 'aprcn_plugin_options', array() );
        ?>
        <div class="wrap aprcn-settings">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <form method="post" action="options.php" class="aprcn-form">
                <?php
                settings_fields( 'aprcn_plugin_options' );
                do_settings_sections( 'apprenticeship-connect-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Get sync status
     */
    private function get_sync_status() {
        $last_sync = get_option( 'aprcn_last_sync' );
        $total_vacancies = wp_count_posts( 'vacancy' );
        $options = get_option( 'aprcn_plugin_options', array() );
        
        return array(
            'last_sync' => $last_sync,
            'total_vacancies' => $total_vacancies->publish,
            'is_configured' => ! empty( $options['api_subscription_key'] ),
        );
    }
    
    /**
     * AJAX manual sync
     */
    public function ajax_manual_sync() {
        check_ajax_referer( 'aprcn_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'apprenticeship-connect' ) );
        }

        $core = new ApprenticeshipConnectCore();
        $result = $core->manual_sync();

        if ( $result ) {
            wp_send_json_success( esc_html__( 'Sync completed successfully!', 'apprenticeship-connect' ) );
        } else {
            wp_send_json_error( esc_html__( 'Sync failed. Please check your API configuration.', 'apprenticeship-connect' ) );
        }
    }
    
    /**
     * AJAX test API
     */
    public function ajax_test_api() {
        check_ajax_referer( 'aprcn_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'apprenticeship-connect' ) );
        }
        
        $options = get_option( 'aprcn_plugin_options', array() );
        
        if ( empty( $options['api_subscription_key'] ) || empty( $options['api_base_url'] ) ) {
            wp_send_json_error( esc_html__( 'API credentials not configured.', 'apprenticeship-connect' ) );
        }
        
        $api_url = $options['api_base_url'] . '/vacancy?PageNumber=1&PageSize=50&Sort=AgeDesc&FilterBySubscription=true';
        
        if ( ! empty( $options['api_ukprn'] ) ) {
            $api_url .= '&Ukprn=' . $options['api_ukprn'];
        }
        
        $headers = array(
            'X-Version'                 => '1',
            'Ocp-Apim-Subscription-Key' => $options['api_subscription_key'],
            'Content-Type'              => 'application/json',
        );
        
        $args = array(
            'headers' => $headers,
            'timeout' => 30,
        );
        
        $response = wp_remote_get( $api_url, $args );
        
        if ( is_wp_error( $response ) ) {
            // This was already fixed, no change needed here.
            wp_send_json_error( esc_html__( 'API connection failed. Please check the logs for more details.', 'apprenticeship-connect' ) );
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_send_json_error( esc_html__( 'Invalid API response format.', 'apprenticeship-connect' ) );
        }
        
        if ( isset( $data->vacancies ) ) {
            wp_send_json_success( esc_html__( 'API connection successful! Found ', 'apprenticeship-connect' ) . count( $data->vacancies ) . esc_html__( ' vacancies.', 'apprenticeship-connect' ) );
        } else {
            wp_send_json_error( esc_html__( 'API response does not contain expected data.', 'apprenticeship-connect' ) );
        }
    }

    /**
     * AJAX test and sync
     */
    public function ajax_test_and_sync() {
        check_ajax_referer( 'aprcn_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'apprenticeship-connect' ) );
        }
        
        $saved = get_option( 'aprcn_plugin_options', array() );
        
        // Allow using current form values without saving
        $api_base_url = isset( $_POST['api_base_url'] ) && $_POST['api_base_url'] !== '' ? esc_url_raw( wp_unslash( $_POST['api_base_url'] ) ) : ( $saved['api_base_url'] ?? '' );
        $api_key      = isset( $_POST['api_subscription_key'] ) && $_POST['api_subscription_key'] !== '' ? sanitize_text_field( wp_unslash( $_POST['api_subscription_key'] ) ) : ( $saved['api_subscription_key'] ?? '' );
        $api_ukprn    = isset( $_POST['api_ukprn'] ) && $_POST['api_ukprn'] !== '' ? sanitize_text_field( wp_unslash( $_POST['api_ukprn'] ) ) : ( $saved['api_ukprn'] ?? '' );
        
        if ( empty( $api_key ) || empty( $api_base_url ) ) {
            wp_send_json_error( esc_html__( 'API credentials not configured.', 'apprenticeship-connect' ) );
        }
        
        // Test API first
        $api_url = $api_base_url . '/vacancy?PageNumber=1&PageSize=50&Sort=AgeDesc&FilterBySubscription=true';
        if ( ! empty( $api_ukprn ) ) {
            $api_url .= '&Ukprn=' . $api_ukprn;
        }
        $headers = array(
            'X-Version'                 => '1',
            'Ocp-Apim-Subscription-Key' => $api_key,
            'Content-Type'              => 'application/json',
        );
        $args = array(
            'headers' => $headers,
            'timeout' => 30,
        );
        $response = wp_remote_get( $api_url, $args );
        if ( is_wp_error( $response ) ) {
            // This was already fixed, no change needed here.
            wp_send_json_error( esc_html__( 'API connection failed. Please check the logs for more details.', 'apprenticeship-connect' ) );
        }
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_send_json_error( esc_html__( 'Invalid API response format.', 'apprenticeship-connect' ) );
        }
        if ( ! isset( $data->vacancies ) ) {
            wp_send_json_error( esc_html__( 'API response does not contain expected data.', 'apprenticeship-connect' ) );
        }
        $vacancy_count = count( $data->vacancies );
        
        // Sync using temporary credentials without saving options
        $main_plugin = ApprenticeshipConnect::get_instance();
        $main_plugin->override_options_for_sync( array(
            'api_base_url' => $api_base_url,
            'api_subscription_key' => $api_key,
            'api_ukprn' => $api_ukprn,
        ) );
        $sync_result = $main_plugin->manual_sync();
        
        if ( $sync_result ) {
            $total_vacancies = wp_count_posts( 'vacancy' );
            wp_send_json_success(
                sprintf(
                /* translators: %1$d is number of vacancies. */
                    esc_html__( 'Success! Found %1$d vacancies from API. Total vacancies in database: %2$d', 'apprenticeship-connect' ),
                    $vacancy_count,
                    $total_vacancies->publish
                )
            );
        } else {
            wp_send_json_error( esc_html__( 'API test successful but sync failed. Please check the error logs.', 'apprenticeship-connect' ) );
        }
    }

    /**
     * Persist API settings after a successful Test & Sync
     */
    public function ajax_save_api_settings() {
        check_ajax_referer( 'aprcn_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'apprenticeship-connect' ) );
        }
        $api_base_url = isset( $_POST['api_base_url'] ) ? esc_url_raw( wp_unslash( $_POST['api_base_url'] ) ) : '';
        $api_key      = isset( $_POST['api_subscription_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_subscription_key'] ) ) : '';
        $api_ukprn    = isset( $_POST['api_ukprn'] ) ? sanitize_text_field( wp_unslash( $_POST['api_ukprn'] ) ) : '';
        if ( empty( $api_base_url ) || empty( $api_key ) ) {
            wp_send_json_error( esc_html__( 'Missing API settings.', 'apprenticeship-connect' ) );
        }
        $options = get_option( 'aprcn_plugin_options', array() );
        $options['api_base_url'] = $api_base_url;
        $options['api_subscription_key'] = $api_key;
        $options['api_ukprn'] = $api_ukprn;
        update_option( 'aprcn_plugin_options', $options );
        wp_send_json_success( __( 'API settings saved.', 'apprenticeship-connect' ) );
    }
}

// Prevent duplicate menu entries
add_action( 'admin_menu', function() {
    global $submenu;
    if ( isset( $submenu['apprenticeship-connect'] ) ) {
        $submenu['apprenticeship-connect'] = array_unique( $submenu['apprenticeship-connect'], SORT_REGULAR );
    }
}, 999);
