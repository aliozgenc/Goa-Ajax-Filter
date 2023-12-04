<?php
/*
Plugin Name: Combined Product Filter
Description: Belirli bir kategoriye, belirli bir fiyat aralığına ve belirli etiketlere ait ürünleri listeleyen bir filtre.
Version: 1.5
Author: Ali Özgenç
*/

// Admin Sekmesi Oluşturma
function my_category_filter_admin_tab()
{
    add_menu_page(
        'Category Filter Custom', // Sayfa başlığı
        'Category Filter Custom', // Menü adı
        'manage_options', // Gereken yetki düzeyi
        'my_category_filter_admin_page', // Sayfa slug
        'my_category_filter_admin_page_content', // İçerik fonksiyonu
        'dashicons-admin-generic', // Menü ikonu (isteğe bağlı)
        30 // Menü sırası
    );
}

// Admin Sayfa İçeriği
function my_category_filter_admin_page_content()
{
?>
    <div class="wrap">
        <h2>Category Filter</h2>
        <p>Shortcode: [combined_product_filter_shortcode]</p>
    </div>
<?php
}

// Filtreleme Formu Shortcode
function combined_product_filter_shortcode()
{
    ob_start();

    // Sadece ürün kategorileri sayfasında görünecek
    if (is_product_category() || is_shop()) {
        // Önceki seçimleri al
        $selected_category = isset($_GET['product_cat']) ? sanitize_text_field($_GET['product_cat']) : '';
        $min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
        $max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 10000;
        $selected_tags = isset($_GET['selected_tags']) ? (array)$_GET['selected_tags'] : array();

        echo '<div class="category-filter-sidebar">';
        echo '<form id="filter-form">';
        /* echo '<div class="category-radio-group">';

        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'parent'   => 0,
            'fields'   => 'id=>name',
        ));

        foreach ($categories as $category_id => $category_name) {
            $category_slug = sanitize_title($category_name);

            // Kategoriye ait ürün sayısını bul
            $category_product_count = new WP_Query(array(
                'post_type'      => 'product',
                'posts_per_page' => -1,
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'slug',
                        'terms'    => $category_slug,
                    ),
                ),
            ));

            echo '<div class="radio-label-container">';
            echo '<input type="radio" id="cat-' . $category_id . '" name="product_cat" value="' . $category_slug . '" ' . checked($category_slug, $selected_category, false) . '>';
            echo '<label for="cat-' . $category_id . '">' . $category_name . ' (' . $category_product_count->found_posts . ')</label>';
            echo '</div>';

            wp_reset_postdata();

            $subcategories = get_terms(array(
                'taxonomy' => 'product_cat',
                'parent'   => $category_id,
                'fields'   => 'id=>name',
            ));

            if ($subcategories) {
                echo '<ul class="subcategories">';

                foreach ($subcategories as $subcategory_id => $subcategory_name) {
                    $subcategory_slug = sanitize_title($subcategory_name);

                    // Alt kategoriye ait ürün sayısını bul
                    $subcategory_product_count = new WP_Query(array(
                        'post_type'      => 'product',
                        'posts_per_page' => -1,
                        'tax_query'      => array(
                            array(
                                'taxonomy' => 'product_cat',
                                'field'    => 'slug',
                                'terms'    => $subcategory_slug,
                            ),
                        ),
                    ));

                    echo '<li>';
                    echo '<input type="radio" id="cat-' . $subcategory_id . '" name="product_cat" value="' . $subcategory_slug . '" class="metro-subcategory" ' . checked($subcategory_slug, $selected_category, false) . '>';
                    echo '<label for="cat-' . $subcategory_id . '">' . $subcategory_name . ' (' . $subcategory_product_count->found_posts . ')</label>';
                    echo '</li>';

                    wp_reset_postdata();
                }

                echo '</ul>';
            }
        }

        echo '</div>';
        */

        // Fiyat filtresi için input 
        echo '<label for="price-min">Min Price:</label>';
        echo '<input type="number" id="price-min" name="min_price" value="' . $min_price . '">';

        echo '<label for="price-max">Max Price:</label>';
        echo '<input type="number" id="price-max" name="max_price" value="' . $max_price . '">';

        // Tag filtresi için input 
        echo '<div class="tag-filter">';
        $tags = get_terms(array(
            'taxonomy' => 'product_tag',
            'fields'   => 'id=>name',
        ));

        foreach ($tags as $tag_id => $tag_name) {
            $tag_slug = sanitize_title($tag_name);

            echo '<div class="checkbox-container">';
            echo '<input type="checkbox" id="' . $tag_slug . '" name="selected_tags[]" value="' . $tag_slug . '" ' . checked($tag_slug, in_array($tag_slug, (array)$selected_tags), false) . '>';
            echo '<label for="' . $tag_slug . '">' . $tag_name . '</label>';
            echo '</div>';
        }
        echo '</div>';

        echo '</form>';
        echo '</div>';

        // Sonuçları gösteren alan
        echo '<div id="category-filter-results" class="category-filter-results">';
        // AJAX sonuçları burada gösterilecek
        echo '</div>';
    }

    return ob_get_clean();
}

add_shortcode('combined_product_filter_shortcode', 'combined_product_filter_shortcode');

// WordPress REST API endpoint create
add_action('rest_api_init', function () {
    register_rest_route('my_custom_namespace', '/my-custom-endpoint', array(
        'methods'             => 'POST',
        'callback'           => 'filter_products_rest',
        'permission_callback' => '__return_true', // Everyone can use this endpoint
        'args'               => array(
            'form_data' => array(
                'sanitize_callback' => 'wp_unslash',
            ),
        ),
    ));
});

// REST API callback function
function filter_products_rest($data)
{
    // Form verilerini al
    $form_data = isset($data['form_data']) ? wp_unslash($data['form_data']) : '';


    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
    );

    parse_str($form_data, $form_array);

    if (isset($form_array['product_cat'])) {
        // Kategori filtresi ekle
        $args['tax_query'][] = array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($form_array['product_cat']),
        );
    }

    if (isset($form_array['min_price']) || isset($form_array['max_price'])) {
        // Fiyat filtresi ekle
        $args['meta_query'] = array(
            array(
                'key'     => '_price',
                'value'   => array(floatval($form_array['min_price']), floatval($form_array['max_price'])),
                'type'    => 'NUMERIC',
                'compare' => 'BETWEEN',
            ),
        );
    }

    if (isset($form_array['selected_tags'])) {
        // Tag filtresi ekle
        $args['tax_query'][] = array(
            'taxonomy' => 'product_tag',
            'field'    => 'slug',
            'terms'    => (array)$form_array['selected_tags'],
        );
    }

    $products = new WP_Query($args);

    ob_start();

    if ($products->have_posts()) :
        while ($products->have_posts()) : $products->the_post();
            // Ürünleri burada listele
            echo '<div class="product">';
            echo '<h2>' . get_the_title() . '</h2>';
            echo '<p>' . get_the_excerpt() . '</p>';
            echo '</div>';
        endwhile;
        wp_reset_postdata();
    else :
        echo '<p>No products found</p>';
    endif;

    $response = ob_get_clean();
    return $response;
}

function enqueue_custom_scripts()
{
    wp_enqueue_script('combined-product-filter-script', plugin_dir_url(__FILE__) . 'js/ajax-script.js', array('jquery'), '1.0', true);

    // JavaScript dosyasına my_ajax_object değişkenini aktar
    wp_localize_script('combined-product-filter-script', 'my_ajax_object', array(
        'ajax_url' => esc_url_raw(rest_url('/my_custom_namespace/my-custom-endpoint')),
        'nonce'    => wp_create_nonce('custom_ajax_nonce'),
        'action'   => 'custom_ajax_endpoint'
    ));
}

add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');
?>