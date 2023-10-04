/* global wcIopayParams, jQuery */
(function ($) {
  function validateHolderName (holderName) {
    const validateHolderName = /\w{3,}/.test(holderName)

    return validateHolderName
  }

  function validateCardCVV (cardCVV) {
    const validateCardCVV = /\d{3,}/.test(cardCVV)

    return validateCardCVV
  }

  function validateCardnum (cardNumber) {
    const validateCardnum = /\d{12,}/.test(cardNumber)

    if (validateCardnum && cardNumber.charAt(0) !== '0') {
      // Luhn test for card number
      let nCheck = 0
      let even = false

      for (let n = cardNumber.length - 1; n >= 0; n--) {
        const cDigit = cardNumber.charAt(n)
        let nDigit = parseInt(cDigit, 10)

        if (even && (nDigit *= 2) > 9) {
          nDigit -= 9
        }

        nCheck += nDigit
        even = !even
      }

      // End Luhn test

      if ((nCheck % 10) === 0 && nCheck > 0) {
        return true
      }
    }

    return false
  }

  function validateExpDate (expirationMonth, expirationYear) {
    const validateExpMonth = /\d{2,}/.test(expirationMonth)
    const validateExpYear = /\d{2,}/.test(expirationYear)
    const dateToday = new Date()

    if (validateExpYear) {
      const actualYear = dateToday.getFullYear()
      expirationYear = parseInt(actualYear.toString().substr(0, 2) + expirationYear)

      // Verify if year is valid
      if (expirationYear >= actualYear) {
        // Verify if month is valid too
        if (validateExpMonth) {
          expirationMonth = parseInt(expirationMonth)

          if (expirationMonth > 0 && expirationMonth < 13) {
            const actualMonth = dateToday.getMonth() + 1

            // Verify if the card is expired
            if (expirationMonth >= actualMonth) {
              return true
            }
          }
        }
      }
    }

    return false
  }

  $(function () {
    $('body').on('click', '#iopay-card-number', function () {
      $('#iopay-card-number').mask('9999 9999 9999 9999')
    })

    $('body').on('click', '#iopay-card-expiry', function () {
      $('#iopay-card-expiry').mask('99/99')
    })

    $('body').on('click', '#iopay-card-cvc', function () {
      $('#iopay-card-cvc').mask('9999')
    })

    /**
         * Process the credit card data when submit the checkout form.
         */
    $('body').on('click', '#place_order', function (event) {
      event.preventDefault()

      const recurrencyWsp = document.getElementsByClassName('wps_wsp_recurring_total')[0]
      let hasRecurrency = 'no'

      if (recurrencyWsp) {
        hasRecurrency = 'yes'
      }

      if (!$('#payment_method_iopay-credit-card').is(':checked')) {
        const form = $('form.checkout, form#order_review')
        // Lock the checkout form.
        form.addClass('processing')

        form.removeClass('processing')

        form.submit()

        return true
      }

      const inputCardHolder = document.getElementById('iopay-card-holder-name')
      inputCardHolder.classList.remove('iopay-input-error')

      const inputCardExpiry = document.getElementById('iopay-card-expiry')
      inputCardExpiry.classList.remove('iopay-input-error')

      const inputCardNum = document.getElementById('iopay-card-number')
      inputCardNum.classList.remove('iopay-input-error')

      const inputCvv = document.getElementById('iopay-card-cvc')
      inputCvv.classList.remove('iopay-input-error')

      $.post(wcIopayParams.url_iopay_auth, { session: wcIopayParams.session_id, auth: wcIopayParams.auth_key }).done(function (response) {
        const form = $('form.checkout, form#order_review'),
          errorDiv = $('.woocommerce'),
          scrollabeErrorDiv = document.getElementsByClassName('woocommerce')[0],
          creditCardForm = $('#iopay-credit-cart-form', form)

        let errors = null,
          errorHtml = ''

        const token = response.token
        const url = wcIopayParams.url_iopay_tokenize

        // Set the Credit card data.
        const holderName = $('#iopay-card-holder-name', form).val()
        const expirationMonth = $('#iopay-card-expiry', form).val().replace(/[^\d]/g, '').substr(0, 2)
        const expirationYear = $('#iopay-card-expiry', form).val().replace(/[^\d]/g, '').substr(2)
        const cardNumber = $('#iopay-card-number', form).val().replace(/[^\d]/g, '')
        const cardCVV = $('#iopay-card-cvc', form).val()

        // Clean errors
        $('.woocommerce-error').remove()

        if (!validateHolderName(holderName)) {
          errorDiv.prepend('<div class="woocommerce-error">Campo do titular do cartão inválido</div>')
          const inputCardHolder = document.getElementById('iopay-card-holder-name')
          inputCardHolder.classList.add('iopay-input-error')
          scrollabeErrorDiv.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' })

          return false
        } else if (!validateCardnum(cardNumber)) {
          errorDiv.prepend('<div class="woocommerce-error">Número de cartão de crédito inválido</div>')
          const inputCardNum = document.getElementById('iopay-card-number')
          inputCardNum.classList.add('iopay-input-error')
          scrollabeErrorDiv.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' })

          return false
        } else if (!validateExpDate(expirationMonth, expirationYear)) {
          errorDiv.prepend('<div class="woocommerce-error">Cartão expirado ou data inválida</div>')
          const inputCardExpiry = document.getElementById('iopay-card-expiry')
          inputCardExpiry.classList.add('iopay-input-error')
          scrollabeErrorDiv.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' })

          return false
        } else if (!validateCardCVV(cardCVV)) {
          errorDiv.prepend('<div class="woocommerce-error">Número de CVV inválido</div>')
          const inputCvv = document.getElementById('iopay-card-cvc')
          inputCvv.classList.add('iopay-input-error')
          scrollabeErrorDiv.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' })

          return false
        } else {
          $.ajax({
            url,
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
                errors = json.error
                form.removeClass('processing')
                $('.woocommerce-error', creditCardForm).remove()

                errorHtml += '<ul>'
                $.each(errors, function (key, value) {
                  errorHtml += '<li>' + value + '</li>'
                })
                errorHtml += '</ul>'

                $('.payment_box').prepend('<div class="woocommerce-error">' + errorHtml + '</div>')
              } else {
                form.removeClass('processing')
                $('.woocommerce-error', creditCardForm).remove()

                const id = json.id
                const cardId = json.card.id
                const cardBrand = json.card.card_brand
                const expirationMonth = json.card.expiration_month
                const expirationYear = json.card.expiration_year
                const last4Digits = json.card.last4_digits

                // Add the hash input.
                form.append($('<input name="session_id" type="hidden" />').val(wcIopayParams.session_id))
                form.append($('<input name="card_id" type="hidden" />').val(cardId))
                form.append($('<input name="token" type="hidden" />').val(id))
                form.append($('<input name="card_brand" type="hidden" />').val(cardBrand))
                form.append($('<input name="expiration_month" type="hidden" />').val(expirationMonth))
                form.append($('<input name="expiration_year" type="hidden" />').val(expirationYear))
                form.append($('<input name="last4_digits" type="hidden" />').val(last4Digits))
                form.append($('<input name="wsp_recurring" type="hidden" />').val(hasRecurrency))

                // Submit the form.
                form.submit()
              }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
              // eslint-disable-next-line no-undef
              alert(textStatus, errorThrown)
            },

            beforeSend: function (xhr) {
              xhr.setRequestHeader('Authorization', 'Bearer ' + token)
            },
            type: 'POST',
            contentType: 'application/x-www-form-urlencoded'
          })

          return true
        }
      })
    })
  })
}(jQuery))
