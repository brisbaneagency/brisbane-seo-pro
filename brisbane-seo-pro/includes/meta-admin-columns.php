<?php

function bsp_custom_columns($columns) {
    $columns['bsp_seo_title'] = 'SEO Title';
    $columns['bsp_seo_description'] = 'SEO Description';
    $columns['bsp_seo_status'] = 'SEO';
    $columns['bsp_seo_canonical'] = 'Canonical URL';
    return $columns;
}

function bsp_render_custom_columns($column, $post_id) {
    if ($column === 'bsp_seo_title') {
        $value = get_post_meta($post_id, 'rush_seo_title', true);
        if ($value === '') $value = get_post_meta($post_id, '_yoast_wpseo_title', true);
        ?>
        <div class="bsp-inline-wrap" data-meta="rush_seo_title" data-post="<?php echo esc_attr($post_id); ?>">
            <span class="bsp-display"><?php echo esc_html($value ?: '—'); ?></span>
            <a href="#" class="bsp-edit-inline"><span class="pencil">&#9998;</span></a>
        </div>
        <?php
    }
    if ($column === 'bsp_seo_description') {
        $value = get_post_meta($post_id, 'rush_seo_metadesc', true);
        if ($value === '') $value = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        ?>
        <div class="bsp-inline-wrap" data-meta="rush_seo_metadesc" data-post="<?php echo esc_attr($post_id); ?>">
            <span class="bsp-display"><?php echo esc_html($value ?: '—'); ?></span>
            <a href="#" class="bsp-edit-inline"><span class="pencil">&#9998;</span></a>
        </div>
        <?php
    }
    if ($column === 'bsp_seo_status') {
        $canonical = get_post_meta($post_id, 'rush_seo_canonical', true);
        if ($canonical === '') $canonical = get_post_meta($post_id, '_yoast_wpseo_canonical', true);

        $noindex = get_post_meta($post_id, 'rush_seo_noindex', true);
        if ($noindex === '') {
            $yoast_noindex = get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true);
            if ($yoast_noindex === '1') $noindex = '1';
        }

        $title = get_post_meta($post_id, 'rush_seo_title', true);
        if ($title === '') $title = get_post_meta($post_id, '_yoast_wpseo_title', true);

        $desc = get_post_meta($post_id, 'rush_seo_metadesc', true);
        if ($desc === '') $desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);

        $color = '#4caf50'; // green normal
        if (!empty($canonical) || $noindex) {
            $color = '#2196f3'; // blue canonical or noindex
        }
        if (empty($title) && empty($desc)) {
            $color = '#f44336'; // red missing
        }
        ?>
        <div style="display:flex;align-items:center;justify-content:center;">
            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:<?php echo esc_attr($color); ?>;"></span>
        </div>
        <?php
    }
    if ($column === 'bsp_seo_canonical') {
        $value = get_post_meta($post_id, 'rush_seo_canonical', true);
        if ($value === '') $value = get_post_meta($post_id, '_yoast_wpseo_canonical', true);
        ?>
        <div class="bsp-inline-wrap" data-meta="rush_seo_canonical" data-post="<?php echo esc_attr($post_id); ?>">
            <span class="bsp-display"><?php echo $value ? esc_html($value) : '—'; ?></span>
            <a href="#" class="bsp-edit-inline"><span class="pencil">&#9998;</span></a>
        </div>
        <?php
    }
}

function bsp_sortable_columns($columns) {
    $columns['bsp_seo_title'] = 'rush_seo_title';
    $columns['bsp_seo_description'] = 'rush_seo_metadesc';
    $columns['bsp_seo_canonical'] = 'rush_seo_canonical';
    $columns['bsp_seo_status'] = 'rush_seo_noindex';
    return $columns;
}

function bsp_orderby($query) {
    if (!is_admin()) return;
    $orderby = $query->get('orderby');
    if ($orderby === 'rush_seo_title') {
        $query->set('meta_key', 'rush_seo_title');
        $query->set('orderby', 'meta_value');
    }
    if ($orderby === 'rush_seo_metadesc') {
        $query->set('meta_key', 'rush_seo_metadesc');
        $query->set('orderby', 'meta_value');
    }
    if ($orderby === 'rush_seo_canonical') {
        $query->set('meta_key', 'rush_seo_canonical');
        $query->set('orderby', 'meta_value');
    }
    if ($orderby === 'rush_seo_noindex') {
        $query->set('meta_key', 'rush_seo_noindex');
        $query->set('orderby', 'meta_value');
    }
}

function bsp_add_columns_all_post_types() {
    $post_types = get_post_types(['public' => true], 'names');
    foreach ($post_types as $post_type) {
        add_filter("manage_{$post_type}_posts_columns", 'bsp_custom_columns', 100);
        add_action("manage_{$post_type}_posts_custom_column", 'bsp_render_custom_columns', 10, 2);
        add_filter("manage_edit-{$post_type}_sortable_columns", 'bsp_sortable_columns');
    }
}

add_action('admin_init', 'bsp_add_columns_all_post_types');
add_action('pre_get_posts', 'bsp_orderby');

add_action('admin_footer', function() {
    global $pagenow;
    if ($pagenow !== 'edit.php') return;
    $nonce = wp_create_nonce('bsp_inline_edit');
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function activateEdit(wrap) {
        var span = wrap.querySelector('.bsp-display');
        var meta = wrap.dataset.meta;
        var post = wrap.dataset.post;
        var currentValue = span.textContent.trim() === '—' ? '' : span.textContent.trim();
        span.style.display = 'none';
        var editBtn = wrap.querySelector('.bsp-edit-inline');
        if(editBtn) editBtn.style.display = 'none';
        var input = meta === 'rush_seo_metadesc' ? document.createElement('textarea') : document.createElement('input');
        input.value = currentValue;
        input.className = 'bsp-inline-input';
        input.style.width = '90%';
        if (meta === 'rush_seo_metadesc') input.style.height = '60px';
        wrap.appendChild(input);
        return input;
    }

    document.querySelectorAll('.bsp-edit-inline').forEach(function(editBtn) {
        editBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var wrap = editBtn.closest('.bsp-inline-wrap');
            var input = activateEdit(wrap);
            var saveBtn = document.createElement('a');
            saveBtn.textContent = '✔';
            saveBtn.href = '#';
            saveBtn.className = 'bsp-save-inline';
            wrap.appendChild(saveBtn);
            saveBtn.addEventListener('click', function(ev) {
                ev.preventDefault();
                saveBtn.textContent = '…';
                var data = new FormData();
                data.append('action', 'bsp_save_inline_meta');
                data.append('nonce', '<?php echo esc_js($nonce); ?>');
                data.append('post_id', wrap.dataset.post);
                data.append('meta_key', wrap.dataset.meta);
                data.append('value', input.value);
                fetch(ajaxurl, {method: 'POST', credentials: 'same-origin', body: data})
                .then(r=>r.json()).then(json=>{
                    if(json.success){
                        wrap.querySelector('.bsp-display').textContent = input.value || '—';
                        wrap.querySelector('.bsp-display').style.display='';
                        wrap.querySelector('.bsp-edit-inline').style.display='';
                        input.remove();
                        saveBtn.remove();
                    }else{
                        saveBtn.textContent='⚠';
                    }
                });
            });
        });
    });

    document.querySelectorAll('th.column-bsp_seo_title, th.column-bsp_seo_description').forEach(function(th){
        if(th.querySelector('.bsp-edit-all')) return;
        var edit = document.createElement('a');
        edit.href = '#';
        edit.className = 'bsp-edit-all';
        edit.innerHTML = '<span class="pencil">&#9998;</span>';
        th.style.position = 'relative';
        edit.style.position = 'absolute';
        edit.style.right = '6px';
        edit.style.top = '50%';
        edit.style.transform = 'translateY(-50%)';
        edit.style.textDecoration = 'none';
        edit.style.fontSize = '13px';
        edit.style.cursor = 'pointer';
        th.appendChild(edit);
        edit.addEventListener('click', function(e){
            e.preventDefault();
            var metaKey = th.textContent.includes('Title') ? 'rush_seo_title' : 'rush_seo_metadesc';
            var wraps = document.querySelectorAll('.bsp-inline-wrap[data-meta="'+metaKey+'"]');
            wraps.forEach(function(wrap){
                if(!wrap.querySelector('.bsp-inline-input')){
                    activateEdit(wrap);
                }
            });
            if(!th.querySelector('.bsp-save-all')){
                var saveAll = document.createElement('a');
                saveAll.textContent='✔ Save All';
                saveAll.href='#';
                saveAll.className='bsp-save-all';
                saveAll.style.marginLeft = '6px';
                th.appendChild(saveAll);
                saveAll.addEventListener('click', function(ev){
                    ev.preventDefault();
                    wraps.forEach(function(wrap){
                        var input=wrap.querySelector('.bsp-inline-input');
                        if(!input)return;
                        var data=new FormData();
                        data.append('action','bsp_save_inline_meta');
                        data.append('nonce','<?php echo esc_js($nonce); ?>');
                        data.append('post_id',wrap.dataset.post);
                        data.append('meta_key',wrap.dataset.meta);
                        data.append('value',input.value);
                        fetch(ajaxurl,{method:'POST',credentials:'same-origin',body:data})
                        .then(r=>r.json()).then(json=>{
                            if(json.success){
                                wrap.querySelector('.bsp-display').textContent=input.value||'—';
                                wrap.querySelector('.bsp-display').style.display='';
                                wrap.querySelector('.bsp-edit-inline').style.display='';
                                input.remove();
                            }
                        });
                    });
                    saveAll.remove();
                });
            }
        });
    });
});
</script>
<?php
});

add_action('wp_ajax_bsp_save_inline_meta', function() {
    if (!current_user_can('edit_posts')) wp_send_json_error();
    check_ajax_referer('bsp_inline_edit', 'nonce');
    $post_id = intval($_POST['post_id']);
    $meta_key = sanitize_key($_POST['meta_key']);
    $value = sanitize_text_field($_POST['value']);
    update_post_meta($post_id, $meta_key, $value);
    wp_send_json_success();
});
