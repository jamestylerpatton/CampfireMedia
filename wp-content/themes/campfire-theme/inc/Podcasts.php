<?php

class Podcasts
{
    function __construct()
    {
        $this->isAdmin = current_user_can( 'edit_dashboard' );

        add_action('init', array($this,'create_post_type'));
        add_filter('pre_get_posts', array($this, 'restrict_user_podcasts'));
        add_filter('pre_get_posts', array($this, 'podcast_archive_args'));
        add_filter('views_edit-podcasts', array($this, 'update_podcast_view_count'), 10, 1);

        add_action('import_rss_feed', array($this, 'import_episodes_cron'));

        if ( ! wp_next_scheduled( 'import_rss_feed' ) ) {
            wp_schedule_event( time(), 'hourly', 'import_rss_feed' );
        }

        add_action('save_post', array($this, 'import_new_podcast_posts'), 10, 2);

        add_filter('manage_podcasts_posts_columns', array($this, 'podcast_author_head'));
        add_action('manage_podcasts_posts_custom_column', array($this, 'podcast_author_content'), 10, 2);
    }

    function create_post_type() {
        register_post_type( 'podcasts', array(
            'labels' => array(
                'name' => __('Podcast'),
                'singular_name' => __('Podcast'),
            ),
            'supports' => array(
                'title', 'editor', 'author', 'thumbnail'
            ),
            'public' => true,
            'show_in_menu' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-flag',
            'has_archive' => true
        ) );
    }

    /**
     * Users should only be able to see their own podcast(s)
     * @param  object $query
     * @return object $query
     */
    function restrict_user_podcasts($query) {
        global $pagenow;
     
        if ('edit.php' != $pagenow ||
            !$query->is_admin ||
            $this->isAdmin ||
            $query->query_vars['post_type'] !== 'podcasts') {
            return $query;
        }
     
        global $user_ID;

        $podcast = get_field('podcast', 'user_'.$user_ID);

        if (!$podcast) {
            $query->set('post__in', [0]);
            return $query;
        }

        $podcastIds = array();
        foreach ($podcast as $podcastItem) {
            $podcastIds[] = $podcastItem->ID;
        }

        $query->set('post__in', $podcastIds);

        return $query;
    }

    /**
     * Raise limit of podcasts shown on podcast archive pages
     * @param  [type] $query [description]
     * @return [type]        [description]
     */
    function podcast_archive_args($query) {
        global $pagenow;
        
        if (is_archive() &&
            !is_admin() &&
            $query->query_vars['post_type'] === 'podcasts') {

            $query->set('posts_per_page', -1);

            return $query;
        }

        return $query;
    }

    /**
     * Update view count on podcasts page
     * @param  $views
     * @return $views
     */
    function update_podcast_view_count($views) {
        if ($this->isAdmin) {
            return $views;
        }
        return;
    }

    /**
     * Import RSS feed from all podcasts daily
     */
    function import_episodes_cron() {
        $query = get_posts(array(
            'post_type' => 'podcasts',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ));

        foreach ($query as $podcast) {
            $this->import_episodes($podcast);
        }
    }

    /**
     * Import RSS feed from new podcasts
     */
    function import_new_podcast_posts($post_id, $post) {
        global $pagenow;
        $post_type = get_post_type($post_id);

        if ($pagenow === 'post.php' && $post_type === 'podcasts') {
            $this->import_episodes($post);
        }
    }

    function import_episodes($podcast)
    {
        $post_updated = get_post_meta($podcast->ID, 'rss_updated', true);

        if (!get_field('rss_feed_url', $podcast->ID)) {
            return;
        }

        $rss = get_field('rss_feed_url', $podcast->ID);

        $xml = @file_get_contents($rss);

        if (!$xml) {
            return;
        }

        $xmlElement = new SimpleXMLElement($xml);

        foreach ($xmlElement->channel->item as $episode) {
            $xml_pub_date = (string) $episode->pubDate;
            $pub_date = new DateTime($xml_pub_date);

            /**
             * When we hit a post that was published before the post_updated var:
             * break the loop
             */
            if ($post_updated && $pub_date->format('Y-m-d H:i:s') < $post_updated) {
                break;
            }

            $title = (string) $episode->title;
            $content = (string) $episode->children('content', true)->encoded;
            $summary = (string) $episode->children('itunes', true)->summary;

            $rss_link = (string) $episode->link;
            $sound_file = (string) $episode->enclosure['url'];
            // $image = (string) $episode->children('itunes', true)->image->attributes()->href;
            $season_num = (string) $episode->children('itunes', true)->season;
            $episode_num = (string) $episode->children('itunes', true)->episode;

            $insert_post = wp_insert_post(array(
                'post_author' => 1,
                'post_date_gmt' => $pub_date->format('Y-m-d H:i:s'),
                'post_content' => $content,
                'post_title' => $title,
                'post_excerpt' => $summary,
                'post_status' => 'publish',
                'post_type' => 'episodes',
            ));

            update_post_meta($insert_post, 'podcast', $podcast->ID);
            update_post_meta($insert_post, 'rss_link', $rss_link);
            update_post_meta($insert_post, 'mp3_link', $sound_file);

            if ($season_num) {
                update_post_meta($insert_post, 'season', $season_num);
            }
            if ($episode_num) {
                update_post_meta($insert_post, 'episode', $episode_num);
            }

            // Set episode image to podcast image by default
            $podcast_thumbnail = get_post_thumbnail_id( $podcast->ID );
            set_post_thumbnail( $insert_post, $podcast_thumbnail );
        }

        update_post_meta($podcast->ID, 'rss_updated', gmdate('Y-m-d H:i:s'));
    }

    /**
     * Display Author filter for podcasts
     * @return [type] [description]
     */
    function podcast_author_head($defaults) {
        $defaults['users'] = 'Hosts';
        return $defaults;
    }
    function podcast_author_content($column_name, $post_ID) {
        if ($column_name == 'users') {
            $args = array(
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => 'podcast',
                        'value' => '"'.$post_ID.'"',
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'podcast',
                        'value' => $post_ID,
                        'compare' => '='
                    )
                )
            );

            // The Query
            $user_query = new WP_User_Query( $args );

            // User Loop
            if ( ! empty( $user_query->get_results() ) ) {
                $podcastUsers = [];
                foreach ( $user_query->get_results() as $user ) {
                    $podcastUsers[] = $user->display_name;
                }
                echo implode($podcastUsers, ', ');
            } else {
                echo 'No users found.';
            }
        }
    }
}

new Podcasts();
