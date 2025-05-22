// Variable globale pour éviter la double initialisation
if (typeof window.dpumInitialized === 'undefined') {
    window.dpumInitialized = true;

    jQuery(document).ready(function($) {
        console.log('DPUM: Initialisation des fonctions de débogage');
        
        // Variables de verrouillage pour éviter les actions en double
        var isSearching = false;
        var isUpdatingUrl = false;
        var isSendingNotifications = false;
        var isUpdatingName = false;
        
        // Nettoyer tous les gestionnaires d'événements existants
        $('.dpum-search-direct-button').off('click');
        $('.dpum-update-url-button').off('click');
        $('.dpum-update-name-button').off('click');
        $('#dpum-update-file-url').off('click');
        $('#dpum-update-file-name').off('click');
        $('#dpum-cancel-name-update').off('click');
        $('#dpum-debug-select-all').off('change');
        $(document).off('change', '.dpum-debug-order-checkbox');
        $('#dpum-debug-send-notifications').off('click');
        
        /**
         * Recherche directe de fichiers
         */
        $('.dpum-search-direct-button').on('click', function(e) {
            e.preventDefault();
            
            if (isSearching) {
                console.log('DPUM: Une recherche est déjà en cours, action ignorée');
                return;
            }
            
            isSearching = true;
            var $button = $(this);
            $button.prop('disabled', true);
            
            var fileName = $(this).data('file-name');
            var loadingIndicator = $('#dpum-debug-loading');
            var resultsContainer = $('#dpum-debug-results');
            var resultsMessage = $('#dpum-debug-message');
            
            console.log('DPUM: Recherche du fichier:', fileName);
            
            resultsContainer.hide();
            resultsMessage.hide();
            $('#dpum-update-url-form').show();
            loadingIndicator.show();
            
            $('#dpum-update-file-url').data('file-name', fileName);
            
            $.ajax({
                url: dpum_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'dpum_search_orders',
                    nonce: dpum_vars.nonce,
                    file_name: fileName
                },
                success: function(response) {
                    console.log('DPUM: Réponse de recherche reçue:', response);
                    loadingIndicator.hide();
                    
                    if (response.success) {
                        $('#dpum-debug-orders-count').text(response.data.count);
                        
                        var ordersList = $('#dpum-debug-orders-list');
                        ordersList.empty();
                        
                        if (response.data.count > 0) {
                            $.each(response.data.orders, function(index, order) {
                                var downloadNames = [];
                                $.each(order.downloads, function(i, download) {
                                    downloadNames.push(download.name);
                                });
                                
                                var orderLink = $('<a>')
                                    .attr('href', dpum_vars.admin_url + 'post.php?post=' + order.order_id + '&action=edit')
                                    .attr('target', '_blank')
                                    .text('#' + order.order_number);
                                
                                var customerCell;
                                if (order.customer_id && order.customer_id > 0) {
                                    var customerLink = $('<a>')
                                        .attr('href', dpum_vars.admin_url + 'user-edit.php?user_id=' + order.customer_id)
                                        .attr('target', '_blank')
                                        .text(order.customer_name);
                                    customerCell = $('<td>').append(customerLink);
                                } else {
                                    customerCell = $('<td>').text(order.customer_name + ' (invité)');
                                }
                                
                                var row = $('<tr>').append(
                                    $('<td>').append(
                                        $('<input>').attr({
                                            type: 'checkbox',
                                            class: 'dpum-debug-order-checkbox',
                                            'data-order-id': order.order_id,
                                            'data-customer-name': order.customer_name,
                                            'data-order-number': order.order_number
                                        })
                                    ),
                                    $('<td>').append(orderLink),
                                    customerCell,
                                    $('<td>').text(order.customer_email),
                                    $('<td>').text(order.formatted_date),
                                    $('<td>').text(downloadNames.join(', '))
                                );
                                
                                ordersList.append(row);
                            });
                            
                            resultsContainer.show();
                            $('h2:first').text('Résultats de la recherche pour "' + fileName + '"');
                            
                            resultsMessage.removeClass('notice-error notice-warning').addClass('notice-success')
                                .html('<p>' + response.data.message + '</p>')
                                .show();
                        } else {
                            resultsMessage.removeClass('notice-success notice-error').addClass('notice-warning')
                                .html('<p>' + response.data.message + '</p>')
                                .show();
                        }
                    } else {
                        resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                            .html('<p>' + dpum_vars.error + ' ' + response.data.message + '</p>')
                            .show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('DPUM: Erreur de recherche:', error);
                    loadingIndicator.hide();
                    
                    resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                        .html('<p>' + dpum_vars.error + ' ' + error + '</p>')
                        .show();
                },
                complete: function() {
                    isSearching = false;
                    $button.prop('disabled', false);
                }
            });
        });
        
        /**
         * Affichage du formulaire de mise à jour d'URL
         */
        $('.dpum-update-url-button').on('click', function(e) {
            e.preventDefault();
            
            var fileName = $(this).data('file-name');
            console.log('DPUM: Affichage du formulaire de mise à jour pour:', fileName);
            
            $('#dpum-update-url-form').show();
            $('html, body').animate({
                scrollTop: $('#dpum-update-url-form').offset().top - 50
            }, 500);
            
            $('#dpum-update-file-url').data('file-name', fileName);
            $('#dpum-update-url-form h3').text('Mettre à jour l\'URL du fichier: ' + fileName);
        });
        
        /**
         * Mise à jour de l'URL du fichier
         */
        $('#dpum-update-file-url').on('click', function() {
            if (isUpdatingUrl) {
                console.log('DPUM: Une mise à jour d\'URL est déjà en cours, action ignorée');
                return;
            }
            
            var $button = $(this);
            var fileName = $button.data('file-name');
            var newUrl = $('#dpum-new-file-url').val().trim();
            var spinner = $button.siblings('.spinner');
            var resultsMessage = $('#dpum-debug-message');
            
            if (newUrl === '') {
                resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                    .html('<p>' + dpum_vars.error + ' ' + 'Veuillez entrer une URL de fichier valide.' + '</p>')
                    .show();
                return;
            }
            
            if (!confirm('Êtes-vous sûr de vouloir mettre à jour l\'URL du fichier "' + fileName + '" pour tous les produits où il est utilisé?')) {
                return;
            }
            
            isUpdatingUrl = true;
            $button.prop('disabled', true);
            
            resultsMessage.hide();
            spinner.addClass('is-active');
            
            $.ajax({
                url: dpum_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'dpum_update_download_url',
                    nonce: dpum_vars.nonce,
                    file_name: fileName,
                    new_url: newUrl
                },
                success: function(response) {
                    console.log('DPUM: Réponse de mise à jour reçue:', response);
                    spinner.removeClass('is-active');
                    
                    if (response.success) {
                        var messageText = '<p><strong>' + response.data.message + '</strong></p>';
                        
                        if (response.data.errors && response.data.errors.length > 0) {
                            messageText += '<p><strong>Erreurs rencontrées :</strong></p>';
                            messageText += '<ul class=\"dpum-error-list\">';
                            $.each(response.data.errors, function(index, error) {
                                messageText += '<li>' + error + '</li>';
                            });
                            messageText += '</ul>';
                        }
                        
                        resultsMessage.removeClass('notice-error notice-warning').addClass('notice-success')
                            .html(messageText)
                            .show();
                        
                        $('#dpum-new-file-url').val('');
                    } else {
                        resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                            .html('<p>' + dpum_vars.error + ' ' + response.data.message + '</p>')
                            .show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('DPUM: Erreur de mise à jour:', error);
                    spinner.removeClass('is-active');
                    
                    resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                        .html('<p>' + dpum_vars.error + ' ' + error + '</p>')
                        .show();
                },
                complete: function() {
                    isUpdatingUrl = false;
                    $button.prop('disabled', false);
                }
            });
        });
        
        /**
         * Affichage du formulaire de mise à jour du nom de fichier
         */
        $('.dpum-update-name-button').on('click', function(e) {
            e.preventDefault();
            
            var fileName = $(this).data('file-name');
            console.log('DPUM: Affichage du formulaire de modification du nom pour:', fileName);
            
            // Pré-remplir le formulaire avec le nom actuel
            $('#dpum-old-file-name').val(fileName);
            $('#dpum-new-file-name').val('').focus();
            
            // Faire défiler vers le formulaire et l'afficher
            $('#dpum-update-name-form').show();
            $('html, body').animate({
                scrollTop: $('#dpum-update-name-form').offset().top - 50
            }, 500);
            
            // Mettre à jour le titre du formulaire
            $('#dpum-update-name-form h3').text('Modifier le nom du fichier: ' + fileName);
        });

        /**
         * Annulation de la modification du nom
         */
        $('#dpum-cancel-name-update').on('click', function() {
            $('#dpum-update-name-form').hide();
            $('#dpum-old-file-name').val('');
            $('#dpum-new-file-name').val('');
        });

        /**
         * Mise à jour du nom du fichier
         */
        $('#dpum-update-file-name').on('click', function() {
            if (isUpdatingName) {
                console.log('DPUM: Une mise à jour de nom est déjà en cours, action ignorée');
                return;
            }
            
            var $button = $(this);
            var oldFileName = $('#dpum-old-file-name').val().trim();
            var newFileName = $('#dpum-new-file-name').val().trim();
            var spinner = $button.siblings('.spinner');
            var resultsMessage = $('#dpum-debug-message');
            
            console.log('DPUM: Tentative de mise à jour du nom:', oldFileName, '->', newFileName);
            
            // Vérifications de base
            if (oldFileName === '' || newFileName === '') {
                resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                    .html('<p>' + dpum_vars.error + ' ' + 'Veuillez remplir tous les champs.' + '</p>')
                    .show();
                return;
            }
            
            if (oldFileName === newFileName) {
                resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                    .html('<p>' + dpum_vars.error + ' ' + 'Le nouveau nom doit être différent de l\'ancien.' + '</p>')
                    .show();
                return;
            }
            
            // Demander confirmation
            if (!confirm('Êtes-vous sûr de vouloir renommer "' + oldFileName + '" en "' + newFileName + '" pour tous les produits?')) {
                return;
            }
            
            // Activer le verrouillage
            isUpdatingName = true;
            $button.prop('disabled', true);
            
            // Réinitialiser le message et afficher le spinner
            resultsMessage.hide();
            spinner.addClass('is-active');
            
            // Faire la requête AJAX
            $.ajax({
                url: dpum_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'dpum_update_download_name',
                    nonce: dpum_vars.nonce,
                    old_file_name: oldFileName,
                    new_file_name: newFileName
                },
                success: function(response) {
                    console.log('DPUM: Réponse de mise à jour de nom reçue:', response);
                    spinner.removeClass('is-active');
                    
                    if (response.success) {
                        var messageText = '<p><strong>' + response.data.message + '</strong></p>';
                        
                        if (response.data.errors && response.data.errors.length > 0) {
                            messageText += '<p><strong>Erreurs rencontrées :</strong></p>';
                            messageText += '<ul class=\"dpum-error-list\">';
                            $.each(response.data.errors, function(index, error) {
                                messageText += '<li>' + error + '</li>';
                            });
                            messageText += '</ul>';
                        }
                        
                        resultsMessage.removeClass('notice-error notice-warning').addClass('notice-success')
                            .html(messageText)
                            .show();
                        
                        // Cacher le formulaire
                        $('#dpum-update-name-form').hide();
                        
                        // Recharger la page après 2 secondes pour voir les changements
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                            .html('<p>' + dpum_vars.error + ' ' + response.data.message + '</p>')
                            .show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('DPUM: Erreur de mise à jour de nom:', error);
                    console.error('DPUM: Status:', status);
                    console.error('DPUM: XHR:', xhr);
                    
                    spinner.removeClass('is-active');
                    
                    var errorMessage = 'Erreur de communication avec le serveur';
                    if (xhr.responseText) {
                        try {
                            var errorData = JSON.parse(xhr.responseText);
                            if (errorData.data && errorData.data.message) {
                                errorMessage = errorData.data.message;
                            }
                        } catch (e) {
                            // Si ce n'est pas du JSON valide, utiliser le texte brut
                            errorMessage = xhr.responseText.substring(0, 200) + '...';
                        }
                    }
                    
                    resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                        .html('<p>' + dpum_vars.error + ' ' + errorMessage + '</p>')
                        .show();
                },
                complete: function() {
                    isUpdatingName = false;
                    $button.prop('disabled', false);
                }
            });
        });

        /**
         * Sélection des commandes
         */
        $('#dpum-debug-select-all').on('change', function() {
            $('.dpum-debug-order-checkbox').prop('checked', $(this).prop('checked'));
        });
        
        $(document).on('change', '.dpum-debug-order-checkbox', function() {
            var allChecked = $('.dpum-debug-order-checkbox:checked').length === $('.dpum-debug-order-checkbox').length;
            $('#dpum-debug-select-all').prop('checked', allChecked);
        });
        
        /**
         * Envoi des notifications
         */
        $('#dpum-debug-send-notifications').on('click', function() {
            if (isSendingNotifications) {
                console.log('DPUM: Un envoi de notifications est déjà en cours, action ignorée');
                return;
            }
            
            var $button = $(this);
            var selectedOrders = $('.dpum-debug-order-checkbox:checked');
            var spinner = $button.siblings('.spinner');
            var resultsMessage = $('#dpum-debug-message');
            var subject = $('#dpum-debug-email-subject').val().trim();
            var content = tinyMCE.get('dpum-debug-email-content') ? tinyMCE.get('dpum-debug-email-content').getContent() : $('#dpum-debug-email-content').val();
            var fileName = $('.dpum-search-direct-button.active').data('file-name') || '';
            
            console.log('DPUM: Tentative d\'envoi de notifications pour', selectedOrders.length, 'commande(s)');
            
            if (selectedOrders.length === 0) {
                resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                    .html('<p>' + dpum_vars.error + ' ' + 'Veuillez sélectionner au moins une commande.' + '</p>')
                    .show();
                return;
            }
            
            if (subject === '') {
                resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                    .html('<p>' + dpum_vars.error + ' ' + 'Veuillez entrer un sujet d\'email.' + '</p>')
                    .show();
                return;
            }
            
            if (content === '') {
                resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                    .html('<p>' + dpum_vars.error + ' ' + 'Veuillez entrer un contenu d\'email.' + '</p>')
                    .show();
                return;
            }
            
            if (!confirm(dpum_vars.confirm_send)) {
                return;
            }
            
            isSendingNotifications = true;
            $button.prop('disabled', true);
            
            resultsMessage.hide();
            spinner.addClass('is-active');
            
            var orderIds = [];
            selectedOrders.each(function() {
                orderIds.push($(this).data('order-id'));
            });
            
            $.ajax({
                url: dpum_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'dpum_send_notifications',
                    nonce: dpum_vars.nonce,
                    order_ids: orderIds,
                    subject: subject,
                    content: content,
                    planner_name: fileName
                },
                success: function(response) {
                    console.log('DPUM: Réponse d\'envoi de notifications reçue:', response);
                    spinner.removeClass('is-active');
                    
                    if (response.success) {
                        var messageText = '<p><strong>' + response.data.message + '</strong></p>';
                        
                        messageText += '<p>' + 'Des notes ont été ajoutées aux commandes suivantes :' + '</p>';
                        messageText += '<ul>';
                        
                        selectedOrders.each(function() {
                            var orderNumber = $(this).data('order-number');
                            var customerName = $(this).data('customer-name');
                            messageText += '<li>Commande #' + orderNumber + ' - ' + customerName + '</li>';
                        });
                        
                        messageText += '</ul>';
                        
                        if (response.data.errors && response.data.errors.length > 0) {
                            messageText += '<p><strong>Erreurs rencontrées :</strong></p>';
                            messageText += '<ul class=\"dpum-error-list\">';
                            $.each(response.data.errors, function(index, error) {
                                messageText += '<li>' + error + '</li>';
                            });
                            messageText += '</ul>';
                        }
                        
                        resultsMessage.removeClass('notice-error notice-warning').addClass('notice-success')
                            .html(messageText)
                            .show();
                        
                        $('#dpum-debug-email-subject').val('');
                        if (tinyMCE.get('dpum-debug-email-content')) {
                            tinyMCE.get('dpum-debug-email-content').setContent('');
                        }
                    } else {
                        resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                            .html('<p>' + dpum_vars.error + ' ' + response.data.message + '</p>')
                            .show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('DPUM: Erreur d\'envoi de notifications:', error);
                    spinner.removeClass('is-active');
                    
                    resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                        .html('<p>' + dpum_vars.error + ' ' + error + '</p>')
                        .show();
                },
                complete: function() {
                    isSendingNotifications = false;
                    $button.prop('disabled', false);
                }
            });
        });
        
        /**
         * Gestion des classes actives sur les boutons
         */
        $(document).on('click', '.dpum-search-direct-button', function() {
            $('.dpum-search-direct-button').removeClass('active');
            $(this).addClass('active');
        });
        
        console.log('DPUM: Initialisation des fonctions de débogage terminée');
    });
} else {
    console.log('DPUM: Les fonctions de débogage sont déjà initialisées');
}