<?php
// Empecher l'acces direct
if (!defined('ABSPATH')) {
    exit;
}
?>
<!-- Formulaire de mise à jour de l'URL -->
<div id="dpum-update-url-form" style="display: none; margin-bottom: 20px; padding: 15px; background: #fff; border: 1px solid #ccd0d4;">
    <h3><?php _e('Mettre à jour l\'URL du fichier téléchargeable', 'digital-product-notifier'); ?></h3>
    
    <div class="dpum-form-group">
        <p class="description"><?php _e('Cette action mettra à jour l\'URL du fichier téléchargeable pour tous les produits où ce fichier est utilisé.', 'digital-product-notifier'); ?></p>
        
        <label for="dpum-new-file-url"><?php _e('Nouvelle URL du fichier :', 'digital-product-notifier'); ?></label>
        <input type="url" id="dpum-new-file-url" name="new_file_url" placeholder="https://example.com/path/to/file.pdf" class="regular-text" style="width: 100%;">
        <p class="description"><?php _e('Entrez l\'URL complète du fichier téléchargeable. Pour les fichiers hébergés sur ce site, vous pouvez utiliser une URL relative.', 'digital-product-notifier'); ?></p>
    </div>
    
    <div class="dpum-form-actions">
        <button type="button" id="dpum-update-file-url" class="button button-primary"><?php _e('Mettre à jour l\'URL', 'digital-product-notifier'); ?></button>
        <span class="spinner"></span>
    </div>
</div>