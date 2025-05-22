<?php
// Mise à jour de templates/partials/file-list.php
// Empecher l'acces direct
if (!defined('ABSPATH')) {
    exit;
}
?>
<table class="widefat">
    <thead>
        <tr>
            <th><?php _e('ID du produit', 'digital-product-notifier'); ?></th>
            <th><?php _e('Nom du produit', 'digital-product-notifier'); ?></th>
            <th><?php _e('Nom du fichier', 'digital-product-notifier'); ?></th>
            <th><?php _e('Actions', 'digital-product-notifier'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Récupérer tous les produits téléchargeables (sans doublons et triés alphabétiquement)
        $downloadable_products = dpum_get_downloadable_products_unique_sorted();
        
        if (empty($downloadable_products)) {
            echo '<tr><td colspan="4">' . __('Aucun produit telechargeable trouve.', 'digital-product-notifier') . '</td></tr>';
        } else {
            foreach ($downloadable_products as $product) {
                echo '<tr>';
                echo '<td>' . esc_html($product['product_id']) . '</td>';
                echo '<td>' . esc_html($product['product_name']) . '</td>';
                echo '<td>' . esc_html($product['file_name']) . '</td>';
                echo '<td>
                        <button type="button" class="button button-small dpum-search-direct-button" data-file-name="' . esc_attr($product['file_name']) . '">' . __('Rechercher ce fichier', 'digital-product-notifier') . '</button>
                        <button type="button" class="button button-small dpum-update-url-button" data-file-name="' . esc_attr($product['file_name']) . '">' . __('Mettre à jour l\'URL', 'digital-product-notifier') . '</button>
                        <button type="button" class="button button-small dpum-update-name-button" data-file-name="' . esc_attr($product['file_name']) . '">' . __('Modifier le nom', 'digital-product-notifier') . '</button>
                    </td>';
                echo '</tr>';
            }
        }
        ?>
    </tbody>
</table>