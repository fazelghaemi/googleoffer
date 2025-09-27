<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GO_Settings {

    private $options;

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
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'googleoffer_option_group',
            'googleoffer_settings',
            [ $this, 'sanitize' ]
        );

        add_settings_section(
            'setting_section_id',
            __( 'تنظیمات اصلی', 'googleoffer' ),
            null,
            'googleoffer-setting-admin'
        );

        add_settings_field( 'enabled', __( 'فعال‌سازی افزونه', 'googleoffer' ), [ $this, 'field_callback' ], 'googleoffer-setting-admin', 'setting_section_id', [ 'type' => 'checkbox', 'id' => 'enabled' ] );
        add_settings_field( 'discount_percent', __( 'درصد تخفیف', 'googleoffer' ), [ $this, 'field_callback' ], 'googleoffer-setting-admin', 'setting_section_id', [ 'type' => 'number', 'id' => 'discount_percent', 'desc' => 'فقط عدد را بدون علامت درصد وارد کنید.' ] );
        add_settings_field( 'discount_type', __( 'نوع تخفیف', 'googleoffer' ), [ $this, 'field_callback' ], 'googleoffer-setting-admin', 'setting_section_id', [ 'type' => 'select', 'id' => 'discount_type', 'options' => [ 'product' => 'روی قیمت هر محصول', 'cart' => 'روی کل سبد خرید (به عنوان هزینه منفی)' ] ] );
        add_settings_field( 'products', __( 'اعمال روی محصولات خاص', 'googleoffer' ), [ $this, 'field_callback' ], 'googleoffer-setting-admin', 'setting_section_id', [ 'type' => 'products', 'id' => 'products', 'desc' => 'اگر محصولی انتخاب نشود، تخفیف روی تمام محصولات اعمال خواهد شد.' ] );
        add_settings_field( 'cookie_duration', __( 'مدت اعتبار تخفیف (به ساعت)', 'googleoffer' ), [ $this, 'field_callback' ], 'googleoffer-setting-admin', 'setting_section_id', [ 'type' => 'number', 'id' => 'cookie_duration', 'desc' => 'کاربر تا چند ساعت پس از ورود از گوگل مشمول تخفیف باشد؟ (پیش‌فرض: 1 ساعت)' ] );
        add_settings_field( 'checkout_message', __( 'پیام در صفحه تسویه‌حساب', 'googleoffer' ), [ $this, 'field_callback' ], 'googleoffer-setting-admin', 'setting_section_id', [ 'type' => 'textarea', 'id' => 'checkout_message', 'desc' => 'این پیام به کاربرانی که تخفیف گرفته‌اند نمایش داده می‌شود. مثال: "تخفیف ویژه به دلیل ورود شما از گوگل برایتان منظور شد!"' ] );
    }

    public function sanitize( $input ) {
        $new_input = [];
        if ( isset( $input['enabled'] ) ) $new_input['enabled'] = absint( $input['enabled'] );
        if ( isset( $input['discount_percent'] ) ) $new_input['discount_percent'] = floatval( $input['discount_percent'] );
        if ( isset( $input['discount_type'] ) ) $new_input['discount_type'] = sanitize_text_field( $input['discount_type'] );
        if ( isset( $input['products'] ) ) $new_input['products'] = array_map( 'absint', (array) $input['products'] );
        if ( isset( $input['cookie_duration'] ) ) $new_input['cookie_duration'] = absint( $input['cookie_duration'] );
        if ( isset( $input['checkout_message'] ) ) $new_input['checkout_message'] = sanitize_textarea_field( $input['checkout_message'] );
        return $new_input;
    }

    public function field_callback( $args ) {
        $value = isset( $this->options[ $args['id'] ] ) ? $this->options[ $args['id'] ] : '';
        $desc = isset( $args['desc'] ) ? "<p class='description'>{$args['desc']}</p>" : '';
        
        switch ( $args['type'] ) {
            case 'checkbox':
                printf( '<label><input type="checkbox" id="%s" name="googleoffer_settings[%s]" value="1" %s /> %s</label>', $args['id'], $args['id'], checked( 1, $value, false ), __( 'فعال', 'googleoffer' ) );
                break;
            case 'number':
                printf( '<input type="number" id="%s" name="googleoffer_settings[%s]" value="%s" step="0.1" class="regular-text" />%s', $args['id'], $args['id'], esc_attr( $value ), $desc );
                break;
            case 'textarea':
                printf( '<textarea id="%s" name="googleoffer_settings[%s]" rows="4" class="large-text">%s</textarea>%s', $args['id'], $args['id'], esc_textarea( $value ), $desc );
                break;
            case 'select':
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
    
    public function enqueue_admin_assets( $hook ) {
        if ( $hook !== 'woocommerce_page_googleoffer-settings' ) {
            return;
        }
        wp_enqueue_style( 'go-admin-styles', GO_PLUGIN_URL . 'assets/css/admin-styles.css', [], $this->version );
        wp_enqueue_script( 'wc-enhanced-select' );
        wp_enqueue_script( 'go-admin-scripts', GO_PLUGIN_URL . 'assets/js/admin-scripts.js', [ 'jquery', 'wc-enhanced-select' ], $this->version, true );
    }
}