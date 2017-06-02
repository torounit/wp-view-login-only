<?php
/**
 * Plugin Name: WP View Login Only
 * Plugin URI: https://github.com/chiilog/wp-view-login-only
 * Description: If you view a website without log in, WordPress redirect to the login page
 * Author: mel_cha
 * Author URI: http://chiilog.com/
 * Version: 1.0
 * Text Domain: wp-view-login-only
 *
 * @package wp-view-login-only
 */

/**
 * Enqueue scripts.
 */
function vlo_theme_name_script() {
	wp_enqueue_style( 'wp-view-login-only', plugins_url( 'css/wp-view-login-only.css', __FILE__ ), array(), null );
	wp_print_styles();
}

add_action( 'login_enqueue_scripts', 'vlo_theme_name_script' );

/**
 * Redirect not authenticated user.
 */
function vlo_view_login_only() {
	global $pagenow;

	if ( 'cli' === php_sapi_name() ) {
		return;
	}

	if ( is_admin() ) {
		return;
	}

	if ( 'wp-login.php' === $pagenow ) {
		return;
	}

	if ( is_user_logged_in() ) {
		return;
	}

	auth_redirect();
}

add_action( 'init', 'vlo_view_login_only' );

/**
 * Load translation.
 */
function vlo_plugins_loaded() {
	load_plugin_textdomain( 'wp-view-login-only', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'init', 'vlo_plugins_loaded' );

/**
 * Add menubar options
 */
function vlo_add_menu() {
	add_options_page(
		'WP View Login Only',
		'WP View Login Only',
		'activate_plugins',
		'vlo',
		'vlo_options_page_content'
	);
}

add_action( 'admin_menu', 'vlo_add_menu' );

/**
 * Option page contents.
 */
function vlo_options_page_content() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ), 'wp-view-login-only' );
	}
	?>

	<div class="wrap">
		<h1><?php esc_html_e( 'WP View Login Only', 'wp-view-login-only' ); ?></h1>

		<p><?php esc_html_e( 'Enter the text to be displayed on the login page.Default message is " Welcome to this site. Please log in to continue ".', 'wp-view-login-only' ) ?></p>
		<form action="" id="vlo-menu-form" method="post">
			<?php
			wp_nonce_field( 'vlo-nonce-key', 'vlo-menu' );
			if ( esc_textarea( get_option( 'vlo-message-data' ) ) ) :
				$message = get_option( 'vlo-message-data' );
			else :
				$message = __( 'Welcome to this site. Please log in to continue', 'wp-view-login-only' );
			endif;
			?>
			<table class="form-table permalink-structure">
				<tr>
					<th>
						<label for="vlo-message-data">
							<?php esc_html_e( 'message', 'wp-view-login-only' ) ?>
						</label>
					</th>
					<td>
						<textarea name="vlo-message-data" id="vlo-message-data" cols="80" rows="10">
							<?php echo esc_textarea( $message ); ?>
						</textarea>
					</td>
				</tr>
			</table>

			<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'wp-view-login-only' ); ?>"/></p>
		</form>
	</div>
	<?php
}

/**
 * Option page contents.
 */
function vlo_add_login_message() {
	if ( ! get_option( 'vlo-message-data' ) ) :
		$message = __( 'Welcome to this site. Please log in to continue', 'wp-view-login-only' );
	else :
		$message = get_option( 'vlo-message-data' );
	endif;

	return '<p class="message error vlo-login-attention">' . esc_html( $message ) . '</p>';
}

add_filter( 'login_message', 'vlo_add_login_message' );

/**
 * Save options.
 */
function vlo_save_options() {
	if ( ! empty( $_POST['vlo-menu'] ) ) {
		if ( check_admin_referer( 'vlo-nonce-key', 'vlo-menu' ) ) {

			if ( ! empty( $_POST['vlo-message-data'] ) ) {
				$data = sanitize_text_field( wp_unslash( $_POST['vlo-message-data'] ) );
				update_option( 'vlo-message-data', $data );
				$e = new WP_Error();
				$e->add( 'error', __( 'saved the message', 'wp-view-login-only' ) );
				set_transient( 'vlo-admin-errors', $e->get_error_messages(), 10 );
			} else {
				update_option( 'vlo-message-data', '' );
			}

			//wp_safe_redirect( menu_page_url( 'vlo-menu', false ) );
		}
	}

}

add_action( 'admin_init', 'vlo_save_options' );

/**
 * Show admin notices.
 */
function vlo_admin_notices() {
	$messages = get_transient( 'vlo-admin-errors' );
	if ( $messages ) : ?>
		<div class="updated">
			<ul>
				<?php foreach ( $messages as $message ) : ?>
					<li><?php echo esc_html( $message ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	endif;
}

add_action( 'admin_notices', 'vlo_admin_notices' );
