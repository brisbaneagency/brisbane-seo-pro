<?php

function bsp_generate_sitemap_cache_key($sitemap_name = 'index', $page = 1) {
    return "bsp_sitemap_cache_{$sitemap_name}_page_{$page}";
}

function bsp_get_sitemap_cache($sitemap_name = 'index', $page = 1) {
    $cache_key = bsp_generate_sitemap_cache_key($sitemap_name, $page);
    $cache = get_transient($cache_key);
    if ($cache !== false) {
        return $cache;
    }
    return false;
}

function bsp_set_sitemap_cache($content, $sitemap_name = 'index', $page = 1, $ttl = DAY_IN_SECONDS * 7) {
    $cache_key = bsp_generate_sitemap_cache_key($sitemap_name, $page);
    set_transient($cache_key, $content, $ttl);
}

function bsp_clear_sitemap_cache() {
    global $wpdb;

    $transient_prefix = 'bsp_sitemap_cache_';
    $transients = [];

    $db_transients = $wpdb->get_col(
        "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '_transient_{$transient_prefix}%'"
    );
    foreach ($db_transients as $option_name) {
        $transients[] = str_replace('_transient_', '', $option_name);
    }

    $transients = array_unique($transients);
    foreach ($transients as $key) {
        delete_transient($key);
    }

    if (wp_using_ext_object_cache()) {
        wp_cache_flush();
    }
}

add_action('save_post', 'bsp_clear_sitemap_cache');
add_action('deleted_post', 'bsp_clear_sitemap_cache');
add_action('created_term', 'bsp_clear_sitemap_cache');
add_action('edited_term', 'bsp_clear_sitemap_cache');
add_action('delete_term', 'bsp_clear_sitemap_cache');
add_action('switch_theme', 'bsp_clear_sitemap_cache');

add_action('init', function() {
    if (function_exists('w3_instance')) {
        $config = w3_instance('W3_Config');
        $no_cache_rules = $config->get_array('pgcache.reject.uri');
        $patterns = [
            '/sitemap_index.xml',
            '/*-sitemap.xml',
            '/*-sitemap-*.xml',
            '/sitemap_index.xml/',
            '/*-sitemap.xml/',
            '/*-sitemap-*.xml/'
        ];
        $updated = false;
        foreach ($patterns as $pattern) {
            if (!in_array($pattern, $no_cache_rules, true)) {
                $no_cache_rules[] = $pattern;
                $updated = true;
            }
        }
        if ($updated) {
            $config->set('pgcache.reject.uri', $no_cache_rules);
            $config->save();
        }
    }
});

add_action('template_redirect', function() {
    $request_uri = $_SERVER['REQUEST_URI'];
    if (preg_match('#/sitemap_index\.xml/?$#', $request_uri) || preg_match('#/.+-sitemap.*\.xml/?$#', $request_uri)) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    }
}, 1);
