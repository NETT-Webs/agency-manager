<?php
/**
 * @var array $config
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- this file is only ever `include`d from inside Templates::render(), a class method (not the true global scope), via extract()+include — its local variables never leak beyond that one method call, so a plugin-wide prefix would add noise without addressing any real scope-pollution risk.
$config = $config ?? array();
?>
<div class="am-location-card am-scouting-card">
	<div class="am-location-card__media">
		<?php if ( ! empty( $config['image_id'] ) ) : ?>
			<?php echo wp_get_attachment_image( (int) $config['image_id'], 'medium' ); ?>
		<?php else : ?>
			<div class="am-placeholder-media" aria-hidden="true"></div>
		<?php endif; ?>
		<?php if ( ! empty( $config['badge'] ) ) : ?>
			<span class="am-scouting-card__badge"><?php echo esc_html( $config['badge'] ); ?></span>
		<?php endif; ?>
	</div>
	<div class="am-location-card__meta">
		<h3><?php esc_html_e( 'Location Partner', 'nettwebs-talent-location-management' ); ?></h3>
		<p class="am-scouting-card__status"><?php esc_html_e( 'Applications Open', 'nettwebs-talent-location-management' ); ?></p>
		<?php if ( ! empty( $config['button_text'] ) ) : ?>
			<a href="<?php echo esc_url( ! empty( $config['button_link'] ) ? $config['button_link'] : home_url( '/' ) ); ?>" class="am-btn am-btn--outline"><?php echo esc_html( $config['button_text'] ); ?></a>
		<?php endif; ?>
	</div>
</div>
