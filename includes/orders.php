<?php
// Empecher l'acces direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fonction de normalisation des noms de fichiers pour la comparaison
 * Vérifier si elle n'existe pas déjà pour éviter les conflits
 */
if (!function_exists('dpum_normalize_filename')) {
    function dpum_normalize_filename($filename) {
        // Supprimer les slashes et nettoyer le nom
        $normalized = stripslashes(trim($filename));
        // Vous pouvez ajouter d'autres normalisations si nécessaire
        return $normalized;
    }
}

// Recherche AJAX des commandes
function dpum_search_orders() {
    // Verifier le nonce
    check_ajax_referer('dpum-ajax-nonce', 'nonce');
    
    // Verifier les permissions
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(array('message' => __('Vous n\'avez pas les permissions necessaires.', 'digital-product-notifier')));
    }
    
    // Recuperer le nom du fichier recherche
    $file_name = isset($_POST['file_name']) ? sanitize_text_field($_POST['file_name']) : '';
    
    if (empty($file_name)) {
        wp_send_json_error(array('message' => __('Veuillez entrer un nom de fichier a rechercher.', 'digital-product-notifier')));
    }
    
    // Trouver les commandes contenant ce fichier
    $orders = dpum_find_orders_with_download($file_name);
    
    wp_send_json_success(array(
        'count'   => count($orders),
        'orders'  => $orders,
        'message' => sprintf(__('%d commande(s) trouvee(s) pour "%s".', 'digital-product-notifier'), count($orders), $file_name),
    ));
}
add_action('wp_ajax_dpum_search_orders', 'dpum_search_orders');

// Fonction pour trouver les commandes avec un telechargement specifique
function dpum_find_orders_with_download($file_name) {
    global $wpdb;
    
    $orders = array();
    
    // Normaliser le nom du fichier recherché
    $normalized_file_name = dpum_normalize_filename($file_name);
    
    // Récupérer les commandes complétées avec des téléchargements
    $args = array(
        'status'       => array('wc-completed'),
        'limit'        => -1,
        'return'       => 'ids',
        'downloadable' => true,
    );
    
    $order_ids = wc_get_orders($args);
    
    // Journaliser pour le débogage
    error_log('DPUM: Recherche de commandes avec le fichier "' . $file_name . '"');
    error_log('DPUM: ' . count($order_ids) . ' commandes complétées avec téléchargements trouvées');
    
    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            continue;
        }
        
        $found = false;
        $download_items = array();
        
        // Parcourir les articles de la commande
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $product = $item->get_product();
            
            if (!$product) {
                // Essayer de récupérer le produit directement si l'item ne le fournit pas
                $product = wc_get_product($product_id);
                if (!$product) {
                    continue;
                }
            }
            
            // Vérifier si le produit est téléchargeable
            if ($product->is_downloadable()) {
                // Vérifier les téléchargements du produit
                $downloads = $product->get_downloads();
                
                if (!empty($downloads)) {
                    foreach ($downloads as $download_id => $download) {
                        $download_name = dpum_normalize_filename($download->get_name());
                        
                        // Vérifier si le nom du fichier correspond (comparaison normalisée)
                        if ($download_name === $normalized_file_name) {
                            $found = true;
                            $download_items[] = array(
                                'name' => $download->get_name(), // Garder le nom original pour l'affichage
                                'id'   => $download_id,
                            );
                        }
                    }
                }
                
                // Si ce n'est pas trouvé directement dans les téléchargements du produit
                // Vérifier les permissions de téléchargement accordées à cette commande
                if (!$found) {
                    $permissions = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions 
                            WHERE order_id = %d AND product_id = %d",
                            $order->get_id(),
                            $product_id
                        )
                    );
                    
                    foreach ($permissions as $permission) {
                        // Vérifier si le fichier est dans les permissions
                        $download_name = '';
                        
                        // Essayez de récupérer le nom du téléchargement
                        if (!empty($permission->download_id)) {
                            // Récupérer le téléchargement par son ID
                            $product = wc_get_product($permission->product_id);
                            if ($product) {
                                $downloads = $product->get_downloads();
                                if (isset($downloads[$permission->download_id])) {
                                    $download_name = $downloads[$permission->download_id]->get_name();
                                }
                            }
                        }
                        
                        // Si nous avons trouvé un nom de téléchargement et qu'il correspond
                        if (!empty($download_name)) {
                            $normalized_download_name = dpum_normalize_filename($download_name);
                            if ($normalized_download_name === $normalized_file_name) {
                                $found = true;
                                $download_items[] = array(
                                    'name' => $download_name,
                                    'id'   => $permission->download_id,
                                );
                            }
                        }
                    }
                }
            }
        }
        
        if ($found) {
            $customer = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $email = $order->get_billing_email();
            $customer_id = $order->get_customer_id(); // Récupérer l'ID du client
            
            $orders[] = array(
                'order_id'        => $order->get_id(),
                'order_number'    => $order->get_order_number(),
                'date_created'    => $order->get_date_created()->format('Y-m-d H:i:s'),
                'formatted_date'  => $order->get_date_created()->date_i18n(get_option('date_format') . ' ' . get_option('time_format')),
                'customer_name'   => $customer,
                'customer_email'  => $email,
                'customer_id'     => $customer_id, // Ajouter l'ID du client
                'downloads'       => $download_items,
            );
            
            // Journaliser pour le débogage
            error_log('DPUM: Commande #' . $order->get_order_number() . ' contient le fichier "' . $file_name . '"');
        }
    }
    
    error_log('DPUM: ' . count($orders) . ' commandes trouvées avec le fichier "' . $file_name . '"');
    
    return $orders;
}