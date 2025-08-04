<?php
if (function_exists('remove_action')) {

    add_action('template_redirect', function() {
        if (is_attachment()) {
            global $post;
            if ($post && $post->post_parent) {
                wp_redirect(get_permalink($post->post_parent), 301);
                exit;
            } else {
                wp_redirect(home_url('/'), 301);
                exit;
            }
        }
    });

    add_action('template_redirect', function() {
        $path = parse_url(home_url($_SERVER['REQUEST_URI']), PHP_URL_PATH);
        if (strpos($path, '/author/') !== false || is_date()) {
            wp_redirect(home_url('/'), 301);
            exit;
        }
    });

    remove_action('wp_head', 'wp_shortlink_wp_head', 10);
    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    remove_action('template_redirect', 'rest_output_link_header', 11);
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wp_resource_hints', 2);
    remove_action('wp_head', 'feed_links_extra', 3);
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');

    add_filter('wp_headers', function($headers) {
        if (isset($headers['X-Pingback'])) {
            unset($headers['X-Pingback']);
        }
        return $headers;
    });

    add_action('template_redirect', function() {
        if (is_feed() && !is_home() && !is_front_page()) {
            status_header(410);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Feed not available.';
            exit;
        }
    });

    add_filter('rest_authentication_errors', function($result) {
        if (!empty($result)) return $result;

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';

        // Allow this specific REST route
        if (strpos($request_uri, '/wp-json/blp/v1/products') !== false) {
            return true;
        }

        // Otherwise, defer to default behavior
        return $result;
    }, 5);

    if (has_filter('perfmatters_rest_api_exceptions')) {
        add_filter('perfmatters_rest_api_exceptions', function($exceptions) {
            $exceptions[] = 'blp/v1/products';
            return $exceptions;
        });
    }


    add_filter('robots_txt', function($output, $public) {
        if ($public) {
            $output .= "Disallow: /wp-json/\n";
        }
        return $output;
    }, 10, 2);

    add_filter('xmlrpc_enabled', '__return_false');

    add_filter('rest_endpoints', function($endpoints) {
        if (isset($endpoints['/xmlrpc.php'])) {
            unset($endpoints['/xmlrpc.php']);
        }
        return $endpoints;
    });
}
