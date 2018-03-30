<?php

class Episodes
{
    protected $isAdmin;

    function __construct()
    {
        $this->isAdmin = current_user_can( 'edit_dashboard' );

        add_action('init', array($this,'create_post_type'));
        add_filter('pre_get_posts', array($this, 'restrict_user_episodes'));
        add_filter('pre_get_posts', array($this, 'episode_archive_args'));
        add_filter('views_edit-episodes' , array($this, 'update_episodes_view_count'), 10, 1);
        add_action('save_post', array($this, 'save_post_meta'), 10, 2);

        add_action('init', array($this, 'archive_page_rewrite'));
        add_filter('query_vars', array($this, 'archive_page_query_vars'));

        add_filter('manage_episodes_posts_columns', array($this, 'episode_podcast_head'));
        add_action('manage_episodes_posts_custom_column', array($this, 'episode_podcast_content'), 10, 2);
    }

    function create_post_type() {
        register_post_type( 'episodes', array(
            'labels' => array(
                'name' => __('Episodes'),
                'singular_name' => __('Episode'),
            ),
            'supports' => array(
                'title', 'editor', 'author', 'thumbnail'
            ),
            'public' => true,
            'show_in_menu' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-media-audio',
            'has_archive' => true
        ) );
    }

    /**
     * Users should only be able to see their own podcast(s)
     * @param  object $query
     * @return object $query
     */
    function restrict_user_episodes($query)
    {
        global $pagenow;
     
        if ('edit.php' != $pagenow ||
            !$query->is_admin ||
            $this->isAdmin ||
            $query->query_vars['post_type'] !== 'episodes') {
            return $query;
        }
     
        global $user_ID;

        $podcasts = get_field('podcast', 'user_'.$user_ID);
        $podcastIds = [];
        foreach ($podcasts as $podcastItem) {
            $podcastIds[] = $podcastItem->ID;
        }

        $query->set('meta_query', array(
            array(
                'key' => 'podcast',
                'value' => $podcastIds,
                'compare' => 'IN'
            )
        ));

        return $query;
    }

    /**
     * Raise limit of episodes shown on episode archive pages
     * @param  [type] $query [description]
     * @return [type]        [description]
     */
    function episode_archive_args($query) {
        global $pagenow;
        
        if (is_archive() &&
            !is_admin() &&
            $query->query_vars['post_type'] === 'episodes') {

            $query->set('posts_per_page', 20);

            return $query;
        }

        return $query;
    }

    /**
     * Remove view count on episodes page
     * @param  $views
     * @return $views
     */
    function update_episodes_view_count($views) {
        if ($this->isAdmin) {
            return $views;
        }
        return;
    }

    /**
     * Update podcast post_meta on publish
     * @param  $views
     * @return $views
     */
    function save_post_meta($post_id, $post) {
        global $user_ID;
        global $pagenow;
        $post_type = get_post_type($post_id);

        if ($pagenow === 'post.php' && $post_type === 'episodes' && !$this->isAdmin) {
            $podcast = get_field('podcast', 'user_'.$user_ID);

            update_field('podcast', $podcast->ID, $post_id);
        }
    }

    /**
     * Add podcast episode archive links
     * ex: /podcasts/lizard-people/episodes/
     */
    function archive_page_rewrite() {
        add_rewrite_rule(
            '^podcasts\/([^/]+)\/episodes\/page/?([0-9]{1,})/?$',
            'index.php?post_type=episodes&podcast_name=$matches[1]&paged=$matches[2]',
            'top' );

        add_rewrite_rule(
            '^podcasts\/([^/]+)\/episodes\/?$',
            'index.php?post_type=episodes&podcast_name=$matches[1]',
            'top' );
    }
    function archive_page_query_vars($query_vars) {
        $query_vars[] = 'podcast_name';
        return $query_vars;
    }

    /**
     * Display dropdown filter for podcasts
     * @return [type] [description]
     */
    function episode_podcast_head($defaults) {
        $defaults['podcast'] = 'Podcast';
        return $defaults;
    }
    function episode_podcast_content($column_name, $post_ID) {
        if ($column_name == 'podcast') {
            echo get_the_title(get_post_meta($post_ID, 'podcast', true));
        }
    }
}

new Episodes();
