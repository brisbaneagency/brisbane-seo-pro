<?php
// includes/redirects.php

function rush_seo_redirects_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rush_seo_redirects';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        old_url varchar(255) NOT NULL,
        new_url varchar(255) NOT NULL,
        type enum('exact','regex') NOT NULL DEFAULT 'exact',
        status_code smallint(3) NOT NULL DEFAULT 301,
        active tinyint(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (id),
        UNIQUE KEY old_url_unique (old_url)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

add_action('wp_ajax_bsp_save_all_redirects', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }
    check_ajax_referer('bsp_redirects_nonce', 'nonce');

    if (!isset($_POST['redirects']) || !is_array($_POST['redirects'])) {
        wp_send_json_error('No redirects provided.');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'rush_seo_redirects';

    foreach ($_POST['redirects'] as $r) {
        $id = intval($r['id']);
        $old_url = sanitize_text_field($r['old_url']);
        $new_url = sanitize_text_field($r['new_url']);
        $type = in_array($r['type'], ['exact','regex']) ? $r['type'] : 'exact';
        $active = $r['active'] === 'true' ? 1 : 0;
        $status_code = 301;

        if (empty($old_url) || empty($new_url)) {
            continue;
        }

        $data = [
            'old_url' => $old_url,
            'new_url' => $new_url,
            'type' => $type,
            'status_code' => $status_code,
            'active' => $active
        ];

        if ($id > 0) {
            $wpdb->update($table, $data, ['id'=>$id], ['%s','%s','%s','%d','%d'], ['%d']);
        } else {
            $wpdb->insert($table, $data, ['%s','%s','%s','%d','%d']);
        }
    }

    wp_send_json_success();
});

function bsp_redirects_settings_html() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions.');
    }
    global $wpdb;
    $table = $wpdb->prefix . 'rush_seo_redirects';
    $redirects = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    $nonce = wp_create_nonce('bsp_redirects_nonce');
    ?>

    <div id="bsp-redirects-tab">
        <h2>Manage Redirects</h2>
        <table class="bsp-redirects-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="width: 30%; padding: 8px; border-bottom: 1px solid #ddd;">Old URL</th>
                    <th style="width: 30%; padding: 8px; border-bottom: 1px solid #ddd;">New URL</th>
                    <th style="width: 15%; padding: 8px; border-bottom: 1px solid #ddd;">Type</th>
                    <th style="width: 5%; padding: 8px; border-bottom: 1px solid #ddd;">Active</th>
                    <th style="width: 20%; padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                </tr>
            </thead>
            <tbody id="bsp-redirects-rows">
                <?php foreach ($redirects as $r): ?>
                <tr data-id="<?php echo esc_attr($r->id); ?>">
                    <td><input type="text" class="bsp-old-url" value="<?php echo esc_attr($r->old_url); ?>" style="width: 95%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;" /></td>
                    <td><input type="text" class="bsp-new-url" value="<?php echo esc_attr($r->new_url); ?>" style="width: 95%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;" /></td>
                    <td>
                        <select class="bsp-type" style="width: 95%; padding: 6px; border-radius: 4px; border: 1px solid #ccc;">
                            <option value="exact" <?php selected($r->type, 'exact'); ?>>301</option>
                            <option value="regex" <?php selected($r->type, 'regex'); ?>>Regex</option>
                        </select>
                    </td>
                    <td style="text-align: center;">
                        <label class="bsp-switch" style="margin:0 auto; display:inline-block; position:relative; width:48px; height:26px;">
                            <input type="checkbox" class="bsp-active" <?php checked($r->active,1); ?> style="opacity:0; width:0; height:0; position:absolute;">
                            <span class="bsp-slider" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#ccc; transition:.4s; border-radius:26px;"></span>
                        </label>
                    </td>
                    <td>
                        <button class="bsp-remove-redirect button button-link-delete">Remove</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button id="bsp-add-row" class="button button-secondary" style="margin-top:10px;">+ Add Row</button>
        <button id="bsp-save-all" class="button button-primary" style="margin-top:10px;">Save All</button>
        <p id="bsp-redirect-message" style="color:green; margin-top:10px;"></p>
    </div>

    <script>
    (function($){
        const nonce = '<?php echo $nonce; ?>';

        function showMessage(msg, isError = false) {
            $('#bsp-redirect-message').css('color', isError ? 'red' : 'green').text(msg);
            if (!isError) {
                setTimeout(()=>{$('#bsp-redirect-message').text('');},4000);
            }
        }

        $('#bsp-add-row').on('click',function(e){
            e.preventDefault();
            const newRow = $('<tr data-id="0">\
                <td><input type="text" class="bsp-old-url" style="width:95%; padding:6px; border:1px solid #ccc; border-radius:4px;" /></td>\
                <td><input type="text" class="bsp-new-url" style="width:95%; padding:6px; border:1px solid #ccc; border-radius:4px;" /></td>\
                <td><select class="bsp-type" style="width:95%; padding:6px; border-radius:4px; border:1px solid #ccc;"><option value="exact">301</option><option value="regex">Regex</option></select></td>\
                <td style="text-align:center;">\
                    <label class="bsp-switch" style="margin:0 auto; display:inline-block; position:relative; width:48px; height:26px;">\
                        <input type="checkbox" class="bsp-active" checked style="opacity:0; width:0; height:0; position:absolute;">\
                        <span class="bsp-slider" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#ccc; transition:.4s; border-radius:26px;"></span>\
                    </label>\
                </td>\
                <td><button class="bsp-remove-redirect button button-link-delete">Remove</button></td>\
            </tr>');
            $('#bsp-redirects-rows').append(newRow);
        });

        $(document).on('click','.bsp-remove-redirect',function(e){
            e.preventDefault();
            $(this).closest('tr').remove();
        });

        $('#bsp-save-all').on('click',function(e){
            e.preventDefault();
            let redirects=[];
            $('#bsp-redirects-rows tr').each(function(){
                let id=$(this).data('id');
                let old_url=$(this).find('.bsp-old-url').val().trim();
                let new_url=$(this).find('.bsp-new-url').val().trim();
                let type=$(this).find('.bsp-type').val();
                let active=$(this).find('.bsp-active').is(':checked')?'true':'false';
                if(old_url && new_url){
                    redirects.push({id,old_url,new_url,type,active});
                }
            });
            $.post(ajaxurl,{
                action:'bsp_save_all_redirects',
                nonce:nonce,
                redirects:redirects
            },function(response){
                if(response.success){
                    showMessage('All redirects saved. Reloading...');
                    setTimeout(()=>{location.reload();},1500);
                }else{
                    showMessage(response.data||'Failed to save.',true);
                }
            });
        });
    })(jQuery);
    </script>
    <?php
}

add_action('template_redirect', function() {
    if (is_admin()) return;

    global $wpdb;
    $table = $wpdb->prefix . 'rush_seo_redirects';

    $requested_url = wp_unslash($_SERVER['REQUEST_URI']);

    $redirects = $wpdb->get_results("SELECT * FROM $table WHERE active = 1");

    foreach ($redirects as $redirect) {
        if ($redirect->type === 'exact') {
            if (rtrim($requested_url, '/') === rtrim($redirect->old_url, '/')) {
                wp_redirect($redirect->new_url, $redirect->status_code);
                exit;
            }
        } elseif ($redirect->type === 'regex') {
            if (@preg_match('#' . $redirect->old_url . '#', $requested_url)) {
                $target = preg_replace('#' . $redirect->old_url . '#', $redirect->new_url, $requested_url);
                wp_redirect($target, $redirect->status_code);
                exit;
            }
        }
    }
});
