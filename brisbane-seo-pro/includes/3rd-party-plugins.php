<?php

// WOOCOMMERCE
add_filter('woocommerce_disable_output', '__return_true');


// CATCHALL FOR ANY OUTPUTS
add_action('template_redirect', function() {
    ob_start(function ($buffer) {
        return preg_replace_callback('/<meta\s+name=["\']robots["\']\s+content=["\'][^"\']*["\']\s*(data-bsp=["\']1["\'])?\s*\/?>/i', function($matches) {
            return isset($matches[1]) ? $matches[0] : '';
        }, $buffer);
    });
}, 0);


// REMOVE DEFAULT WORDPRESS STUFF
add_action('init', function() {
    // WordPress core cleanup
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rest_output_link_wp_head');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');
    remove_action('wp_head', 'rel_canonical');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
    remove_action('wp_head', 'index_rel_link');
    remove_action('wp_head', 'parent_post_rel_link', 10);
    remove_action('wp_head', 'start_post_rel_link', 10);
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'wp_robots', 1);
    remove_action('template_redirect', 'redirect_canonical');
	remove_action('wp_head', '_wp_render_title_tag', 1);
	remove_action('wp_head', 'rel_alternate_hreflang', 10);
	remove_action('wp_head', 'wpml_hreflang_tags');
}, 20);


// GRAVITY FORMS HEADER CLEANUP
if (get_option('bsp_enable_gravityforms_cleanup') === '1') {

    add_filter('gform_init_scripts_footer', '__return_true');

    add_action('template_redirect', function () {
        ob_start(function ($html) {
            if (!is_singular()) return $html;
            global $post;
            if (!isset($post->post_content) || !has_shortcode($post->post_content, 'gravityform')) return $html;

            return preg_replace('#<script[^>]+src="data:text/javascript;base64,[^"]+"[^>]*></script>#i', '', $html);
        });
    });

}









