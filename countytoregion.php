<?php
/**
 * Plugin Name: Job Region Tag from Geolocation County
 * Plugin URI: https://github.com/joelgratcyk
 * Description: This plugin automatically adds a _job_region tag to job_listing posts based on their geolocation_county meta value, but only for job listings in the US.
 * Version: 1.0
 * Author: Joel Gratcyk
 * Author URI: https://joel.gr
 * License: GPL2
 */

add_action('save_post_job_listing', 'add_job_region_tag', 10, 3);

function add_job_region_tag($post_id, $post, $update) {
    // Only run on job listings and when updating an existing one
    if ($post->post_type !== 'job_listing' || !$update || wp_is_post_revision($post_id)) {
        return;
    }

    // Check if the job listing is in the US
    $country_short = get_post_meta($post_id, 'geolocation_country_short', true);
    if ($country_short !== 'US') {
        return;
    }

    // Get the geolocation_county meta value
    $county = get_post_meta($post_id, 'geolocation_county', true);

    // Get the parent term based on the geolocation_state_long meta value
    $state_long = get_post_meta($post_id, 'geolocation_state_long', true);
    $parent_term = get_term_by('name', $state_long, 'job_listing_region');
    $parent_term_id = $parent_term ? $parent_term->term_id : 0;

    // Add the county as a child term of the parent term
    if ($parent_term_id && $county) {
        $county_tag = sanitize_title($county);
        $county_term = term_exists($county_tag, 'job_listing_region', $parent_term_id);
        if (!$county_term) {
            wp_insert_term($county, 'job_listing_region', array(
                'slug' => $county_tag,
                'parent' => $parent_term_id
            ));
            $county_term = term_exists($county_tag, 'job_listing_region', $parent_term_id);
        }
        wp_set_post_terms($post_id, array($parent_term_id, $county_term['term_id']), 'job_listing_region', true);
    }
}
