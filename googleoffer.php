<?php
/**
 * Plugin Name:       GOOGLE Offer | تخفیف هوشمند برای کاربران گوگل
 * Description:       تخفیف هوشمند و خودکار برای کاربرانی که از طریق جستجوی گوگل وارد سایت شما می‌شوند.
 * Version:           1.0.0
 * Author:            ReadyStudio | FazelGhaemi
 * Author URI:        https://readystudio.ir/
 * License:           GPL v2 or later
 * Text Domain:       googleoffer
 * Domain Path:       /languages
 * WC requires at least: 3.0
 * WC tested up to: 8.9
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * The main plugin class.
 */
final class GoogleOffer {

    /**
     * Plugin version.
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * The single instance of the class.
     *
     * @var GoogleOffer
     */
    private static $_instance = null;

    /**
     * Main GoogleOffer Instance.
     * Ensures only one instance of the class is loaded.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->define_constants();
        add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ], -1 );
    }

    /**
     * Define Plugin Constants.
     */
    private function define_constants() {
        define( 'GO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
        define( 'GO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        define( 'GO_COOKIE_NAME', 'go_google_user_status' );
    }

    /**
     * Load required files and initialize hooks.
     */
    public function on_plugins_loaded() {
        // Check if WooCommerce is active.
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', [ $this, 'woocommerce_missing_notice' ] );
            return;
        }

        // Include required files.
        require_once GO_PLUGIN_PATH . 'includes/class-go-settings.php';
        require_once GO_PLUGIN_PATH . 'includes/class-go-core.php';
        require_once GO_PLUGIN_PATH . 'includes/class-go-dev-mode.php';

        // Initialize classes.
        new GO_Settings();
        new GO_Core();
        new GO_Dev_Mode();
        
        // Load text domain for translations.
        load_plugin_textdomain( 'googleoffer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Show a notice if WooCommerce is not active.
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php esc_html_e( 'افزونه "تخفیف برای کاربران گوگل" برای کار کردن نیاز به نصب و فعال بودن ووکامرس دارد.', 'googleoffer' ); ?></p>
        </div>
        <?php
    }
}

/**
 * Begins execution of the plugin.
 */
function google_offer() {
    return GoogleOffer::instance();
}

// Let's go!
google_offer();