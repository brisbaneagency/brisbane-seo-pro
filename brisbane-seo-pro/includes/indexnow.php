<?php

add_action('admin_init', function() {
    if (!get_option('bsp_indexnow_key')) {
        $key = bin2hex(random_bytes(16));
        update_option('bsp_indexnow_key', $key);
    }
});

add_action('init', function() {
    add_rewrite_rule('^([a-f0-9]{32})\.txt$', 'index.php?indexnow_key=$matches[1]', 'top');
    add_rewrite_tag('%indexnow_key%', '([a-f0-9]{32})');
});

add_action('template_redirect', function() {
    $key = get_query_var('indexnow_key');
    if ($key && $key === get_option('bsp_indexnow_key')) {
        header('Content-Type: text/plain');
        echo $key;
        exit;
    }
});

function bsp_indexnow_ping($urls) {
    $indexnow_key = get_option('bsp_indexnow_key');
    if (!$indexnow_key || empty($urls)) return;

    $host = parse_url(home_url(), PHP_URL_HOST);

    $payload = json_encode([
        'host' => $host,
        'key' => $indexnow_key,
        'keyLocation' => home_url("/$indexnow_key.txt"),
        'urlList' => $urls,
    ]);

    $search_engines = [
        'https://api.indexnow.org/indexnow',
        'https://www.bing.com/indexnow',
        'https://www.baidubce.com/indexnow',
        'https://www.yandex.com/indexnow',
    ];

    foreach ($search_engines as $endpoint) {
        wp_remote_post($endpoint, [
            'body' => $payload,
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 5,
        ]);
    }
}

function bsp_ping_google_bing_sitemap() {
    $sitemap_url = home_url('/sitemap_index.xml');

    $google_ping_url = 'https://www.google.com/ping?sitemap=' . urlencode($sitemap_url);
    $bing_ping_url = 'https://www.bing.com/webmaster/ping.aspx?siteMap=' . urlencode($sitemap_url);

    wp_remote_get($google_ping_url, ['timeout' => 5]);
    wp_remote_get($bing_ping_url, ['timeout' => 5]);
}

add_action('save_post', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Schedule ping to avoid slowing down post save
    if (!wp_next_scheduled('bsp_indexnow_ping_event', [$post_id])) {
        wp_schedule_single_event(time() + 60, 'bsp_indexnow_ping_event', [$post_id]);
    }
});

add_action('bsp_indexnow_ping_event', function($post_id) {
    $post_url = get_permalink($post_id);
    bsp_indexnow_ping([$post_url]);
    bsp_ping_google_bing_sitemap();
});
