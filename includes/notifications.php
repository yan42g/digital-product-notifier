<?php
// Empecher l'acces direct
if (!defined('ABSPATH')) {
    exit;
}

// Envoi AJAX des notifications et ajout de notes aux commandes
function dpum_send_notifications() {
    // Verifier le nonce
    check_ajax_referer('dpum-ajax-nonce', 'nonce');
    
    // Verifier les permissions
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(array('message' => __('Vous n\'avez pas les permissions necessaires.', 'digital-product-notifier')));
    }
    
    // Récupérer les données et nettoyer les caractères échappés
    $order_ids = isset($_POST['order_ids']) ? array_map('intval', $_POST['order_ids']) : array();
    $subject = isset($_POST['subject']) ? stripslashes(sanitize_text_field($_POST['subject'])) : '';
    $content = isset($_POST['content']) ? stripslashes(wp_kses_post($_POST['content'])) : '';
    $planner_name = isset($_POST['planner_name']) ? stripslashes(sanitize_text_field($_POST['planner_name'])) : '';
    
    // Journaliser les données pour le débogage
    error_log('DPUM - Ordre IDs: ' . print_r($order_ids, true));
    error_log('DPUM - Sujet: ' . $subject);
    error_log('DPUM - Contenu: ' . $content);
    error_log('DPUM - Nom du planner: ' . $planner_name);
    
    if (empty($order_ids)) {
        wp_send_json_error(array('message' => __('Veuillez selectionner au moins une commande.', 'digital-product-notifier')));
    }
    
    if (empty($subject)) {
        wp_send_json_error(array('message' => __('Veuillez entrer un sujet d\'email.', 'digital-product-notifier')));
    }
    
    if (empty($content)) {
        wp_send_json_error(array('message' => __('Veuillez entrer un contenu d\'email.', 'digital-product-notifier')));
    }
    
    $sent = 0;
    $errors = array();
    
    // Hook pour modifier le sujet des emails de notes clients
    add_filter('woocommerce_email_subject_customer_note', 'dpum_custom_email_subject', 10, 2);
    
    // Envoyer les emails et ajouter les notes aux commandes
    foreach ($order_ids as $order_id) {
        try {
            // Récupérer l'objet commande
            $order = wc_get_order($order_id);
            
            if (!$order) {
                $errors[] = sprintf(__('Commande #%d introuvable.', 'digital-product-notifier'), $order_id);
                continue;
            }
            
            $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $customer_email = $order->get_billing_email();
            $order_number = $order->get_order_number();
            
            // Remplacer les variables dans le contenu
            $email_content = str_replace(
                array('{customer_name}', '{order_number}', '{planner_name}'),
                array($customer_name, $order_number, $planner_name),
                $content
            );
            
            // Stocker le sujet personnalisé temporairement
            update_option('dpum_temp_email_subject', $subject);
            
            // Vérifier si les emails de notes clients sont activés
            $note_emails_enabled = false;
            if (function_exists('WC')) {
                $mailer = WC()->mailer();
                $emails = $mailer->get_emails();
                
                if (isset($emails['WC_Email_Customer_Note'])) {
                    $note_emails_enabled = $emails['WC_Email_Customer_Note']->is_enabled();
                }
            }
            
            if ($note_emails_enabled) {
                // Si les emails de notes clients sont activés, ajouter une note client
                // Le hook dpum_custom_email_subject modifiera le sujet
                $note_id = $order->add_order_note($email_content, 1); // 1 = envoyer au client
                
                error_log('DPUM: Note client ajoutée via système standard à la commande #' . $order_number);
            } else {
                // Si les emails de notes clients sont désactivés, envoyer l'email manuellement
                $headers = array(
                    'Content-Type: text/html; charset=UTF-8',
                    'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
                );
                
                // Utiliser directement le sujet personnalisé
                $email_subject = $subject;
                
                // Créer un contenu d'email formaté
                $formatted_email = '
                    <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
                        <div style="background-color: #f8f9fa; padding: 20px; border-bottom: 3px solid #7f54b3;">
                            <h2 style="color: #7f54b3; margin: 0;">' . esc_html($subject) . '</h2>
                            <p style="margin: 5px 0 0 0; color: #666;">Commande #' . esc_html($order_number) . '</p>
                        </div>
                        <div style="padding: 20px; background-color: #ffffff;">
                            ' . wpautop($email_content) . '
                        </div>
                        <div style="background-color: #f8f9fa; padding: 15px; border-top: 1px solid #dee2e6;">
                            <p style="font-size: 12px; color: #6c757d; margin: 0; text-align: center;">
                                Cet email a été envoyé depuis ' . get_bloginfo('name') . ' (' . site_url() . ')
                            </p>
                        </div>
                    </div>
                ';
                
                $mail_sent = wp_mail($customer_email, $email_subject, $formatted_email, $headers);
                
                if ($mail_sent) {
                    // Ajouter une note admin pour tracer l'envoi
                    $note_id = $order->add_order_note($email_content, 0); // 0 = note admin seulement
                    error_log('DPUM: Email envoyé manuellement à ' . $customer_email);
                } else {
                    $errors[] = sprintf(__('Impossible d\'envoyer l\'email à %s pour la commande #%d.', 'digital-product-notifier'), $customer_email, $order_number);
                    continue;
                }
            }
            
            // Vérifier si une action a été effectuée
            if (isset($note_id) && !$note_id && !isset($mail_sent)) {
                $errors[] = sprintf(__('Impossible d\'ajouter une note à la commande #%d.', 'digital-product-notifier'), $order_number);
                continue;
            }
            
            $sent++;
            
            // Ajouter une note privée pour confirmer l'envoi
            $order->add_order_note(
                sprintf(
                    __('Notification DPUM envoyée au client. Sujet: %s', 'digital-product-notifier'),
                    $subject
                ),
                0 // 0 = note privée pour l'admin seulement
            );
            
            // Nettoyer le sujet temporaire
            delete_option('dpum_temp_email_subject');
            
        } catch (Exception $e) {
            $errors[] = sprintf(__('Erreur pour la commande #%d: %s', 'digital-product-notifier'), $order_id, $e->getMessage());
            error_log('DPUM Exception: ' . $e->getMessage());
        }
    }
    
    // Retirer le hook après utilisation
    remove_filter('woocommerce_email_subject_customer_note', 'dpum_custom_email_subject', 10);
    
    // Préparer la réponse
    $response = array(
        'sent'    => $sent,
        'errors'  => $errors,
        'message' => sprintf(__('%d notification(s) envoyée(s) avec succès.', 'digital-product-notifier'), $sent),
    );
    
    if (!empty($errors)) {
        $response['message'] .= ' ' . sprintf(__('%d erreur(s) rencontrée(s).', 'digital-product-notifier'), count($errors));
    }
    
    wp_send_json_success($response);
}
add_action('wp_ajax_dpum_send_notifications', 'dpum_send_notifications');

/**
 * Fonction pour personnaliser le sujet des emails de notes clients
 */
function dpum_custom_email_subject($subject, $order) {
    // Récupérer le sujet personnalisé stocké temporairement
    $custom_subject = get_option('dpum_temp_email_subject', '');
    
    if (!empty($custom_subject)) {
        error_log('DPUM: Sujet personnalisé appliqué: ' . $custom_subject);
        return $custom_subject;
    }
    
    return $subject;
}

/**
 * Alternative: Hook plus spécifique pour les emails WooCommerce
 */
function dpum_modify_woocommerce_email_subject($subject, $order, $email) {
    // Vérifier si c'est un email de note client et si nous avons un sujet personnalisé
    if ($email->id === 'customer_note') {
        $custom_subject = get_option('dpum_temp_email_subject', '');
        if (!empty($custom_subject)) {
            error_log('DPUM: Sujet WooCommerce modifié: ' . $custom_subject);
            return $custom_subject;
        }
    }
    
    return $subject;
}

// Hook alternatif pour intercepter tous les emails WooCommerce
add_filter('woocommerce_email_subject', 'dpum_modify_woocommerce_email_subject', 10, 3);

// Fonction pour vérifier la configuration des emails
function dpum_check_email_configuration() {
    // Ne s'applique qu'aux pages de notre plugin et aux utilisateurs administrateurs
    if (
        current_user_can('manage_options') && 
        isset($_GET['page']) && 
        strpos($_GET['page'], 'digital-product-notifier') !== false &&
        isset($_GET['check_email']) && 
        $_GET['check_email'] == 1
    ) {
        // Exécuter un test d'email
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = 'Test d\'email - ' . $site_name;
        $message = '<p>Ceci est un test d\'email envoyé depuis le plugin Digital Planner Update Manager.</p>';
        $message .= '<p>Si vous recevez cet email, cela signifie que votre configuration d\'email fonctionne correctement.</p>';
        $message .= '<p>Date et heure du test : ' . current_time('mysql') . '</p>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . $admin_email . '>',
        );
        
        $result = wp_mail($admin_email, $subject, $message, $headers);
        
        // Afficher le résultat
        add_action('admin_notices', function() use ($result, $admin_email) {
            ?>
            <div class="notice <?php echo $result ? 'notice-success' : 'notice-error'; ?>">
                <p>
                    <strong>Test d'email :</strong> 
                    <?php if ($result): ?>
                        Un email de test a été envoyé avec succès à <?php echo esc_html($admin_email); ?>. Veuillez vérifier votre boîte de réception.
                    <?php else: ?>
                        Échec de l'envoi de l'email de test à <?php echo esc_html($admin_email); ?>. Veuillez vérifier votre configuration SMTP.
                    <?php endif; ?>
                </p>
            </div>
            <?php
        });
    }
}
add_action('admin_init', 'dpum_check_email_configuration');