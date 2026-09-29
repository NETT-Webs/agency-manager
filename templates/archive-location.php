<?php
/**
 * Default Location archive template — used only when neither the active
 * theme nor an agency-manager/archive-location.php override provides one
 * (see Frontend\Template_Loader). Reuses Carousel_Renderer so the archive
 * grid is identical to [location_grid] — one rendering implementation, not
 * a second copy of card markup. Deliberately simple (all entries, no
 * pagination) — a baseline fallback, not a design showcase.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Nettalo\TalentLocationManagement\Frontend\Carousel_Renderer;

get_header();
?>
<header class="am-archive-header">
	<h1><?php post_type_archive_title(); ?></h1>
</header>

<?php
// Carousel_Renderer::render() returns fully pre-escaped HTML — every
// dynamic value it outputs is escaped at its actual point of output,
// several calls deep (this echo is not where escaping needs to happen):
// its own wrapper markup uses esc_attr()/esc_attr__() (class names, the
// aria-labels, the inline --am-columns style), and the card markup it
// delegates to (templates/location-card.php, templates/location-placeholder-card.php)
// uses esc_url() for links, esc_html()/esc_html_e() for all text, and
// wp_get_attachment_image() (WordPress core's own safe image markup
// generator) for images. See docs/REBRAND.md for the full escaping audit.
echo Carousel_Renderer::render( 'location', 'grid', array( 'count' => -1 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped HTML, see comment above.
?>

<?php get_footer(); ?>
