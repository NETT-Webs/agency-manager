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

get_header();

while ( have_posts() ) :
	the_post();

	$post_id = get_the_ID();

	$fields = array(
		'city'          => __( 'City', 'agency-manager' ),
		'age'           => __( 'Age', 'agency-manager' ),
		'availability'  => __( 'Availability', 'agency-manager' ),
		'height'        => __( 'Height', 'agency-manager' ),
		'body_type'     => __( 'Body Type', 'agency-manager' ),
		'hair_color'    => __( 'Hair Colour', 'agency-manager' ),
		'eye_color'     => __( 'Eye Colour', 'agency-manager' ),
		'measurements'  => __( 'Measurements', 'agency-manager' ),
		'languages'     => __( 'Languages', 'agency-manager' ),
		'skills'        => __( 'Skills', 'agency-manager' ),
		'experience'    => __( 'Experience', 'agency-manager' ),
	);

	$social = array(
		'social_instagram' => __( 'Instagram', 'agency-manager' ),
		'social_facebook'  => __( 'Facebook', 'agency-manager' ),
		'social_tiktok'    => __( 'TikTok', 'agency-manager' ),
		'social_website'   => __( 'Website', 'agency-manager' ),
	);

	$gallery_ids = \AgencyManager\Frontend\Meta_Resolver::get( $post_id, 'talent', 'gallery_ids' );
	$gallery_ids = $gallery_ids ? array_filter( array_map( 'intval', array_map( 'trim', explode( ',', $gallery_ids ) ) ) ) : array();
	$video_url   = \AgencyManager\Frontend\Meta_Resolver::get( $post_id, 'talent', 'video_url' );
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
					<?php $value = \AgencyManager\Frontend\Meta_Resolver::get( $post_id, 'talent', $key ); ?>
					<?php if ( '' !== $value ) : ?>
						<div class="am-single-talent__fact">
							<dt><?php echo esc_html( $label ); ?></dt>
							<dd><?php echo esc_html( $value ); ?></dd>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</dl>

			<?php if ( $video_url ) : ?>
				<p class="am-single-talent__video"><a href="<?php echo esc_url( $video_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Watch Reel', 'agency-manager' ); ?></a></p>
			<?php endif; ?>

			<?php
			$social_links = array();
			foreach ( $social as $key => $label ) {
				$url = \AgencyManager\Frontend\Meta_Resolver::get( $post_id, 'talent', $key );
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
