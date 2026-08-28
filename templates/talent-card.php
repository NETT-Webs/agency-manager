<?php
/**
 * @var int $post_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id  = $post_id ?? 0;
$image_id = \AgencyManager\Frontend\Card_Renderer::get_card_image_id( $post_id, 'talent' );
$city     = \AgencyManager\Frontend\Meta_Resolver::get( $post_id, 'talent', 'city' );
$category = wp_get_post_terms( $post_id, 'talent_category', array( 'fields' => 'names' ) );
$category = is_wp_error( $category ) ? array() : $category;
?>
<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="am-talent-card">
	<div class="am-talent-card__media">
		<?php if ( $image_id ) : ?>
			<?php echo wp_get_attachment_image( $image_id, 'medium', false, array( 'loading' => 'lazy', 'alt' => get_the_title( $post_id ) ) ); ?>
		<?php else : ?>
			<div class="am-placeholder-media" aria-hidden="true"></div>
		<?php endif; ?>
	</div>
	<div class="am-talent-card__meta">
		<h3><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
		<?php if ( ! empty( $category ) || $city ) : ?>
			<p class="am-talent-card__sub">
				<?php echo esc_html( implode( ', ', $category ) ); ?>
				<?php if ( ! empty( $category ) && $city ) : ?> &mdash; <?php endif; ?>
				<?php echo esc_html( $city ); ?>
			</p>
		<?php endif; ?>
	</div>
</a>
