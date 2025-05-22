<?php
// Empecher l'acces direct
if (!defined('ABSPATH')) {
    exit;
}
?>

<script type="text/javascript">
// Code de débogage pour vérifier le chargement
console.log('Debug page script loaded');
jQuery(document).ready(function($) {
    console.log('jQuery ready in debug page');
    
    // Attacher un gestionnaire d'événements de test
    $('.dpum-search-direct-button').on('click', function() {
        console.log('Search button clicked: ' + $(this).data('file-name'));
    });
    
    $('.dpum-update-url-button').on('click', function() {
        console.log('Update URL button clicked: ' + $(this).data('file-name'));
    });
});
</script>


<div class="wrap">
    <h1><?php _e('Liste des fichiers telechargeables', 'digital-product-notifier'); ?></h1>
    
    <p><?php _e('Cette page affiche tous les noms de fichiers telechargeables disponibles dans votre boutique WooCommerce.', 'digital-product-notifier'); ?></p>
    
    <!-- Indicateur de chargement -->
    <div id="dpum-debug-loading" class="notice notice-info" style="display: none;">
        <p><span class="spinner is-active" style="float:left; margin-right:10px;"></span> <?php _e('Recherche en cours...', 'digital-product-notifier'); ?></p>
    </div>
    
    <!-- Affichage des messages -->
    <div id="dpum-debug-message" class="notice" style="display: none;">
        <!-- Les messages de resultat seront affiches ici -->
    </div>
    
    <!-- Inclure les composants -->
    <?php 
    // Créer le dossier partials s'il n'existe pas
    $partials_dir = DPUM_PLUGIN_DIR . 'templates/partials';
    if (!file_exists($partials_dir)) {
        wp_mkdir_p($partials_dir);
    }
    
    // Inclure les composants partiels
    require_once DPUM_PLUGIN_DIR . 'templates/partials/results-display.php';
    require_once DPUM_PLUGIN_DIR . 'templates/partials/url-update-form.php';
    require_once DPUM_PLUGIN_DIR . 'templates/partials/file-list.php';
    require_once DPUM_PLUGIN_DIR . 'templates/partials/admin-tools.php';
    require_once DPUM_PLUGIN_DIR . 'templates/partials/name-update-form.php';
    ?>
</div>

<script type="text/javascript" src="<?php echo DPUM_PLUGIN_URL . 'templates/js/debug-functions.js'; ?>"></script>