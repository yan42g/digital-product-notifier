<?php
// Empecher l'acces direct test
if (!defined('ABSPATH')) {
    exit;
}
?>
<!-- Formulaire de mise à jour du nom -->
<div id="dpum-update-name-form" style="display: none; margin-bottom: 20px; padding: 15px; background: #fff; border: 1px solid #ccd0d4;">
    <h3><?php _e('Modifier le nom du fichier téléchargeable', 'digital-product-notifier'); ?></h3>
    
    <div class="dpum-form-group">
        <p class="description"><?php _e('Cette action mettra à jour le nom du fichier téléchargeable pour tous les produits où ce fichier est utilisé.', 'digital-product-notifier'); ?></p>
        
        <label for="dpum-old-file-name"><?php _e('Nom actuel du fichier :', 'digital-product-notifier'); ?></label>
        <input type="text" id="dpum-old-file-name" name="old_file_name" class="regular-text" readonly style="background-color: #f7f7f7;">
        
        <label for="dpum-new-file-name"><?php _e('Nouveau nom du fichier :', 'digital-product-notifier'); ?></label>
        <input type="text" id="dpum-new-file-name" name="new_file_name" placeholder="Nouveau nom du fichier" class="regular-text" style="width: 100%;">
        <p class="description"><?php _e('Entrez le nouveau nom que vous souhaitez donner à ce fichier téléchargeable.', 'digital-product-notifier'); ?></p>
    </div>
    
    <div class="dpum-form-actions">
        <button type="button" id="dpum-update-file-name" class="button button-primary"><?php _e('Mettre à jour le nom', 'digital-product-notifier'); ?></button>
        <button type="button" id="dpum-cancel-name-update" class="button"><?php _e('Annuler', 'digital-product-notifier'); ?></button>
        <span class="spinner"></span>
    </div>
</div>
