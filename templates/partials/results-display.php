<?php
// Empecher l'acces direct
if (!defined('ABSPATH')) {
    exit;
}
?>
<!-- Container pour afficher les résultats -->
<div id="dpum-debug-results" style="display: none; margin-bottom: 20px;">
    <h2><?php _e('Résultats de la recherche', 'digital-product-notifier'); ?></h2>
    
    <div class="dpum-orders-count">
        <span id="dpum-debug-orders-count">0</span> <?php _e('commande(s) trouvee(s)', 'digital-product-notifier'); ?>
    </div>
    
    <div class="dpum-orders-table-wrapper">
        <table class="widefat dpum-orders-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="dpum-debug-select-all"></th>
                    <th><?php _e('Commande #', 'digital-product-notifier'); ?></th>
                    <th><?php _e('Client', 'digital-product-notifier'); ?></th>
                    <th><?php _e('Email', 'digital-product-notifier'); ?></th>
                    <th><?php _e('Date', 'digital-product-notifier'); ?></th>
                    <th><?php _e('Telechargement', 'digital-product-notifier'); ?></th>
                </tr>
            </thead>
            <tbody id="dpum-debug-orders-list">
                <!-- Les resultats seront affiches ici via JavaScript -->
            </tbody>
        </table>
    </div>
    
    <!-- Formulaire de notification -->
    <div class="dpum-notification-form">
        <h3><?php _e('Envoyer une notification aux clients sélectionnés', 'digital-product-notifier'); ?></h3>
        
        <div class="dpum-form-group">
            <label for="dpum-debug-email-subject"><?php _e('Sujet de l\'email :', 'digital-product-notifier'); ?></label>
            <input type="text" id="dpum-debug-email-subject" name="email_subject" placeholder="Mise a jour de votre agenda numerique" class="regular-text">
        </div>
        
        <div class="dpum-form-group">
            <label for="dpum-debug-email-content"><?php _e('Contenu de la notification :', 'digital-product-notifier'); ?></label>
            <?php
            wp_editor('', 'dpum-debug-email-content', array(
                'media_buttons' => false,
                'textarea_rows' => 10,
                'teeny' => true,
            ));
            ?>
            <p class="description"><?php _e('Vous pouvez utiliser les variables suivantes : {customer_name}, {order_number}, {planner_name}', 'digital-product-notifier'); ?></p>
        </div>
        
        <div class="dpum-form-actions">
            <button type="button" id="dpum-debug-send-notifications" class="button button-primary"><?php _e('Envoyer les notifications', 'digital-product-notifier'); ?></button>
            <span class="spinner"></span>
        </div>
    </div>
</div>