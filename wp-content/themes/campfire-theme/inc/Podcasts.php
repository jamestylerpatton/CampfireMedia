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
            wp_schedule_event( time(), 'twicedaily', 'import_rss_feed' );
        }

        add_action('save_post', array($this, 'import_new_podcast_posts'), 10, 2);

        // add_action('wp', array($this,'import_podcasts'));
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
            // 'capability_type' => 'podcast',
            // 'capabilities' => array(
            //     'publish_posts' => 'publish_podcasts',
            //     'edit_posts' => 'edit_podcasts',
            //     'edit_others_posts' => 'edit_others_podcasts',
            //     'delete_posts' => 'delete_podcasts',
            //     'delete_others_posts' => 'delete_others_podcasts',
            //     'read_private_posts' => 'read_private_podcasts',
            //     'edit_post' => 'edit_podcast',
            //     'delete_post' => 'delete_podcast',
            //     'read_post' => 'read_podcast',
            // ),
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

        $query->set('p', $podcast->ID);

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

    /*function import_podcasts() {
        $podcasts = [ ['name' => 'Choose Your Own Religion', 'url' => 'http://chooseyourownreligion.libsyn.com/rss'], ['name' => 'Cheers, Stomps & Whistles', 'url' => 'http://cheersstompswhistles.libsyn.com/rss'], ['name' => 'Lizard People', 'url' => 'http://lizardpeopleshow.libsyn.com/rss'], ['name' => 'Honey', 'url' => 'http://honey.libsyn.com/rss'], ['name' => 'What the F**K Do I Do with My Jewish Hair?!', 'url' => 'http://wtfdidwmjh.libsyn.com/rss'], ['name' => 'Under the Top Part of a Boat', 'url' => 'http://uttpoab.libsyn.com/rss'], ['name' => 'My Name is Weezer', 'url' => 'http://mynameisweezer.libsyn.com/rss'], ['name' => 'TBToonz', 'url' => 'http://tbtoonz.libsyn.com/rss'], ['name' => 'In Defense', 'url' => 'http://indefense.libsyn.com/rss'], ['name' => 'The Parrothead Podcast', 'url' => 'http://parrotheadpodcast.libsyn.com/rss'], ['name' => 'Playing Favorites with Shane Lennon', 'url' => 'http://playingfavorites.libsyn.com/rss'], ['name' => 'Learnt Up', 'url' => 'http://learntup.libsyn.com/rss'], ['name' => 'Let\'s Fall In Love', 'url' => 'http://letsfallinlove.libsyn.com/rss'], ['name' => 'We Rebel', 'url' => 'http://werebel.libsyn.com/rss'], ['name' => 'Welcome To The Clambake', 'url' => 'http://welcometotheclambake.libsyn.com/rss'], ['name' => 'Inside the Disney Vault', 'url' => 'http://disneyvault.libsyn.com/rss'], ['name' => 'Under Her Eye', 'url' => 'http://underhereye.libsyn.com/rss'], ['name' => 'Extra Extra', 'url' => 'http://extraextra.libsyn.com/rss'], ['name' => 'Trust the Bachelor Process', 'url' => 'http://bachelorprocess.libsyn.com/rss'], ['name' => 'Hella In Your Thirties', 'url' => 'http://hellathirties.libsyn.com/rss'], ['name' => 'Kar Dishin\' It: All Things Kardashian', 'url' => 'http://kardishinit.libsyn.com/rss'], ['name' => 'I Burn Everything', 'url' => 'http://iburneverything.libsyn.com/rss'], ['name' => 'That\'s My Story, Period.', 'url' => 'http://thatsmystory.libsyn.com/rss'], ['name' => 'Everybody Up: A Discussion On Coaching Improv', 'url' => 'http://everybodyup.libsyn.com/rss'], ['name' => 'Nintendo Cartridge Society', 'url' => 'http://nintendo.libsyn.com/rss'], ['name' => 'Broad Jobs', 'url' => 'http://broadjobs.libsyn.com/rss'], ['name' => 'Mouth Feelings', 'url' => 'http://mouthfeelings.libsyn.com/rss'], ['name' => 'Same Day Shipping', 'url' => 'http://samedayshipping.libsyn.com/rss'], ['name' => 'ScaryTown', 'url' => 'http://scarytown.libsyn.com/rss'] ];
        foreach ($podcasts as $podcast) {
            $title = $podcast['name'];
            $url = $podcast['url'];

            $xml = @file_get_contents($url);

            if (!$xml) {
                continue;
            }

            $xmlElement = new SimpleXMLElement($xml);

            $content = (string) $xmlElement->channel->description;

            $insert_post = wp_insert_post(array(
                'post_author' => 1,
                'post_content' => $content,
                'post_title' => $title,
                'post_status' => 'publish',
                'post_type' => 'podcasts',
            ));

            update_post_meta($insert_post, 'rss_feed_url', $url);

            $image_url = (string) $xmlElement->channel->image->url;
            $image_name = (string) $xmlElement->channel->image->title;

            $attachment = new ImageUpload(array(
                'url' => $image_url,
                'post_id' => $insert_post,
                'image_name' => $image_name . '.jpg'
            ));
        }
    }*/
}

new Podcasts();
