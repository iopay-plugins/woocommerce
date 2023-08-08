/* eslint-disable no-undef */
/* global jQuery */
(function ($) {
  'use strict'

  $(function () {
    $('body').on('click', '#copy_link_iopay', function () {
      // Get the text field
      const copyText = $(this).text()

      copyToClipboard(copyText)

      function copyToClipboard (text) {
        alert('Copiado com sucesso: ' + text)
        const sampleTextarea = document.createElement('textarea')
        document.body.appendChild(sampleTextarea)
        sampleTextarea.value = text // save main text in it
        sampleTextarea.select() // select textarea contenrs
        document.execCommand('copy')
        document.body.removeChild(sampleTextarea)
      }
    })

    $('body').on('blur', '#woocommerce_iopay-banking-ticket_interest_rate_value, #woocommerce_iopay-banking-ticket_expiration_date, #woocommerce_iopay-banking-ticket_late_fee_value', function () {
      const expirationDate = parseInt($('#woocommerce_iopay-banking-ticket_expiration_date').val())
      const lateFeeValue = parseFloat($('#woocommerce_iopay-banking-ticket_late_fee_value').val())
      const interestRateValue = parseFloat($('#woocommerce_iopay-banking-ticket_interest_rate_value').val())

      if (expirationDate > 100) {
        $('#woocommerce_iopay-banking-ticket_expiration_date').val(100)
      }
      if (expirationDate < 0) {
        $('#woocommerce_iopay-banking-ticket_expiration_date').val(0)
      }

      if (lateFeeValue < 0) {
        $('#woocommerce_iopay-banking-ticket_late_fee_value').val(0)
      }

      if (lateFeeValue > 100) {
        $('#woocommerce_iopay-banking-ticket_late_fee_value').val(100)
      }

      if (interestRateValue < 0) {
        $('#woocommerce_iopay-banking-ticket_interest_rate_value').val(0)
      }

      if (interestRateValue > 3.3) {
        $('#woocommerce_iopay-banking-ticket_interest_rate_value').val('3,3')
      }
    })

    $('body').on('change', '#select2-woocommerce_iopay-credit-card_max_installment-container', function () {
      const numberInstallment = $('#select2-woocommerce_iopay-credit-card_max_installment-container').val()
      alert(numberInstallment)
      // $("#dynamicTable_bank").append('teste');

      $(document).on('click', '.remove-tr', function () {
        $(this).parents('tbody').remove()
      })
      $(document).on('click', '.remove-tr2', function () {
        $(this).parents('tr').remove()
      })
    })
  })
}(jQuery))
