<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GO_Settings {

    private $options;

    public function __construct() {
    /**
     * Styles and scripts version.
     *
     * @var string
     */
    private $version;

    public function __construct( $version = GoogleOffer::VERSION ) {
        $this->version = $version;

        add_action( 'admin_menu', [ $this, 'add_plugin_page' ] );
        add_action( 'admin_init', [ $this, 'page_init' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function add_plugin_page() {
        add_submenu_page(
            'woocommerce',
            __( 'تخفیف کاربران گوگل', 'googleoffer' ),
            __( 'تخفیف کاربران گوگل', 'googleoffer' ),
            'manage_woocommerce',
            'googleoffer-settings',
            [ $this, 'create_admin_page' ]
        );
    }

    public function create_admin_page() {
        $this->options = get_option( 'googleoffer_settings' );
        ?>
        <div class="wrap go-settings-wrap">
            <h1><?php esc_html_e( 'تنظیمات افزونه تخفیف کاربران گوگل', 'googleoffer' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'googleoffer_option_group' );
                do_settings_sections( 'googleoffer-setting-admin' );
@@ -89,34 +98,34 @@ class GO_Settings {
                echo "<select id='{$args['id']}' name='googleoffer_settings[{$args['id']}]'>";
                foreach ( $args['options'] as $key => $label ) {
                    echo "<option value='{$key}' " . selected( $value, $key, false ) . ">{$label}</option>";
                }
                echo '</select>';
                break;
            case 'products':
                $product_ids = (array) $value;
                ?>
                <select class="wc-product-search" multiple="multiple" style="width: 50%;" id="<?php echo esc_attr($args['id']); ?>" name="googleoffer_settings[<?php echo esc_attr($args['id']); ?>][]" data-placeholder="<?php esc_attr_e( 'جستجوی محصول&hellip;', 'googleoffer' ); ?>">
                    <?php
                    foreach ( $product_ids as $product_id ) {
                        $product = wc_get_product( $product_id );
                        if ( is_object( $product ) ) {
                            echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
                        }
                    }
                    ?>
                </select>
                <?php echo $desc; ?>
                <?php
                break;
        }
    }
    
    public function enqueue_admin_assets($hook) {
        if ($hook != 'woocommerce_page_googleoffer-settings') {
    public function enqueue_admin_assets( $hook ) {
        if ( $hook !== 'woocommerce_page_googleoffer-settings' ) {
            return;
        }
        wp_enqueue_style( 'go-admin-styles', GO_PLUGIN_URL . 'assets/css/admin-styles.css', [], self::VERSION );
        wp_enqueue_style( 'go-admin-styles', GO_PLUGIN_URL . 'assets/css/admin-styles.css', [], $this->version );
        wp_enqueue_script( 'wc-enhanced-select' );
        wp_enqueue_script( 'go-admin-scripts', GO_PLUGIN_URL . 'assets/js/admin-scripts.js', ['jquery', 'wc-enhanced-select'], self::VERSION, true );
        wp_enqueue_script( 'go-admin-scripts', GO_PLUGIN_URL . 'assets/js/admin-scripts.js', [ 'jquery', 'wc-enhanced-select' ], $this->version, true );
    }
}
