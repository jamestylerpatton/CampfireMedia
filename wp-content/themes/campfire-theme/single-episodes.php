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

$episodePodcast = new Timber\Post($post->get_field('podcast'));

$userArgs = array(
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => 'podcast',
            'value' => '"'.$episodePodcast->id.'"',
            'compare' => 'LIKE'
        ),
        array(
            'key' => 'podcast',
            'value' => $episodePodcast->id,
            'compare' => '='
        )
    )
);
$userQuery = new WP_User_Query($userArgs);

$userArray = array();
foreach ($userQuery->get_results() as $userResult) {
    $userArray[] = $userResult->data->display_name;
}

$last  = array_slice($userArray, -1);
$first = join(', ', array_slice($userArray, 0, -1));
$both  = array_filter(array_merge(array($first), $last), 'strlen');

$context['hosts'] = join(' and ', $both);

if ( post_password_required( $post->ID ) ) {
    Timber::render( 'single-password.twig', $context );
} else {
    Timber::render( array( 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' ), $context );
}
