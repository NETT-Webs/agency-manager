<?php
/**
 * Default single Location template — used only when neither the active
 * theme nor an agency-manager/single-location.php override provides one
 * (see Frontend\Template_Loader). Deliberately plain, unbranded markup:
 * visual identity is entirely the active theme's responsibility, via the
 * CSS custom properties (--am-*) consumed in assets/css/frontend.css and/or
 * a full template override — not anything decided here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$post_id = get_the_ID();

	$fields = array(
		'city'         => __( 'City', 'agency-manager' ),
		'parking'      => __( 'Parking', 'agency-manager' ),
		'power'        => __( 'Power', 'agency-manager' ),
		'amenities'    => __( 'Facilities', 'agency-manager' ),
		'availability' => __( 'Availability', 'agency-manager' ),
	);

	$gallery_ids = \AgencyManager\Frontend\Meta_Resolver::get( $post_id, 'location', 'gallery_ids' );
	$gallery_ids = $gallery_ids ? array_filter( array_map( 'intval', array_map( 'trim', explode( ',', $gallery_ids ) ) ) ) : array();
	$map_embed   = \AgencyManager\Frontend\Meta_Resolver::get( $post_id, 'location', 'map_embed' );
	?>
	<article <?php post_class( 'am-single-location' ); ?>>
		<div class="am-single-location__media">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'large' ); ?>
			<?php else : ?>
				<div class="am-placeholder-media" aria-hidden="true"></div>
			<?php endif; ?>
		</div>

		<div class="am-single-location__body">
			<h1 class="am-single-location__title"><?php the_title(); ?></h1>

			<?php
			$terms = wp_get_post_terms( $post_id, 'location_type', array( 'fields' => 'names' ) );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) :
				?>
				<p class="am-single-location__terms"><?php echo esc_html( implode( ' · ', $terms ) ); ?></p>
			<?php endif; ?>

			<?php if ( get_the_content() ) : ?>
				<div class="am-single-location__description"><?php the_content(); ?></div>
			<?php endif; ?>

			<dl class="am-single-location__facts">
				<?php foreach ( $fields as $key => $label ) : ?>
					<?php $value = \AgencyManager\Frontend\Meta_Resolver::get( $post_id, 'location', $key ); ?>
					<?php if ( '' !== $value ) : ?>
						<div class="am-single-location__fact">
							<dt><?php echo esc_html( $label ); ?></dt>
							<dd><?php echo esc_html( $value ); ?></dd>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</dl>

			<?php if ( $map_embed ) : ?>
				<div class="am-single-location__map">
					<iframe src="<?php echo esc_url( $map_embed ); ?>" width="100%" height="360" style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"></iframe>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $gallery_ids ) ) : ?>
				<div class="am-single-location__gallery">
					<?php foreach ( $gallery_ids as $attachment_id ) : ?>
						<?php echo wp_get_attachment_image( $attachment_id, 'medium', false, array( 'loading' => 'lazy' ) ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
