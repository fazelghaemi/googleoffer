<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GO_Core {

    private $settings;

    public function __construct() {
        $this->settings = get_option( 'googleoffer_settings', [] );

        if ( empty( $this->settings['enabled'] ) ) {
            return;
        }

        add_action( 'template_redirect', [ $this, 'check_user_source_and_set_cookie' ] );
        
        $discount_type = !empty($this->settings['discount_type']) ? $this->settings['discount_type'] : 'product';
        if ($discount_type === 'product') {
            add_action( 'woocommerce_before_calculate_totals', [ $this, 'apply_product_discount' ], 10, 1 );
        } else {
            add_action( 'woocommerce_cart_calculate_fees', [ $this, 'apply_cart_fee_discount' ], 10, 1 );
        }

        add_action( 'woocommerce_before_checkout_form', [ $this, 'display_checkout_message' ] );
    }
    
    public function is_eligible_for_discount() {
        if ( isset( $_COOKIE[ GO_COOKIE_NAME ] ) && time() < (int) $_COOKIE[ GO_COOKIE_NAME ] ) {
            return true;
        }
        return false;
    }

    public function check_user_source_and_set_cookie() {
        if ( is_admin() || $this->is_eligible_for_discount() ) {
            return;
        }

        $is_google_user = false;
        $referrer = isset( $_SERVER['HTTP_REFERER'] ) ? parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST ) : '';

        if ( $referrer && preg_match( '/\.google\./i', $referrer ) ) {
            $is_google_user = true;
        }

        if ( isset( $_GET['sim_google_user'] ) && $_GET['sim_google_user'] == '1' && current_user_can( 'manage_options' ) ) {
            $is_google_user = true;
        }
        
        if ( isset( $_GET['clear_google_status'] ) && $_GET['clear_google_status'] == '1' && current_user_can( 'manage_options' ) ) {
            setcookie( GO_COOKIE_NAME, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
            wp_safe_redirect( remove_query_arg( 'clear_google_status' ) );
            exit;
        }

        if ( $is_google_user ) {
            $duration_hours = !empty($this->settings['cookie_duration']) ? (int)$this->settings['cookie_duration'] : 1;
            $duration_seconds = $duration_hours * HOUR_IN_SECONDS;
            $expiration_time = time() + $duration_seconds;
            setcookie( GO_COOKIE_NAME, $expiration_time, $expiration_time, COOKIEPATH, COOKIE_DOMAIN );
             // Refresh the page to apply discount immediately if needed
            if (!isset($_COOKIE[GO_COOKIE_NAME])) {
                wp_safe_redirect(remove_query_arg('sim_google_user'));
                exit;
            }
        }
    }

    public function apply_product_discount( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        if ( ! $this->is_eligible_for_discount() ) return;
        
        $discount_percent = !empty($this->settings['discount_percent']) ? (float)$this->settings['discount_percent'] : 0;
        $allowed_products = !empty($this->settings['products']) ? (array)$this->settings['products'] : [];
        
        if ( $discount_percent <= 0 ) return;

        foreach ( $cart->get_cart() as $cart_item ) {
            $product_id = $cart_item['product_id'];
            if ( empty($allowed_products) || in_array($product_id, $allowed_products) ) {
                $price = $cart_item['data']->get_price();
                $discounted_price = $price * ( 1 - ( $discount_percent / 100 ) );
                $cart_item['data']->set_price( $discounted_price );
            }
        }
    }
    
    public function apply_cart_fee_discount( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        if ( ! $this->is_eligible_for_discount() ) return;

        $discount_percent = !empty($this->settings['discount_percent']) ? (float)$this->settings['discount_percent'] : 0;
        if ($discount_percent <= 0) return;

        $discount_amount = ( $cart->get_subtotal() * $discount_percent ) / 100;
        $cart->add_fee( __( 'تخفیف ویژه گوگل', 'googleoffer' ), -$discount_amount );
    }

    public function display_checkout_message() {
        if ( $this->is_eligible_for_discount() && ! empty( $this->settings['checkout_message'] ) ) {
            wc_print_notice( esc_html( $this->settings['checkout_message'] ), 'success' );
        }
    }
}