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

use AgencyManager\Frontend\Carousel_Renderer;

get_header();
?>
<header class="am-archive-header">
	<h1><?php post_type_archive_title(); ?></h1>
</header>

<?php
echo Carousel_Renderer::render( 'location', 'grid', array( 'count' => -1 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Carousel_Renderer/Card_Renderer escape all dynamic values internally.
?>

<?php get_footer(); ?>
