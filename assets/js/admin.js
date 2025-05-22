jQuery(document).ready(function($) {
    // Recherche des commandes
    $('#dpum-search-orders').on('click', function() {
        var fileNameInput = $('#dpum-file-name');
        var fileName = fileNameInput.val().trim();
        var spinner = $(this).siblings('.spinner');
        var resultsContainer = $('#dpum-results-container');
        var resultsMessage = $('#dpum-results-message');
        var debugContainer = $('#dpum-debug-info');
        
        // Vérifier que le champ n'est pas vide
        if (fileName === '') {
            resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                .html('<p>' + dpum_vars.error + ' ' + 'Veuillez entrer un nom de fichier à rechercher.' + '</p>')
                .show();
            return;
        }
        
        // Réinitialiser les conteneurs
        resultsContainer.hide();
        resultsMessage.hide();
        if (debugContainer.length) {
            debugContainer.hide();
        }
        
        // Afficher le spinner
        spinner.addClass('is-active');
        
        // Faire la requête AJAX
        $.ajax({
            url: dpum_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'dpum_search_orders',
                nonce: dpum_vars.nonce,
                file_name: fileName
            },
            success: function(response) {
                spinner.removeClass('is-active');
                if (response.success) {
                    // Mettre à jour le compteur
                    $('#dpum-orders-count').text(response.data.count);
                    
                    // Vider la liste
                    var ordersList = $('#dpum-orders-list');
                    ordersList.empty();
                    
                    // Afficher les résultats
                    if (response.data.count > 0) {
                        $.each(response.data.orders, function(index, order) {
                            var downloadNames = [];
                            if (order.downloads && order.downloads.length > 0) {
                                $.each(order.downloads, function(i, download) {
                                    downloadNames.push(download.name);
                                });
                            }
                            
                            // Créer les liens pour la commande et le client
                            var orderLink = '<a href="' + dpum_vars.admin_url + 'post.php?post=' + order.order_id + '&action=edit" target="_blank">#' + order.order_number + '</a>';
                            var customerLink = '<a href="' + dpum_vars.admin_url + 'user-edit.php?user_id=' + (order.customer_id || 0) + '" target="_blank">' + order.customer_name + '</a>';
                            
                            // Si pas d'ID client (guest), afficher juste le nom
                            if (!order.customer_id || order.customer_id == 0) {
                                customerLink = order.customer_name + ' <small>(Invité)</small>';
                            }
                            
                            var row = $('<tr>').append(
                                $('<td>').append(
                                    $('<input>').attr({
                                        type: 'checkbox',
                                        class: 'dpum-order-checkbox',
                                        'data-order-id': order.order_id,
                                        'data-customer-name': order.customer_name,
                                        'data-order-number': order.order_number
                                    })
                                ),
                                $('<td>').html(orderLink),
                                $('<td>').html(customerLink),
                                $('<td>').text(order.customer_email),
                                $('<td>').text(order.formatted_date),
                                $('<td>').text(downloadNames.join(', '))
                            );
                            
                            ordersList.append(row);
                        });
                        
                        // Afficher le conteneur de résultats
                        resultsContainer.show();
                        
                        // Afficher un message de succès
                        resultsMessage.removeClass('notice-error notice-warning').addClass('notice-success')
                            .html('<p>' + response.data.message + '</p>')
                            .show();
                    } else {
                        // Aucun résultat trouvé
                        resultsMessage.removeClass('notice-success notice-error').addClass('notice-warning')
                            .html('<p>' + response.data.message + '</p>')
                            .show();
                    }
                    
                    // Afficher les informations de débogage si disponibles
                    if (response.data.debug && debugContainer.length) {
                        $('#dpum-debug-content').text(JSON.stringify(response.data.debug, null, 2));
                        debugContainer.show();
                    }
                } else {
                    // Afficher le message d'erreur
                    resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                        .html('<p>' + dpum_vars.error + ' ' + response.data.message + '</p>')
                        .show();
                }
            },
            error: function(xhr, status, error) {
                spinner.removeClass('is-active');
                
                // Afficher le message d'erreur
                resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                    .html('<p>' + dpum_vars.error + ' ' + error + '</p>')
                    .show();
            }
        });
    });
    
    // Sélectionner/Désélectionner tout
    $('#dpum-select-all').on('change', function() {
        $('.dpum-order-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Mettre à jour Sélectionner tout si tout est sélectionné manuellement
    $(document).on('change', '.dpum-order-checkbox', function() {
        var allChecked = $('.dpum-order-checkbox:checked').length === $('.dpum-order-checkbox').length;
        $('#dpum-select-all').prop('checked', allChecked);
    });
    
    // Envoyer les notifications
    $('#dpum-send-notifications').on('click', function() {
        var selectedOrders = $('.dpum-order-checkbox:checked');
        var spinner = $(this).siblings('.spinner');
        var resultsMessage = $('#dpum-results-message');
        var subject = $('#dpum-email-subject').val().trim();
        var content = tinyMCE.get('dpum-email-content') ? tinyMCE.get('dpum-email-content').getContent() : $('#dpum-email-content').val();
        var fileName = $('#dpum-file-name').val().trim();
        
        // Vérifier qu'au moins une commande est sélectionnée
        if (selectedOrders.length === 0) {
            resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                .html('<p>' + dpum_vars.error + ' ' + 'Veuillez sélectionner au moins une commande.' + '</p>')
                .show();
            return;
        }
        
        // Vérifier que le sujet n'est pas vide
        if (subject === '') {
            resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                .html('<p>' + dpum_vars.error + ' ' + 'Veuillez entrer un sujet d\'email.' + '</p>')
                .show();
            return;
        }
        
        // Vérifier que le contenu n'est pas vide
        if (content === '') {
            resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                .html('<p>' + dpum_vars.error + ' ' + 'Veuillez entrer un contenu d\'email.' + '</p>')
                .show();
            return;
        }
        
        // Demander confirmation
        if (!confirm(dpum_vars.confirm_send)) {
            return;
        }
        
        // Réinitialiser le message
        resultsMessage.hide();
        
        // Afficher le spinner
        spinner.addClass('is-active');
        
        // Récupérer les IDs des commandes sélectionnées
        var orderIds = [];
        selectedOrders.each(function() {
            orderIds.push($(this).data('order-id'));
        });
        
        // Faire la requête AJAX
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
                spinner.removeClass('is-active');
                
                if (response.success) {
                    // Afficher un message de succès
                    var messageText = '<p><strong>' + response.data.message + '</strong></p>';
                    
                    // Ajouter les détails des commandes concernées
                    messageText += '<p>' + 'Des notes ont été ajoutées aux commandes suivantes :' + '</p>';
                    messageText += '<ul>';
                    
                    selectedOrders.each(function() {
                        var orderNumber = $(this).data('order-number');
                        var customerName = $(this).data('customer-name');
                        messageText += '<li>Commande #' + orderNumber + ' - ' + customerName + '</li>';
                    });
                    
                    messageText += '</ul>';
                    
                    // Ajouter les erreurs si présentes
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
                    
                    // Réinitialiser le formulaire après un envoi réussi
                    $('#dpum-email-subject').val('');
                    if (tinyMCE.get('dpum-email-content')) {
                        tinyMCE.get('dpum-email-content').setContent('');
                    }
                } else {
                    // Afficher le message d'erreur
                    resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                        .html('<p>' + dpum_vars.error + ' ' + response.data.message + '</p>')
                        .show();
                }
            },
            error: function(xhr, status, error) {
                spinner.removeClass('is-active');
                
                // Afficher le message d'erreur
                resultsMessage.removeClass('notice-success notice-warning').addClass('notice-error')
                    .html('<p>' + dpum_vars.error + ' ' + error + '</p>')
                    .show();
            }
        });
    });
    
    // Appuyer sur Entrée dans le champ de recherche lance la recherche
    $('#dpum-file-name').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#dpum-search-orders').click();
        }
    });
    
    // Vérifier s'il y a une recherche directe à effectuer
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('direct_search') === '1' && urlParams.get('pre_search')) {
        // Lancer la recherche immédiatement avec un délai légèrement plus long
        setTimeout(function() {
            $('#dpum-search-orders').click();
        }, 800);
    }
});