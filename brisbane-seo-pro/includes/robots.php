<?php

function bsp_default_robots_txt($output, $public) {
    $output = "# Custom Robots by Brisbane

User-agent: *
Disallow: /wp-admin/
Disallow: /wp-login.php
Disallow: /wp-register.php
Disallow: /readme.html
Disallow: /thank-you/
Disallow: /my-account/*
Disallow: /account/*
Disallow: /cart/*
Disallow: /checkout/*
Disallow: /?s=
Disallow: *?replytocom
Disallow: /*?orderby=
Disallow: /*?filter_
Disallow: /*?add-to-cart=
Disallow: /*?ref=
Allow: /wp-admin/admin-ajax.php

User-agent: Googlebot
Disallow: /wp-admin/
Disallow: /wp-login.php
Disallow: /wp-register.php
Disallow: /readme.html
Disallow: /thank-you/
Disallow: /my-account/*
Disallow: /account/*
Disallow: /cart/*
Disallow: /checkout/*
Disallow: /?s=
Disallow: *?replytocom
Disallow: /*?orderby=
Disallow: /*?filter_
Disallow: /*?add-to-cart=
Disallow: /*?ref=
Allow: /wp-admin/admin-ajax.php

User-agent: Googlebot-Image
Disallow:
Allow: /wp-content/uploads/

Sitemap: " . home_url('/sitemap_index.xml');

    return $output;
}

add_filter('robots_txt', 'bsp_default_robots_txt', 10, 2);
