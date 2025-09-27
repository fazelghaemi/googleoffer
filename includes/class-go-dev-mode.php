<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GO_Dev_Mode {

    public function __construct() {
    private $core;
    private $version;

    public function __construct( GO_Core $core, $version = GoogleOffer::VERSION ) {
        $this->core    = $core;
        $this->version = $version;

        add_action( 'wp_footer', [ $this, 'render_dev_panel' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
    }

    public function enqueue_styles() {
        if ( current_user_can( 'manage_options' ) ) {
            wp_enqueue_style( 'go-dev-mode-styles', GO_PLUGIN_URL . 'assets/css/dev-mode.css', [], GoogleOffer::VERSION );
            wp_enqueue_style( 'go-dev-mode-styles', GO_PLUGIN_URL . 'assets/css/dev-mode.css', [], $this->version );
        }
    }

    public function render_dev_panel() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        $core = new GO_Core();
        $is_eligible = $core->is_eligible_for_discount();
        
        $status = $is_eligible ? 
            '<strong style="color: #28a745;">' . __( 'کاربر گوگل (تخفیف فعال)', 'googleoffer' ) . '</strong>' : 
            __( 'کاربر عادی', 'googleoffer' );
            
        $remaining_time = 'N/A';
        if ($is_eligible) {
            $expiration_time = (int) $_COOKIE[ GO_COOKIE_NAME ];
            $remaining_seconds = $expiration_time - time();
            $remaining_time = $remaining_seconds > 0 ? gmdate( "H:i:s", $remaining_seconds ) : 'Expired';

        $is_eligible = $this->core->is_eligible_for_discount();
        $status      = $is_eligible
            ? '<strong style="color: #28a745;">' . __( 'کاربر گوگل (تخفیف فعال)', 'googleoffer' ) . '</strong>'
            : __( 'کاربر عادی', 'googleoffer' );

        $remaining_time = __( 'N/A', 'googleoffer' );
        $expiration     = $this->core->get_cookie_expiration();

        if ( $is_eligible && $expiration ) {
            $remaining_seconds = $expiration - time();
            $remaining_time    = $remaining_seconds > 0 ? gmdate( 'H:i:s', $remaining_seconds ) : __( 'Expired', 'googleoffer' );
        }

        $settings_url = admin_url( 'admin.php?page=googleoffer-settings' );
        $sim_url = add_query_arg( 'sim_google_user', '1' );
        $clear_url = add_query_arg( 'clear_google_status', '1' );
        $sim_url      = add_query_arg( 'sim_google_user', '1' );
        $clear_url    = add_query_arg( 'clear_google_status', '1' );
        ?>
        <div id="go-dev-panel">
            <h4 class="go-panel-title"><?php _e( 'پنل توسعه | تخفیف گوگل', 'googleoffer' ); ?></h4>
            <div class="go-panel-content">
                <p><strong><?php _e( 'وضعیت کاربر:', 'googleoffer' ); ?></strong> <?php echo $status; ?></p>
                <p><strong><?php _e( 'زمان باقی‌مانده:', 'googleoffer' ); ?></strong> <?php echo $remaining_time; ?></p>
                <p><strong><?php _e( 'وضعیت کاربر:', 'googleoffer' ); ?></strong> <?php echo wp_kses_post( $status ); ?></p>
                <p><strong><?php _e( 'زمان باقی‌مانده:', 'googleoffer' ); ?></strong> <?php echo esc_html( $remaining_time ); ?></p>
            </div>
            <div class="go-panel-actions">
                <a href="<?php echo esc_url( $sim_url ); ?>" class="button"><?php _e( 'شبیه‌سازی کاربر گوگل', 'googleoffer' ); ?></a>
                <a href="<?php echo esc_url( $clear_url ); ?>" class="button"><?php _e( 'پاک کردن وضعیت', 'googleoffer' ); ?></a>
                <a href="<?php echo esc_url( $settings_url ); ?>" class="button" target="_blank"><?php _e( 'تنظیمات افزونه', 'googleoffer' ); ?></a>
            </div>
        </div>
        <?php
    }
}
}
