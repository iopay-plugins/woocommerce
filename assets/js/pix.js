/* global jQuery */
(function ($) {
  'use strict'

  $(document.body).on('click', '.copy-qr-code', function () {
    /* Get the text field */
    const tempInput = document.createElement('input')
    const copyText = document.getElementById('pixQrCodeInput')
    tempInput.value = copyText.value
    document.body.appendChild(tempInput)
    tempInput.select()
    tempInput.setSelectionRange(0, 99999) /* For mobile devices */
    document.execCommand('copy')
    document.body.removeChild(tempInput)

    $('.qrcode-copyed').show()
  })
}(jQuery))
