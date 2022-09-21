/* global wcIopayParams, IoPay */
(function ($) {
    'use strict';




    $(function () {

        $("#billing_phone").mask("(99)99999-9999");
        $("#billing_cellphone").mask("(99)99999-9999");




        $('body').on('click', '#iopay-card-number', function () {

            $("#iopay-card-number").mask("9999 9999 9999 9999");


        });

        $('body').on('click', '#iopay-card-expiry', function () {

            $("#iopay-card-expiry").mask("99/99");


        });

        $('body').on('click', '#iopay-card-cvc', function () {

            $("#iopay-card-cvc").mask("9999");


        });


        /**
         * Process the credit card data when submit the checkout form.
         */
        $('body').on('click', '#place_order', function (event) {
            event.preventDefault();
            if (!$('#payment_method_iopay-credit-card').is(':checked')) {
                
              
                
                 var form = $('form.checkout, form#order_review'),
                        errors = null,
                        creditCardForm = $('#pagarme-credit-cart-form', form),
                        errorHtml = '';
                // Lock the checkout form.
                form.addClass('processing');
                
                form.removeClass('processing');
                
                 form.submit();
                
                return true;
                
            }



            $.post(wcIopayParams.url_iopay_auth, {secret: wcIopayParams.secret, email: wcIopayParams.email, io_seller_id: wcIopayParams.io_seller_id}).done(function (response) {

  var form = $('form.checkout, form#order_review'),
                        errors = null,
                        creditCardForm = $('#iopay-credit-cart-form', form),
                        errorHtml = '';


                var token = response.access_token;
                var url = wcIopayParams.url_iopay_tokenize;

                // Set the Credit card data.
                var holderName = $('#iopay-card-holder-name', form).val();
                var expirationMonth = $('#iopay-card-expiry', form).val().replace(/[^\d]/g, '').substr(0, 2);
                var expirationYear = $('#iopay-card-expiry', form).val().replace(/[^\d]/g, '').substr(2);
                var cardNumber = $('#iopay-card-number', form).val().replace(/[^\d]/g, '');
                var cardCVV = $('#iopay-card-cvc', form).val();

                $.ajax({
                    url: url,
                    data: {
                        holder_name: holderName,
                        expiration_month: expirationMonth,
                        expiration_year: expirationYear,
                        card_number: cardNumber,
                        security_code: cardCVV
                    },
                    success: function (json) {
                        if (json.error) {


                            // Get the errors.
                            errors = json.error;
                            form.removeClass('processing');
                            $('.woocommerce-error', creditCardForm).remove();

                            errorHtml += '<ul>';
                            $.each(errors, function (key, value) {
                                errorHtml += '<li>' + value + '</li>';
                            });
                            errorHtml += '</ul>';

                            $(".payment_box").prepend('<div class="woocommerce-error">' + errorHtml + '</div>');


                        } else {

                            form.removeClass('processing');
                            $('.woocommerce-error', creditCardForm).remove();


                            var id = json.id;
                            var card_id = json.card.id;
                            var card_brand = json.card.card_brand;
                            var expiration_month = json.card.expiration_month;
                            var expiration_year = json.card.expiration_year;
                            var last4_digits = json.card.last4_digits;



                            // Add the hash input.
                            form.append($('<input name="session_id" type="hidden" />').val(wcIopayParams.session_id));
                            form.append($('<input name="card_id" type="hidden" />').val(card_id));
                            form.append($('<input name="token" type="hidden" />').val(id));
                            form.append($('<input name="card_brand" type="hidden" />').val(card_brand));
                            form.append($('<input name="expiration_month" type="hidden" />').val(expiration_month));
                            form.append($('<input name="expiration_year" type="hidden" />').val(expiration_year));
                            form.append($('<input name="last4_digits" type="hidden" />').val(last4_digits));

                            // Submit the form.
                            form.submit();


                        }




                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                        alert(textStatus, errorThrown);
                    },

                    beforeSend: function (xhr) {
                        xhr.setRequestHeader("Authorization", "Bearer " + token);
                    },
                    type: 'POST',
                    contentType: 'application/x-www-form-urlencoded'
                });






            });




        });
    });

}(jQuery));
