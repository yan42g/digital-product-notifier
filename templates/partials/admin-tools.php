<?php
// Empecher l'acces direct
if (!defined('ABSPATH')) {
    exit;
}
?>
<!-- Outils d'administration -->
<div class="dpum-admin-tools" style="margin-top: 30px; padding: 15px; background: #fff; border: 1px solid #ccd0d4;">
    <h3><?php _e('Informations du plugin', 'digital-product-notifier'); ?></h3>
    
    <p><?php _e('Plugin de gestion de mises à jour d\'agendas numériques pour WooCommerce.', 'digital-product-notifier'); ?></p>
    <p><?php _e('Version: ' . DPUM_VERSION, 'digital-product-notifier'); ?></p>
</div>