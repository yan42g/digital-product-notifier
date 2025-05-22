<?php
// Empecher l'acces direct
if (!defined('ABSPATH')) {
    exit;
}

// Ajouter la page d'administration dans le menu WooCommerce
function dpum_add_admin_menu() {
    add_submenu_page(
        'woocommerce',
        __('Notifications', 'digital-product-notifier'),
        __('Notifications', 'digital-product-notifier'),
        'manage_woocommerce',
        'digital-product-notifier',
        'dpum_admin_page'
    );
    
    add_submenu_page(
        'woocommerce',
        __('Liste des fichiers', 'digital-product-notifier'),
        __('Liste des fichiers', 'digital-product-notifier'),
        'manage_woocommerce',
        'digital-product-notifier-files',
        'dpum_debug_page'
    );
}
add_action('admin_menu', 'dpum_add_admin_menu');

// Dans le fichier includes/admin.php, fonction dpum_enqueue_admin_scripts()
function dpum_enqueue_admin_scripts($hook) {
    // Ne charger que sur nos pages d'admin
    if (strpos($hook, 'digital-product-notifier') === false) {
        return;
    }
    
    wp_enqueue_style(
        'dpum-admin-styles',
        DPUM_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        DPUM_VERSION
    );
    
    // Charger le script principal
    wp_enqueue_script(
        'dpum-admin-script',
        DPUM_PLUGIN_URL . 'assets/js/admin.js',
        array('jquery'),
        DPUM_VERSION,
        true
    );
    
    // S'assurer que le script debug-functions.js est chargé sur la page de debug
    if (isset($_GET['page']) && $_GET['page'] == 'digital-product-notifier-files') {
        wp_enqueue_script(
            'dpum-debug-functions',
            DPUM_PLUGIN_URL . 'templates/js/debug-functions.js',
            array('jquery'),
            DPUM_VERSION,
            true
        );
        
        // Passer les variables au script de debug
        wp_localize_script('dpum-debug-functions', 'dpum_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'admin_url' => admin_url(),
            'nonce' => wp_create_nonce('dpum-ajax-nonce'),
            'loading' => __('Chargement...', 'digital-product-notifier'),
            'success' => __('Succes!', 'digital-product-notifier'),
            'error' => __('Erreur:', 'digital-product-notifier'),
            'confirm_send' => __('Etes-vous sur de vouloir envoyer des notifications aux clients selectionnes?', 'digital-product-notifier')
        ));
    }
    
    // Passer les variables au script principal
    wp_localize_script('dpum-admin-script', 'dpum_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'admin_url' => admin_url(),
        'nonce' => wp_create_nonce('dpum-ajax-nonce'),
        'loading' => __('Chargement...', 'digital-product-notifier'),
        'success' => __('Succes!', 'digital-product-notifier'),
        'error' => __('Erreur:', 'digital-product-notifier'),
        'confirm_send' => __('Etes-vous sur de vouloir envoyer des notifications aux clients selectionnes?', 'digital-product-notifier')
    ));
}
add_action('admin_enqueue_scripts', 'dpum_enqueue_admin_scripts');

// Fonction pour creer la page d'administration principale
function dpum_admin_page() {
    // Verifier si nous avons une recherche pre-remplie
    $pre_search = isset($_GET['pre_search']) ? sanitize_text_field($_GET['pre_search']) : '';
    
    // Charger le template
    include(DPUM_PLUGIN_DIR . 'templates/admin-page.php');
}

// Page de liste des fichiers (anciennement debug)
function dpum_debug_page() {
    // Charger le template
    include(DPUM_PLUGIN_DIR . 'templates/debug-page.php');
}

// Ajouter une option pour afficher les erreurs PHP
function dpum_debug_mode() {
    // Ne s'applique qu'aux pages de notre plugin et aux utilisateurs administrateurs
    if (
        current_user_can('manage_options') && 
        isset($_GET['page']) && 
        strpos($_GET['page'], 'digital-product-notifier') !== false &&
        isset($_GET['debug']) && 
        $_GET['debug'] == 1
    ) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Ajouter une notice pour indiquer que le mode debug est actif
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-warning">
                <p><strong>Mode débogage activé</strong> - Les erreurs PHP seront affichées.</p>
            </div>
            <?php
        });
    }
}
add_action('admin_init', 'dpum_debug_mode');