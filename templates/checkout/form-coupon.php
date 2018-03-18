<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Coupon area of the checkout form
 *
 * @author 		LifterLMS
 * @package 	LifterLMS/Templates
 */

// don't display if the plan is marked as free
if ( isset( $plan ) && $plan->is_free() ) {
	return;
}
?>
<div class="llms-coupon-wrapper">

	<?php if ( empty( $coupon ) ) : ?>

		<?php _e( 'Have a coupon?', 'lifterlms' ); ?>
		<a href="#llms-coupon-toggle"><?php _e( 'Click here to enter your code', 'lifterlms' ); ?></a>

		<div class="llms-coupon-entry llms-form-fields flush">

			<div class="llms-coupon-messages"></div>

			<?php llms_form_field( array(

				'columns' => 12,
				'id' => 'llms_coupon_code',
				'placeholder' => __( 'Coupon Code', 'lifterlms' ),
				'last_column' => true,
				'required' => false,
				'type'  => 'text',

			) ); ?>
			<?php llms_form_field( array(

				'columns' => 12,
				'classes' => 'btn',
				'id' => 'llms-apply-coupon',
				'value' => __( 'Apply Coupon', 'lifterlms' ),
				'last_column' => true,
				'required' => false,
				'type'  => 'button',

			) ); ?>
		</div>

	<?php else : ?>

		<?php llms_print_notice( sprintf( __( 'Coupon code "%s" has been applied to your order.', 'lifterlms' ), $coupon->get( 'title' ) ), 'success' ); ?>

		<div class="llms-form-fields flush">
			<?php llms_form_field( array(

				'columns' => 12,
				'classes' => 'btn',
				'id' => 'llms-remove-coupon',
				'value' => __( 'Remove Coupon', 'lifterlms' ),
				'last_column' => true,
				'required' => false,
				'type'  => 'button',

			) ); ?>

		</div>

		<input name="llms_coupon_code" type="hidden" value="<?php echo $coupon->get( 'title' ); ?>">

	<?php endif; ?>

</div>
