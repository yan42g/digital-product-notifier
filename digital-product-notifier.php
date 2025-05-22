<?php
/**
 * Plugin Name: Digital Product Notifier
 * Description: Plugin de notification en masse pour les mises à jour de produits numériques WooCommerce. Permet de contacter automatiquement les clients lorsqu'un produit téléchargeable de leur commande a été mis à jour.
 * Version: 1.0
 * Author: Yannick Guichard
 * Text Domain: digital-product-notifier
 * Domain Path: /languages
 * Requires WooCommerce: 4.0.0
 */


if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('DPUM_VERSION', '1.0.0');
define('DPUM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DPUM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DPUM_PLUGIN_FILE', __FILE__);

// Charger les modules
require_once DPUM_PLUGIN_DIR . 'includes/core.php';
require_once DPUM_PLUGIN_DIR . 'includes/admin.php';
require_once DPUM_PLUGIN_DIR . 'includes/products.php';
require_once DPUM_PLUGIN_DIR . 'includes/orders.php';
require_once DPUM_PLUGIN_DIR . 'includes/notifications.php';
require_once DPUM_PLUGIN_DIR . 'includes/download-manager.php';