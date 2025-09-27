<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GO_Core {

    const ORIGINAL_PRICE_KEY = 'google_offer_original_price';

    private $settings;

    public function __construct() {
        $this->settings = get_option( 'googleoffer_settings', [] );
        $this->settings = wp_parse_args(
            get_option( 'googleoffer_settings', [] ),
            [
                'enabled'           => 0,
                'discount_percent'  => 0,
                'discount_type'     => 'product',
                'products'          => [],
                'cookie_duration'   => 1,
                'checkout_message'  => '',
            ]
        );

        add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'handle_cart_session_restore' ], 10, 1 );
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'maybe_adjust_product_pricing' ], 10, 1 );

        if ( empty( $this->settings['enabled'] ) ) {
            return;
        }

        add_action( 'template_redirect', [ $this, 'check_user_source_and_set_cookie' ] );
        
        $discount_type = !empty($this->settings['discount_type']) ? $this->settings['discount_type'] : 'product';
        if ($discount_type === 'product') {
            add_action( 'woocommerce_before_calculate_totals', [ $this, 'apply_product_discount' ], 10, 1 );
        } else {

        if ( 'cart' === $this->settings['discount_type'] ) {
            add_action( 'woocommerce_cart_calculate_fees', [ $this, 'apply_cart_fee_discount' ], 10, 1 );
        }

        add_action( 'woocommerce_before_checkout_form', [ $this, 'display_checkout_message' ] );
    }
    

    public function is_eligible_for_discount() {
        if ( isset( $_COOKIE[ GO_COOKIE_NAME ] ) && time() < (int) $_COOKIE[ GO_COOKIE_NAME ] ) {
            return true;
        }
        return false;
        $expiration = $this->get_cookie_expiration();

        return $expiration && time() < $expiration;
    }

    public function get_cookie_expiration() {
        return isset( $_COOKIE[ GO_COOKIE_NAME ] ) ? (int) $_COOKIE[ GO_COOKIE_NAME ] : null;
    }

    public function check_user_source_and_set_cookie() {
        if ( is_admin() || $this->is_eligible_for_discount() ) {
        if ( empty( $this->settings['enabled'] ) || is_admin() ) {
            return;
        }

        if ( isset( $_GET['clear_google_status'] ) && '1' === $_GET['clear_google_status'] && current_user_can( 'manage_options' ) ) {
            $secure = is_ssl();
            wc_setcookie( GO_COOKIE_NAME, '', time() - YEAR_IN_SECONDS, $secure );
            unset( $_COOKIE[ GO_COOKIE_NAME ] );
            wp_safe_redirect( remove_query_arg( 'clear_google_status' ) );
            exit;
        }

        if ( $this->is_eligible_for_discount() ) {
            return;
        }

        $is_google_user = false;
        $referrer = isset( $_SERVER['HTTP_REFERER'] ) ? parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST ) : '';
        $referrer       = isset( $_SERVER['HTTP_REFERER'] ) ? wp_parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST ) : '';

        if ( $referrer && preg_match( '/\.google\./i', $referrer ) ) {
            $is_google_user = true;
        }

        if ( isset( $_GET['sim_google_user'] ) && $_GET['sim_google_user'] == '1' && current_user_can( 'manage_options' ) ) {
        if ( isset( $_GET['sim_google_user'] ) && '1' === $_GET['sim_google_user'] && current_user_can( 'manage_options' ) ) {
            $is_google_user = true;
        }
        
        if ( isset( $_GET['clear_google_status'] ) && $_GET['clear_google_status'] == '1' && current_user_can( 'manage_options' ) ) {
            setcookie( GO_COOKIE_NAME, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
            wp_safe_redirect( remove_query_arg( 'clear_google_status' ) );
            exit;

        if ( ! $is_google_user ) {
            return;
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
        $duration_hours   = max( 1, (int) $this->settings['cookie_duration'] );
        $duration_seconds = $duration_hours * HOUR_IN_SECONDS;
        $expiration_time  = time() + $duration_seconds;

        $secure = is_ssl();
        wc_setcookie( GO_COOKIE_NAME, $expiration_time, $expiration_time, $secure );
        $_COOKIE[ GO_COOKIE_NAME ] = $expiration_time;

        if ( isset( $_GET['sim_google_user'] ) ) {
            wp_safe_redirect( remove_query_arg( 'sim_google_user' ) );
            exit;
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
    public function maybe_adjust_product_pricing( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        if ( ! $this->is_product_mode_enabled() ) {
            $this->restore_original_prices( $cart, true );
            return;
        }

        if ( ! $this->is_eligible_for_discount() ) {
            $this->restore_original_prices( $cart, true );
            return;
        }

        $discount_percent = (float) $this->settings['discount_percent'];
        $allowed_products = array_map( 'absint', array_filter( (array) $this->settings['products'] ) );

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            $product_id = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;

            if ( ! empty( $allowed_products ) && ! in_array( $product_id, $allowed_products, true ) ) {
                if ( isset( $cart_item[ self::ORIGINAL_PRICE_KEY ] ) ) {
                    $cart_item['data']->set_price( $cart_item[ self::ORIGINAL_PRICE_KEY ] );
                    unset( $cart_item[ self::ORIGINAL_PRICE_KEY ] );
                    $cart->cart_contents[ $cart_item_key ] = $cart_item;
                }
                continue;
            }

            $product = $cart_item['data'];

            if ( ! isset( $cart_item[ self::ORIGINAL_PRICE_KEY ] ) ) {
                $cart_item[ self::ORIGINAL_PRICE_KEY ] = (float) $product->get_price();
            }

            $original_price  = (float) $cart_item[ self::ORIGINAL_PRICE_KEY ];
            $discount_factor = max( 0, 1 - ( $discount_percent / 100 ) );
            $discounted      = wc_format_decimal( $original_price * $discount_factor, wc_get_price_decimals() );

            $product->set_price( $discounted );
            $cart->cart_contents[ $cart_item_key ] = $cart_item;
        }
    }
    

    public function apply_cart_fee_discount( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        if ( ! $this->is_eligible_for_discount() ) return;
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        if ( ! $this->is_cart_mode_enabled() || ! $this->is_eligible_for_discount() ) {
            return;
        }

        $discount_percent = (float) $this->settings['discount_percent'];

        $discount_percent = !empty($this->settings['discount_percent']) ? (float)$this->settings['discount_percent'] : 0;
        if ($discount_percent <= 0) return;
        if ( $discount_percent <= 0 ) {
            return;
        }

        $discount_amount = wc_format_decimal( ( $cart->get_subtotal() * $discount_percent ) / 100, wc_get_price_decimals() );

        if ( $discount_amount <= 0 ) {
            return;
        }

        $discount_amount = ( $cart->get_subtotal() * $discount_percent ) / 100;
        $cart->add_fee( __( 'تخفیف ویژه گوگل', 'googleoffer' ), -$discount_amount );
    }

    public function display_checkout_message() {
        if ( empty( $this->settings['enabled'] ) ) {
            return;
        }

        if ( $this->is_eligible_for_discount() && ! empty( $this->settings['checkout_message'] ) ) {
            wc_print_notice( esc_html( $this->settings['checkout_message'] ), 'success' );
        }
    }
}

    public function handle_cart_session_restore( $cart ) {
        $remove_marker = ! $this->is_product_mode_enabled();
        $this->restore_original_prices( $cart, $remove_marker );
    }

    private function restore_original_prices( $cart, $remove_marker ) {
        if ( ! is_a( $cart, 'WC_Cart' ) ) {
            return;
        }

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( ! isset( $cart_item[ self::ORIGINAL_PRICE_KEY ] ) ) {
                continue;
            }

            $cart_item['data']->set_price( (float) $cart_item[ self::ORIGINAL_PRICE_KEY ] );

            if ( $remove_marker ) {
                unset( $cart_item[ self::ORIGINAL_PRICE_KEY ] );
            }

            $cart->cart_contents[ $cart_item_key ] = $cart_item;
        }
    }

    private function is_product_mode_enabled() {
        return ! empty( $this->settings['enabled'] )
            && 'product' === $this->settings['discount_type']
            && (float) $this->settings['discount_percent'] > 0;
    }

    private function is_cart_mode_enabled() {
        return ! empty( $this->settings['enabled'] )
            && 'cart' === $this->settings['discount_type']
            && (float) $this->settings['discount_percent'] > 0;
    }
}
