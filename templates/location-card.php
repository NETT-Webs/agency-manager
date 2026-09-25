<?php
/**
 * @var int $post_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- this file is only ever `include`d from inside Templates::render(), a class method (not the true global scope), via extract()+include — its local variables never leak beyond that one method call, so a plugin-wide prefix would add noise without addressing any real scope-pollution risk.
$post_id  = $post_id ?? 0;
$image_id = \Nettalo\TalentLocationManagement\Frontend\Card_Renderer::get_card_image_id( $post_id, 'location' );
$city     = \Nettalo\TalentLocationManagement\Frontend\Meta_Resolver::get( $post_id, 'location', 'city' );
$type     = wp_get_post_terms( $post_id, 'location_type', array( 'fields' => 'names' ) );
$type     = is_wp_error( $type ) ? array() : $type;
?>
<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="am-location-card">
	<div class="am-location-card__media">
		<?php if ( $image_id ) : ?>
			<?php echo wp_get_attachment_image( $image_id, 'medium', false, array( 'loading' => 'lazy', 'alt' => get_the_title( $post_id ) ) ); ?>
		<?php else : ?>
			<div class="am-placeholder-media" aria-hidden="true"></div>
		<?php endif; ?>
	</div>
	<div class="am-location-card__meta">
		<h3><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
		<?php if ( ! empty( $type ) || $city ) : ?>
			<p class="am-location-card__sub">
				<?php echo esc_html( implode( ', ', $type ) ); ?>
				<?php if ( ! empty( $type ) && $city ) : ?> &mdash; <?php endif; ?>
				<?php echo esc_html( $city ); ?>
			</p>
		<?php endif; ?>
	</div>
</a>
