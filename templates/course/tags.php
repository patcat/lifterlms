<?php
/**
 * Course tags template
 * @author 		LifterLMS
 * @package 	LifterLMS/Templates
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

global $post;
?>

<div class="llms-meta llms-tags">
	<p><?php echo get_the_term_list( $post->ID, 'course_tag', __( '<strong>Tags:</strong> ', 'lifterlms' ), ', ', '' ); ?></p>
</div>
