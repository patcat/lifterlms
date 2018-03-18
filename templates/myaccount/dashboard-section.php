<?php
/**
 * Section template for dashboard index
 * @since    3.14.0
 * @version  3.14.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

?>

<section class="llms-sd-section <?php echo $slug; ?>">

	<h3 class="llms-sd-section-title">
		<?php echo apply_filters( 'lifterlms_' . $action . '_title', $title ); ?>
	</h3>

	<?php do_action( 'lifterlms_before_' . $action ); ?>

	<?php echo $content; ?>

	<?php if ( $more ) : ?>
		<footer class="llms-sd-section-footer">
			<a class="btn" href="<?php echo esc_url( $more['url'] ); ?>"><?php echo $more['text']; ?></a>
		</footer>
	<?php endif; ?>

	<?php do_action( 'lifterlms_before_' . $action ); ?>

</section>
