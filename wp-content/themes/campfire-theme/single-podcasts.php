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

if (get_field('apple_podcasts', $post->ID)) {
    preg_match('/id([0-9]+)\??/', get_field('apple_podcasts', $post->ID), $matches);
    if (isset($matches[1])) {
        $context['podcast_id'] = $matches[1];
    }
}

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

$userArgs = array(
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => 'podcast',
            'value' => '"'.$post->id.'"',
            'compare' => 'LIKE'
        ),
        array(
            'key' => 'podcast',
            'value' => $post->id,
            'compare' => '='
        )
    )
);
$userQuery = new WP_User_Query($userArgs);
$context['hosts'] = $userQuery->results;

if ( post_password_required( $post->ID ) ) {
    Timber::render( 'single-password.twig', $context );
} else {
    Timber::render( array( 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' ), $context );
}
