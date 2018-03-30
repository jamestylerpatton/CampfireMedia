<?php
/**
 * The template for displaying Archive pages.
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.2
 */

$templates = array( 'archive.twig', 'index.twig' );

$context = Timber::get_context();

$context['title'] = 'Archive';
if ( is_day() ) {
	$context['title'] = 'Archive: '.get_the_date( 'D M Y' );
} else if ( is_month() ) {
	$context['title'] = 'Archive: '.get_the_date( 'M Y' );
} else if ( is_year() ) {
	$context['title'] = 'Archive: '.get_the_date( 'Y' );
} else if ( is_tag() ) {
	$context['title'] = single_tag_title( '', false );
} else if ( is_category() ) {
	$context['title'] = single_cat_title( '', false );
	array_unshift( $templates, 'archive-' . get_query_var( 'cat' ) . '.twig' );
} else if ( is_post_type_archive() ) {
	$context['title'] = post_type_archive_title( '', false ) . ' Archive';
	array_unshift( $templates, 'archive-' . get_post_type() . '.twig' );
}

if (get_post_type() === 'episodes' && get_query_var('podcast_name', null)) {
    $context['podcast'] = get_query_var('podcast_name', null);
    $podcast = get_page_by_path(get_query_var('podcast_name', null), OBJECT, 'podcasts');
    $args = array(
        'post_type' => 'episodes',
        'paged' => get_query_var('paged', null),
        'meta_query' => array(
            array(
                'key' => 'podcast',
                'value' => $podcast->ID
            )
        )
    );
    $context['posts'] = new Timber\PostQuery($args);
} else {
    $context['posts'] = new Timber\PostQuery();
}

Timber::render( $templates, $context );
