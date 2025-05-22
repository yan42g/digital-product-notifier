<?php
// Empecher l'acces direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fonction de normalisation des noms de fichiers pour la comparaison
 * Cette fonction doit être accessible depuis tous les autres fichiers
 */
if (!function_exists('dpum_normalize_filename')) {
    function dpum_normalize_filename($filename) {
        // Supprimer les slashes et nettoyer le nom
        $normalized = stripslashes(trim($filename));
        // Vous pouvez ajouter d'autres normalisations si nécessaire
        // Par exemple : supprimer les espaces supplémentaires, normaliser la casse, etc.
        return $normalized;
    }
}

// S'assurer que WooCommerce est active
function dpum_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'dpum_woocommerce_notice');
        deactivate_plugins(plugin_basename(DPUM_PLUGIN_FILE));
    }
}
add_action('admin_init', 'dpum_check_woocommerce');

// Message d'erreur si WooCommerce n'est pas active
function dpum_woocommerce_notice() {
    ?>
    <div class="error">
        <p><?php _e('Digital Planner Update Manager necessite WooCommerce pour fonctionner. Veuillez installer et activer WooCommerce.', 'digital-product-notifier'); ?></p>
    </div>
    <?php
}

/**
 * Fonction pour normaliser les noms de fichiers lors des comparaisons
 * À ajouter à la fin du fichier includes/core.php
 */
function dpum_normalize_filename($filename) {
    // Supprimer les caractères d'échappement
    $filename = stripslashes($filename);
    
    // Supprimer les espaces en début et fin
    $filename = trim($filename);
    
    // Normaliser les espaces multiples en un seul espace
    $filename = preg_replace('/\s+/', ' ', $filename);
    
    return $filename;
}

// Vérifier et activer les emails de notes clients si nécessaire
function dpum_check_woocommerce_customer_note_emails() {
    // Vérifier si les emails de notes clients sont actifs
    $mailer = WC()->mailer();
    $emails = $mailer->get_emails();
    
    if (isset($emails['WC_Email_Customer_Note']) && !$emails['WC_Email_Customer_Note']->is_enabled()) {
        // Afficher une notice si les emails de notes clients sont désactivés
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>Digital Planner Update Manager :</strong> 
                    Les emails de notes clients sont désactivés dans WooCommerce. 
                    <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=email&section=wc_email_customer_note'); ?>">
                        Cliquez ici pour les activer
                    </a>.
                </p>
            </div>
            <?php
        });
    }
}
add_action('admin_init', 'dpum_check_woocommerce_customer_note_emails');

// Activer la traduction
function dpum_load_textdomain() {
    load_plugin_textdomain('digital-product-notifier', false, dirname(plugin_basename(DPUM_PLUGIN_FILE)) . '/languages');
}
add_action('plugins_loaded', 'dpum_load_textdomain');

// Créer la structure des dossiers lors de l'activation du plugin
function dpum_activation() {
    // Créer le dossier assets s'il n'existe pas
    $assets_dir = DPUM_PLUGIN_DIR . 'assets';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    
    // Créer le dossier CSS
    $css_dir = $assets_dir . '/css';
    if (!file_exists($css_dir)) {
        wp_mkdir_p($css_dir);
        
        // Créer le fichier CSS
        $css_content = file_get_contents(DPUM_PLUGIN_DIR . 'templates/admin.css');
        file_put_contents($css_dir . '/admin.css', $css_content);
    }
    
    // Créer le dossier JS
    $js_dir = $assets_dir . '/js';
    if (!file_exists($js_dir)) {
        wp_mkdir_p($js_dir);
        
        // Créer le fichier JS
        $js_content = file_get_contents(DPUM_PLUGIN_DIR . 'templates/js/debug-functions.js');
        file_put_contents($js_dir . '/admin.js', $js_content);
    }
    
    // Créer le dossier templates/partials s'il n'existe pas
    $partials_dir = DPUM_PLUGIN_DIR . 'templates/partials';
    if (!file_exists($partials_dir)) {
        wp_mkdir_p($partials_dir);
    }
    
    // Créer le dossier templates/js s'il n'existe pas
    $js_templates_dir = DPUM_PLUGIN_DIR . 'templates/js';
    if (!file_exists($js_templates_dir)) {
        wp_mkdir_p($js_templates_dir);
    }
    
    // Créer le dossier languages
    $lang_dir = DPUM_PLUGIN_DIR . 'languages';
    if (!file_exists($lang_dir)) {
        wp_mkdir_p($lang_dir);
    }
}
register_activation_hook(DPUM_PLUGIN_FILE, 'dpum_activation');

// Nettoyer lors de la désactivation
function dpum_deactivation() {
    // Vous pouvez ajouter ici du code pour nettoyer les données si nécessaire
}
register_deactivation_hook(DPUM_PLUGIN_FILE, 'dpum_deactivation');

// Fonction de débogage pour vérifier les erreurs PHP
function dpum_debug_errors() {
    // Ajouter une fonction pour capturer et journaliser les erreurs fatales
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error && $error['type'] === E_ERROR) {
            error_log('DPUM Fatal Error: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']);
        }
    });
}