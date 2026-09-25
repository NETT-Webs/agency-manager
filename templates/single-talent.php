<?php
/**
 * Default single Talent template — used only when neither the active theme
 * nor an agency-manager/single-talent.php override provides one (see
 * Frontend\Template_Loader). Deliberately plain, unbranded markup: visual
 * identity is entirely the active theme's responsibility, via the CSS
 * custom properties (--am-*) consumed in assets/css/frontend.css and/or a
 * full template override — not anything decided here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- this file is loaded exactly once per request, as the terminal leaf of WordPress core's own `template_include` chain (see Template_Loader::maybe_override()), the same mechanism every theme's single.php uses — its local variables are conventionally unprefixed throughout WordPress core and every theme for exactly this reason, and it may also be copied verbatim into a theme's agency-manager/ override folder, where theme developers expect ordinary WordPress template variable names, not a plugin-specific prefix.

get_header();

while ( have_posts() ) :
	the_post();

	$post_id = get_the_ID();

	$fields = array(
		'city'          => __( 'City', 'nettwebs-talent-location-management' ),
		'age'           => __( 'Age', 'nettwebs-talent-location-management' ),
		'availability'  => __( 'Availability', 'nettwebs-talent-location-management' ),
		'height'        => __( 'Height', 'nettwebs-talent-location-management' ),
		'body_type'     => __( 'Body Type', 'nettwebs-talent-location-management' ),
		'hair_color'    => __( 'Hair Colour', 'nettwebs-talent-location-management' ),
		'eye_color'     => __( 'Eye Colour', 'nettwebs-talent-location-management' ),
		'measurements'  => __( 'Measurements', 'nettwebs-talent-location-management' ),
		'languages'     => __( 'Languages', 'nettwebs-talent-location-management' ),
		'skills'        => __( 'Skills', 'nettwebs-talent-location-management' ),
		'experience'    => __( 'Experience', 'nettwebs-talent-location-management' ),
	);

	$social = array(
		'social_instagram' => __( 'Instagram', 'nettwebs-talent-location-management' ),
		'social_facebook'  => __( 'Facebook', 'nettwebs-talent-location-management' ),
		'social_tiktok'    => __( 'TikTok', 'nettwebs-talent-location-management' ),
		'social_website'   => __( 'Website', 'nettwebs-talent-location-management' ),
	);

	$gallery_ids = \Nettalo\TalentLocationManagement\Frontend\Meta_Resolver::get( $post_id, 'talent', 'gallery_ids' );
	$gallery_ids = $gallery_ids ? array_filter( array_map( 'intval', array_map( 'trim', explode( ',', $gallery_ids ) ) ) ) : array();
	$video_url   = \Nettalo\TalentLocationManagement\Frontend\Meta_Resolver::get( $post_id, 'talent', 'video_url' );
	?>
	<article <?php post_class( 'am-single-talent' ); ?>>
		<div class="am-single-talent__media">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'large' ); ?>
			<?php else : ?>
				<div class="am-placeholder-media" aria-hidden="true"></div>
			<?php endif; ?>
		</div>

		<div class="am-single-talent__body">
			<h1 class="am-single-talent__title"><?php the_title(); ?></h1>

			<?php
			$terms = wp_get_post_terms( $post_id, array( 'talent_category', 'talent_group' ), array( 'fields' => 'names' ) );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) :
				?>
				<p class="am-single-talent__terms"><?php echo esc_html( implode( ' · ', $terms ) ); ?></p>
			<?php endif; ?>

			<?php if ( get_the_content() ) : ?>
				<div class="am-single-talent__bio"><?php the_content(); ?></div>
			<?php endif; ?>

			<dl class="am-single-talent__facts">
				<?php foreach ( $fields as $key => $label ) : ?>
					<?php $value = \Nettalo\TalentLocationManagement\Frontend\Meta_Resolver::get( $post_id, 'talent', $key ); ?>
					<?php if ( '' !== $value ) : ?>
						<div class="am-single-talent__fact">
							<dt><?php echo esc_html( $label ); ?></dt>
							<dd><?php echo esc_html( $value ); ?></dd>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</dl>

			<?php if ( $video_url ) : ?>
				<p class="am-single-talent__video"><a href="<?php echo esc_url( $video_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Watch Reel', 'nettwebs-talent-location-management' ); ?></a></p>
			<?php endif; ?>

			<?php
			$social_links = array();
			foreach ( $social as $key => $label ) {
				$url = \Nettalo\TalentLocationManagement\Frontend\Meta_Resolver::get( $post_id, 'talent', $key );
				if ( $url ) {
					$social_links[ $label ] = $url;
				}
			}
			?>
			<?php if ( ! empty( $social_links ) ) : ?>
				<ul class="am-single-talent__social">
					<?php foreach ( $social_links as $label => $url ) : ?>
						<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $gallery_ids ) ) : ?>
				<div class="am-single-talent__gallery">
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
