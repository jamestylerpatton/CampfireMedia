<?php
/**
 * The Template for displaying all single posts
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context = Timber::get_context();
$post = Timber::query_post();
$context['post'] = $post;

if ($post->post_type === 'podcasts') {
    $args = array(
        'post_type' => 'episodes',
        'posts_per_page' => 3,
        'meta_query' => array(
            array(
                'key' => 'podcast',
                'value' => $post->id
            )
        )
    );
    $context['episodes'] = new Timber\PostQuery($args);
}

if ( post_password_required( $post->ID ) ) {
	Timber::render( 'single-password.twig', $context );
} else {
	Timber::render( array( 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' ), $context );
}
