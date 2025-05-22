<?php
// Empecher l'acces direct
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap dpum-admin-container">
    <h1><?php _e('Gestionnaire de mises a jour d\'agendas numeriques', 'digital-product-notifier'); ?></h1>
    
    <div class="dpum-search-form">
        <div class="dpum-form-group">
            <label for="dpum-file-name"><?php _e('Nom du fichier a rechercher :', 'digital-product-notifier'); ?></label>
            <input type="text" id="dpum-file-name" name="file_name" placeholder="Agenda Planner 2025" class="regular-text" value="<?php echo esc_attr($pre_search); ?>">
            <p class="description"><?php _e('Entrez exactement le nom qui apparait dans la colonne "Nom" de vos fichiers telechargeables.', 'digital-product-notifier'); ?></p>
            <p><a href="<?php echo admin_url('admin.php?page=digital-product-notifier-files'); ?>"><?php _e('Voir la liste de tous les noms de fichiers disponibles', 'digital-product-notifier'); ?></a></p>
        </div>
        
        <div class="dpum-form-actions">
            <button type="button" id="dpum-search-orders" class="button button-primary"><?php _e('Rechercher les commandes', 'digital-product-notifier'); ?></button>
            <span class="spinner"></span>
        </div>
    </div>
    
    <div id="dpum-results-container" style="display: none;">
        <h2><?php _e('Commandes trouvees', 'digital-product-notifier'); ?></h2>
        
        <div class="dpum-orders-count">
            <span id="dpum-orders-count">0</span> <?php _e('commande(s) trouvee(s)', 'digital-product-notifier'); ?>
        </div>
        
        <div class="dpum-orders-table-wrapper">
            <table class="widefat dpum-orders-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="dpum-select-all"></th>
                        <th><?php _e('Commande #', 'digital-product-notifier'); ?></th>
                        <th><?php _e('Client', 'digital-product-notifier'); ?></th>
                        <th><?php _e('Email', 'digital-product-notifier'); ?></th>
                        <th><?php _e('Date', 'digital-product-notifier'); ?></th>
                        <th><?php _e('Telechargement', 'digital-product-notifier'); ?></th>
                    </tr>
                </thead>
                <tbody id="dpum-orders-list">
                    <!-- Les resultats seront affiches ici via JavaScript -->
                </tbody>
            </table>
        </div>
        
        <div class="dpum-notification-form">
            <h3><?php _e('Envoyer une notification aux clients selectionnes', 'digital-product-notifier'); ?></h3>
            
            <div class="dpum-form-group">
                <label for="dpum-email-subject"><?php _e('Sujet de l\'email :', 'digital-product-notifier'); ?></label>
                <input type="text" id="dpum-email-subject" name="email_subject" placeholder="Mise a jour de votre agenda numerique" class="regular-text">
            </div>
            
            <div class="dpum-form-group">
                <label for="dpum-email-content"><?php _e('Contenu de la notification :', 'digital-product-notifier'); ?></label>
                <?php
                wp_editor('', 'dpum-email-content', array(
                    'media_buttons' => false,
                    'textarea_rows' => 10,
                    'teeny' => true,
                ));
                ?>
                <p class="description"><?php _e('Vous pouvez utiliser les variables suivantes : {customer_name}, {order_number}, {planner_name}', 'digital-product-notifier'); ?></p>
            </div>
            
            <div class="dpum-form-actions">
                <button type="button" id="dpum-send-notifications" class="button button-primary"><?php _e('Envoyer les notifications', 'digital-product-notifier'); ?></button>
                <span class="spinner"></span>
            </div>
        </div>
    </div>
    
    <div id="dpum-results-message" class="notice" style="display: none;">
        <!-- Les messages de resultat seront affiches ici -->
    </div>
    
    <div id="dpum-debug-info" style="margin-top: 30px; display: none;">
        <h3><?php _e('Informations de debogage', 'digital-product-notifier'); ?></h3>
        <pre id="dpum-debug-content" style="background: #f5f5f5; padding: 15px; overflow: auto; max-height: 300px;"></pre>
    </div>
</div>


<script type="text/javascript">
jQuery(document).ready(function($) {
    // Si un terme de recherche est pre-rempli, lancer la recherche automatiquement
    <?php if (!empty($pre_search)) : ?>
    setTimeout(function() {
        $("#dpum-search-orders").click();
    }, 500);
    <?php endif; ?>
});
</script>