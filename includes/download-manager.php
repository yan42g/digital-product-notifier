<?php
// Empecher l'acces direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fonction AJAX pour mettre à jour le nom du fichier téléchargeable
 */
function dpum_update_download_name() {
    // Activer les logs d'erreur pour le débogage
    error_log('DPUM: Début de dpum_update_download_name');
    
    try {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'dpum-ajax-nonce')) {
            error_log('DPUM: Échec de la vérification du nonce');
            wp_send_json_error(array('message' => __('Erreur de sécurité.', 'digital-product-notifier')));
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_woocommerce')) {
            error_log('DPUM: Permissions insuffisantes');
            wp_send_json_error(array('message' => __('Vous n\'avez pas les permissions nécessaires.', 'digital-product-notifier')));
        }
        
        // Récupérer les données
        $old_file_name = isset($_POST['old_file_name']) ? sanitize_text_field(stripslashes($_POST['old_file_name'])) : '';
        $new_file_name = isset($_POST['new_file_name']) ? sanitize_text_field(stripslashes($_POST['new_file_name'])) : '';
        
        error_log('DPUM: Ancien nom: ' . $old_file_name);
        error_log('DPUM: Nouveau nom: ' . $new_file_name);
        
        if (empty($old_file_name)) {
            wp_send_json_error(array('message' => __('Ancien nom de fichier manquant.', 'digital-product-notifier')));
        }
        
        if (empty($new_file_name)) {
            wp_send_json_error(array('message' => __('Nouveau nom de fichier manquant.', 'digital-product-notifier')));
        }
        
        // Trouver tous les produits avec ce nom de fichier téléchargeable
        $products = dpum_find_products_with_downloadable_file($old_file_name);
        
        error_log('DPUM: Produits trouvés: ' . count($products));
        
        if (empty($products)) {
            wp_send_json_error(array('message' => sprintf(__('Aucun produit trouvé avec le fichier "%s".', 'digital-product-notifier'), $old_file_name)));
        }
        
        $updated_products = 0;
        $errors = array();
        
        // Mettre à jour le nom pour chaque produit
        foreach ($products as $product_id) {
            try {
                error_log('DPUM: Traitement du produit #' . $product_id);
                
                $product = wc_get_product($product_id);
                
                if (!$product) {
                    $errors[] = sprintf(__('Produit #%d introuvable.', 'digital-product-notifier'), $product_id);
                    continue;
                }
                
                // Récupérer les fichiers téléchargeables actuels
                $downloads = $product->get_downloads();
                $update_needed = false;
                
                error_log('DPUM: Téléchargements du produit: ' . count($downloads));
                
                // Parcourir les fichiers téléchargeables et mettre à jour le nom si correspond
                foreach ($downloads as $download_id => $download) {
                    $current_name = dpum_normalize_filename($download->get_name());
                    $search_name = dpum_normalize_filename($old_file_name);
                    
                    error_log('DPUM: Comparaison - Actuel: "' . $current_name . '" vs Recherché: "' . $search_name . '"');
                    
                    if ($current_name === $search_name) {
                        error_log('DPUM: Correspondance trouvée, mise à jour du nom');
                        
                        // Créer un nouvel objet téléchargeable avec le nom mis à jour
                        $downloads[$download_id] = new WC_Product_Download();
                        $downloads[$download_id]->set_id($download_id);
                        $downloads[$download_id]->set_name($new_file_name);
                        $downloads[$download_id]->set_file($download->get_file());
                        
                        $update_needed = true;
                    }
                }
                
                // Si des modifications ont été apportées, mettre à jour le produit
                if ($update_needed) {
                    $product->set_downloads($downloads);
                    $result = $product->save();
                    
                    if ($result) {
                        $updated_products++;
                        error_log('DPUM: Produit #' . $product_id . ' mis à jour avec succès');
                    } else {
                        $errors[] = sprintf(__('Échec de la sauvegarde du produit #%d.', 'digital-product-notifier'), $product_id);
                        error_log('DPUM: Échec de la sauvegarde du produit #' . $product_id);
                    }
                } else {
                    error_log('DPUM: Aucune mise à jour nécessaire pour le produit #' . $product_id);
                }
            } catch (Exception $e) {
                $error_msg = sprintf(__('Erreur pour le produit #%d: %s', 'digital-product-notifier'), $product_id, $e->getMessage());
                $errors[] = $error_msg;
                error_log('DPUM: ' . $error_msg);
            }
        }
        
        // Préparer la réponse
        $response = array(
            'updated' => $updated_products,
            'errors'  => $errors,
            'message' => sprintf(__('Nom mis à jour pour %d produit(s).', 'digital-product-notifier'), $updated_products),
        );
        
        if (!empty($errors)) {
            $response['message'] .= ' ' . sprintf(__('%d erreur(s) rencontrée(s).', 'digital-product-notifier'), count($errors));
        }
        
        error_log('DPUM: Réponse finale: ' . json_encode($response));
        wp_send_json_success($response);
        
    } catch (Exception $e) {
        error_log('DPUM: Exception globale: ' . $e->getMessage());
        wp_send_json_error(array('message' => __('Erreur interne: ', 'digital-product-notifier') . $e->getMessage()));
    }
}
add_action('wp_ajax_dpum_update_download_name', 'dpum_update_download_name');

/**
 * Fonction AJAX pour mettre à jour l'URL du fichier téléchargeable
 */
function dpum_update_download_url() {
    // Vérifier le nonce
    check_ajax_referer('dpum-ajax-nonce', 'nonce');
    
    // Vérifier les permissions
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(array('message' => __('Vous n\'avez pas les permissions nécessaires.', 'digital-product-notifier')));
    }
    
    // Récupérer les données et nettoyer les caractères échappés
    $file_name = isset($_POST['file_name']) ? stripslashes(sanitize_text_field($_POST['file_name'])) : '';
    $new_url = isset($_POST['new_url']) ? esc_url_raw($_POST['new_url']) : '';
    
    if (empty($file_name)) {
        wp_send_json_error(array('message' => __('Nom de fichier manquant.', 'digital-product-notifier')));
    }
    
    if (empty($new_url)) {
        wp_send_json_error(array('message' => __('URL de fichier manquante.', 'digital-product-notifier')));
    }
    
    // Trouver tous les produits avec ce nom de fichier téléchargeable
    $products = dpum_find_products_with_downloadable_file($file_name);
    
    if (empty($products)) {
        wp_send_json_error(array('message' => sprintf(__('Aucun produit trouvé avec le fichier "%s".', 'digital-product-notifier'), $file_name)));
    }
    
    $updated_products = 0;
    $errors = array();
    
    // Mettre à jour l'URL pour chaque produit
    foreach ($products as $product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            $errors[] = sprintf(__('Produit #%d introuvable.', 'digital-product-notifier'), $product_id);
            continue;
        }
        
        // Récupérer les fichiers téléchargeables actuels
        $downloads = $product->get_downloads();
        $update_needed = false;
        
        // Parcourir les fichiers téléchargeables et mettre à jour l'URL si le nom correspond
        foreach ($downloads as $download_id => $download) {
            // Utiliser la fonction de normalisation pour la comparaison
            if (dpum_normalize_filename($download->get_name()) === dpum_normalize_filename($file_name)) {
                // Créer un nouvel objet téléchargeable avec l'URL mise à jour
                $downloads[$download_id] = new WC_Product_Download();
                $downloads[$download_id]->set_id($download_id);
                $downloads[$download_id]->set_name($download->get_name()); // Garder le nom original
                $downloads[$download_id]->set_file($new_url);
                
                $update_needed = true;
            }
        }
        
        // Si des modifications ont été apportées, mettre à jour le produit
        if ($update_needed) {
            $product->set_downloads($downloads);
            $product->save();
            $updated_products++;
            
            // Journaliser la mise à jour
            error_log('DPUM: URL du fichier "' . $file_name . '" mise à jour pour le produit #' . $product_id);
        }
    }
    
    // Préparer la réponse
    $response = array(
        'updated' => $updated_products,
        'errors'  => $errors,
        'message' => sprintf(__('URL mise à jour pour %d produit(s).', 'digital-product-notifier'), $updated_products),
    );
    
    if (!empty($errors)) {
        $response['message'] .= ' ' . sprintf(__('%d erreur(s) rencontrée(s).', 'digital-product-notifier'), count($errors));
    }
    
    wp_send_json_success($response);
}
add_action('wp_ajax_dpum_update_download_url', 'dpum_update_download_url');

/**
 * Fonction pour trouver les produits avec un fichier téléchargeable spécifique
 */
function dpum_find_products_with_downloadable_file($file_name) {
    $product_ids = array();
    
    // Normaliser le nom du fichier recherché
    $normalized_file_name = dpum_normalize_filename($file_name);
    
    error_log('DPUM: Recherche des produits avec le fichier normalisé: "' . $normalized_file_name . '"');
    
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
                
                foreach ($downloads as $download) {
                    // Normaliser le nom du fichier du produit pour la comparaison
                    $normalized_download_name = dpum_normalize_filename($download->get_name());
                    
                    error_log('DPUM: Comparaison - Produit #' . $product_id . ' - "' . $normalized_download_name . '" vs "' . $normalized_file_name . '"');
                    
                    // Utiliser la fonction de normalisation pour la comparaison
                    if ($normalized_download_name === $normalized_file_name) {
                        $product_ids[] = $product_id;
                        error_log('DPUM: Correspondance trouvée pour le produit #' . $product_id);
                        break; // On a trouvé un fichier correspondant, pas besoin de continuer
                    }
                }
            }
        }
        wp_reset_postdata();
    }
    
    error_log('DPUM: ' . count($product_ids) . ' produits trouvés');
    return $product_ids;
}