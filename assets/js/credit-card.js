/* global wcIopayParams, IoPay */
(function ($) {

    function validateHolderName(holderName) {
        var validateHolderName = /\w{3,}/.test(holderName);

        return validateHolderName;
    }

    function validateCardCVV(cardCVV) {
        var validateCardCVV = /\d{3,}/.test(cardCVV);

        return validateCardCVV;
    }

    function validateCardnum(cardNumber) {
        var validateCardnum = /\d{12,}/.test(cardNumber);

        if (validateCardnum && cardNumber.charAt(0) !== '0') {
            // Luhn test for card number
            var nCheck = 0;
            var even = false;

            for (var n = cardNumber.length - 1; n >= 0; n--) {
                var cDigit = cardNumber.charAt(n);
                var nDigit = parseInt(cDigit, 10);

                if (even && (nDigit *= 2) > 9) {
                    nDigit -= 9;
                }

                nCheck += nDigit;
                even = !even;
            }

            // End Luhn test

            if ((nCheck % 10) === 0 && nCheck > 0) {
                return true;
            }
        }

        return false;
    }

    function validateExpDate(expirationMonth, expirationYear) {
        var validateExpMonth = /\d{2,}/.test(expirationMonth);
        var validateExpYear = /\d{2,}/.test(expirationYear);
        var dateToday = new Date();

        if (validateExpYear) {
            var actualYear = dateToday.getFullYear();
            expirationYear = parseInt(actualYear.toString().substr(0, 2) + expirationYear);

            // Verify if year is valid
            if (expirationYear >= actualYear) {
                // Verify if month is valid too
                if (validateExpMonth) {
                    expirationMonth = parseInt(expirationMonth);

                    if (expirationMonth > 0 && expirationMonth < 13) {
                        var actualMonth = dateToday.getMonth() + 1;

                        // Verify if the card is expired
                        if (expirationMonth >= actualMonth) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    $(function () {

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
                    creditCardForm = $('#iopay-credit-cart-form', form),
                    errorHtml = '';
                // Lock the checkout form.
                form.addClass('processing');

                form.removeClass('processing');

                form.submit();

                return true;

            }

            var inputCardHolder = document.getElementById('iopay-card-holder-name');
            inputCardHolder.classList.remove('iopay-input-error');

            var inputCardExpiry = document.getElementById('iopay-card-expiry');
            inputCardExpiry.classList.remove('iopay-input-error');

            var inputCardNum = document.getElementById('iopay-card-number');
            inputCardNum.classList.remove('iopay-input-error');

            var inputCvv = document.getElementById('iopay-card-cvc');
            inputCvv.classList.remove('iopay-input-error');

            $.post(wcIopayParams.url_iopay_auth, { session: wcIopayParams.session_id, auth: wcIopayParams.auth_key }).done(function (response) {
                var form = $('form.checkout, form#order_review'),
                    errors = null,
                    creditCardForm = $('#iopay-credit-cart-form', form),
                    errorHtml = '',
                    errorDiv = $(".woocommerce"),
                    scrollabeErrorDiv = document.getElementsByClassName("woocommerce")[0];

                var token = response.token;
                var url = wcIopayParams.url_iopay_tokenize;

                // Set the Credit card data.
                var holderName = $('#iopay-card-holder-name', form).val();
                var expirationMonth = $('#iopay-card-expiry', form).val().replace(/[^\d]/g, '').substr(0, 2);
                var expirationYear = $('#iopay-card-expiry', form).val().replace(/[^\d]/g, '').substr(2);
                var cardNumber = $('#iopay-card-number', form).val().replace(/[^\d]/g, '');
                var cardCVV = $('#iopay-card-cvc', form).val();

                // Clean errors
                $('.woocommerce-error').remove();

                if (!validateHolderName(holderName)) {
                    errorDiv.prepend('<div class="woocommerce-error">Campo do titular do cartão inválido</div>');
                    var inputCardHolder = document.getElementById('iopay-card-holder-name');
                    inputCardHolder.classList.add('iopay-input-error');
                    scrollabeErrorDiv.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });

                    return false;
                } else if (!validateCardnum(cardNumber)) {
                    errorDiv.prepend('<div class="woocommerce-error">Número de cartão de crédito inválido</div>');
                    var inputCardNum = document.getElementById('iopay-card-number');
                    inputCardNum.classList.add('iopay-input-error');
                    scrollabeErrorDiv.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });

                    return false;
                } else if (!validateExpDate(expirationMonth, expirationYear)) {
                    errorDiv.prepend('<div class="woocommerce-error">Cartão expirado ou data inválida</div>');
                    var inputCardExpiry = document.getElementById('iopay-card-expiry');
                    inputCardExpiry.classList.add('iopay-input-error');
                    scrollabeErrorDiv.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });

                    return false;
                } else if (!validateCardCVV(cardCVV)) {
                    errorDiv.prepend('<div class="woocommerce-error">Número de CVV inválido</div>');
                    var inputCvv = document.getElementById('iopay-card-cvc');
                    inputCvv.classList.add('iopay-input-error');
                    scrollabeErrorDiv.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });

                    return false;
                } else {
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

                    return true;
                }
            });

        });
    });

}(jQuery));
