/**
 * @file
 * Defines behaviors for the Omise payment method form..
 */

(function ($, Drupal, drupalSettings, omise, omiseCard) {

    'use strict';

    /**
     * Attaches the commerceOmiseForm behavior.
     *
     * @type {Drupal~behavior}
     *
     * @prop {Drupal~behaviorAttach} attach
     *   Attaches the commerceomiseForm behavior.
     */
    Drupal.behaviors.commerceOmiseForm = {
        attach: function (context) {
            // Check requirements to continue. Using JS integration the form will be prebuilt on Omise (with iFrame)
            if (!drupalSettings.commerceOmise || !drupalSettings.commerceOmise.publicKey || drupalSettings.commerceOmise.useJS) {
                var omiseIsVisible=$('#omise-card-button').is(":visible");
                if (drupalSettings.commerceOmise.useJS && omiseIsVisible) {

                    //var _formId = '#omise-checkout-collect-card-form'; // Rendered from CheckoutCollectCardForm
                    var _formId = '.omise-customer'; // Rendered from original form
                    // Set default parameters
                    omiseCard.configure({
                        publicKey: drupalSettings.commerceOmise.publicKey,
                        image: 'https://cdn.omise.co/assets/dashboard/images/omise-logo.png',
                        amount: drupalSettings.commerceOmise.amount,
                        submitFormTarget: _formId
                    });
                    // Configuring your own custom button
                    omiseCard.configureButton('#omise-card-button', {
                        frameLabel: drupalSettings.commerceOmise.frameLabel,
                        submitLabel: drupalSettings.commerceOmise.buttonLabel
                    });
                    // Then, attach all of the config and initiate it by 'OmiseCard.attach();' method
                    omiseCard.attach();


                }
                return;
            }
            $('.omise-form', context).once('omise-processed').each(function () {

                var $form = $('.omise-form', context).closest('form');

                // Clear the token every time the payment form is loaded. We only need the token
                // one time, as it is submitted to Omise after a card is validated. If this
                // form reloads it's due to an error; received tokens are stored in the checkout pane.
                $('#omise_token').val('');
                omise.setPublicKey(drupalSettings.commerceOmise.publicKey);
                var omiseResponseHandler = function (status, response) {
                    if (status === 200) {
                        // Token contains id, last4, and card type.
                        var token = response.id;
                        // Insert the token into the form so it gets submitted to the server.
                        $('#omise_token').val(token);
                        // Do not send card details to server
                        $('.card-number').removeAttr('name');
                        $('.card-expiry-month').removeAttr('name');
                        $('.card-expiry-year').removeAttr('name');
                        $('.card-cvc').removeAttr('name');
                        // Submit only if is checkout.
                        if ($form.hasClass('has-checkout')) {
                            $form.get(0).submit();
                        } else {
                            // We are just collecting card
                            // Save token on parent form
                            $('.omise-token').val(token);
                            var _slicedCard = $('.card-number').val().slice(-4);

                            // When input has specific class "with-card" we must set value as TOKEN|CC_NUMBER
                            $('.omise-token.with-card').val(token + "|" + _slicedCard);
                            // Hide trigger button and add html info
                            $('.omise-trigger').hide();
                            $('.omise-trigger').after("<p>Card succesfully added on Omise: **********" + _slicedCard + "</p>");

                            // Close modal
                            $('#drupal-modal').modal('hide');

                        }
                    }
                    else {
                        // Show the errors on the form
                        $form.find('.payment-errors').text(response.message);
                        $form.find('button').prop('disabled', false);
                    }
                };

                $form.submit(function (e) {
                    var $form = $(this);
                    var card_number = $('.card-number').val();
                    var card_expiry_month = $('.card-expiry-month').val();
                    var card_expiry_year = $('.card-expiry-year').val();
                    var card_cvc = $('.card-cvc').val();
                    var card_name = $('.holder-name').val();
                    var cardObject = {
                        name: card_name,
                        number: card_number,
                        expiration_month: card_expiry_month,
                        expiration_year: card_expiry_year,
                        security_code: card_cvc
                    };
                    // Disable the submit button to prevent repeated clicks
                    $form.find('button').prop('disabled', true);

                    omise.createToken('card', cardObject, omiseResponseHandler);

                    // Prevent the form from submitting with the default action.
                    if ($('.card-number').length) {
                        return false;
                    }
                });
            });


        }
    };

    // Remove card action
    $('.remove-omise-card').click(function (e) {
        e.preventDefault();
        var _buttonClicked = $(this);
        var _buttonClickedContainer = $(this).parents().first('.panel-body');
        var params = {
            "customer": _buttonClicked.attr("customer"),
            "card": _buttonClicked.attr("id")
        };
        $.ajax({
            type: "POST",
            url: drupalSettings.commerceOmise.removeCardCallback,
            data: params,
            success: function (response) {
                if (response.result == "success") {
                    _buttonClicked.parent().remove();
                    _buttonClickedContainer.parent().html('<div>' + response.data.message + '</div>');
                } else {
                    _buttonClicked.parent().append('<div style="color:red;">' + response.data.message + '</div>');
                }
            }
        });
    });

    // Remove bank account action
    $('.remove-omise-bank-account').click(function (e) {
        e.preventDefault();
        var _buttonClicked = $(this);
        var _buttonClickedContainer = $(this).parents().first('.panel-body');
        var _oldValue = _buttonClicked.attr("id");
        var params = {
            "recipient": _buttonClicked.attr("id"),
            "entityType": _buttonClicked.attr("entityType"),
            "entityId": _buttonClicked.attr("entityId"),
            "entityField": _buttonClicked.attr("entityField")
        };
        $.ajax({
            type: "POST",
            url: drupalSettings.commerceOmise.removeBankAccountCallback,
            data: params,
            success: function (response) {
                if (response.result == "success") {
                    // Empty input element and force submit
                    _buttonClicked.parents().find('input[value="' + _oldValue + '"]').val('');
                    _buttonClicked.parent().hide();
                    _buttonClickedContainer.parent().html('<div>' + response.data.message + '. The page will reload... Please wait.</div>');


                    location.reload();
                } else {
                    _buttonClicked.parent().append('<div style="color:red;">' + response.data.message + '</div>');
                }
            }
        });
    });

    $.extend(Drupal.theme, /** @lends Drupal.theme */{
        commerceomiseError: function (message) {
            return $('<div class="messages messages--error"></div>').html(message);
        }
    });

})(jQuery, Drupal, drupalSettings, window.Omise, window.OmiseCard);
