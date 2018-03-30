<?php
ini_set('max_execution_time', 300);

class ImageUpload
{
    protected $args;

    function __construct(array $args)
    {
        $defaults = array(
            'url' => null,
            'post_id' => null,
            'image_name' => 'url-upload'
        );

        $this->args = array_merge($defaults, $args);

        return $this->set_featured_image_from_url();
    }

    function set_featured_image_from_url()
    {
        $options = $this->args;

        if (!$options && !$options['url'] && !$options['post_id']) {
            return;
        }

        $post_id = $options['post_id'];

        // Add Featured Image to Post
        $image_url        = $options['url']; // Define the image URL here
        $image_name       = $options['image_name'];
        $upload_dir       = wp_upload_dir(); // Set upload folder
        $image_data       = file_get_contents($image_url); // Get image data
        $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
        $filename         = basename( $unique_file_name ); // Create image file name

        // Check folder permission and define file location
        if( wp_mkdir_p( $upload_dir['path'] ) ) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        // Create the image  file on the server
        file_put_contents( $file, $image_data );

        // Check image file type
        $wp_filetype = wp_check_filetype( $filename, null );

        // Set attachment data
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => sanitize_file_name( $filename ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        // Create the attachment
        $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

        // Include image.php
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Define attachment metadata
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

        // Assign metadata to attachment
        wp_update_attachment_metadata( $attach_id, $attach_data );

        // And finally assign featured image to post
        set_post_thumbnail( $post_id, $attach_id );

        return $attach_id;
    }
}