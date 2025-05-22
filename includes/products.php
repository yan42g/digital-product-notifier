<?php
// Empecher l'acces direct
if (!defined('ABSPATH')) {
    exit;
}

// Fonction pour récupérer tous les produits téléchargeables (sans doublons et triés)
function dpum_get_downloadable_products_unique_sorted() {
    $downloadable_products = dpum_get_downloadable_products();
    
    // Créer un tableau associatif pour éliminer les doublons par nom de fichier
    $unique_products = array();
    foreach ($downloadable_products as $product) {
        $file_name = $product['file_name'];
        // Si ce nom de fichier n'existe pas déjà ou si c'est une entrée plus récente, on la garde
        if (!isset($unique_products[$file_name]) || $product['product_id'] > $unique_products[$file_name]['product_id']) {
            $unique_products[$file_name] = $product;
        }
    }
    
    // Convertir de nouveau en tableau indexé
    $result = array_values($unique_products);
    
    // Trier par nom de fichier (ordre alphabétique)
    usort($result, function($a, $b) {
        return strcasecmp($a['file_name'], $b['file_name']);
    });
    
    return $result;
}

// Fonction pour recuperer tous les produits telechargeables
function dpum_get_downloadable_products() {
    $downloadable_products = array();
    
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_downloadable',
                'value'   => 'yes',
                'compare' => '=',
            ),
        ),
    );
    
    $products = new WP_Query($args);
    
    if ($products->have_posts()) {
        while ($products->have_posts()) {
            $products->the_post();
            $product_id = get_the_ID();
            $product = wc_get_product($product_id);
            
            if ($product && $product->is_downloadable()) {
                $downloads = $product->get_downloads();
                
                foreach ($downloads as $download_id => $download) {
                    $downloadable_products[] = array(
                        'product_id'   => $product_id,
                        'product_name' => $product->get_name(),
                        'file_name'    => $download->get_name(),
                        'file_id'      => $download_id,
                    );
                }
            }
        }
        wp_reset_postdata();
    }
    
    return $downloadable_products;
}