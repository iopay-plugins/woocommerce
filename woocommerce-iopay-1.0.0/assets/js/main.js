/* global wcIopayParams, IoPayCheckout */
(function ($) {
    'use strict';

    $(function () {
        
        
        
//        $('#woocommerce_iopay-credit-card_interest_rate_installment_2').hide();
//        $('#woocommerce_iopay-credit-card_interest_rate_installment_2').hide();
       

        $('body').on('click', '#copy_link_iopay', function () {

            // Get the text field
            var copyText = $(this).text();

            // Copy the text inside the text field
            // navigator.clipboard.writeText(copyText);

            copyToClipboard(copyText);

            function copyToClipboard(text) {
                alert('Link copiado com sucesso: ' + text)
                var sampleTextarea = document.createElement("textarea");
                document.body.appendChild(sampleTextarea);
                sampleTextarea.value = text; //save main text in it
                sampleTextarea.select(); //select textarea contenrs
                document.execCommand("copy");
                document.body.removeChild(sampleTextarea);
            }

        });

        $('body').on('blur', '#woocommerce_iopay-banking-ticket_interest_rate_value, #woocommerce_iopay-banking-ticket_expiration_date, #woocommerce_iopay-banking-ticket_late_fee_value', function () {

            var expiration_date = $('#woocommerce_iopay-banking-ticket_expiration_date').val();
            var late_fee_value = $('#woocommerce_iopay-banking-ticket_late_fee_value').val();
            var interest_rate_value = $('#woocommerce_iopay-banking-ticket_interest_rate_value').val();
            
      

            if (expiration_date > 100) {
                $('#woocommerce_iopay-banking-ticket_expiration_date').val(100);
            }
            if (expiration_date < 0) {
                $('#woocommerce_iopay-banking-ticket_expiration_date').val(0);
            }

            if (!$.isNumeric(late_fee_value)) {
                $('#woocommerce_iopay-banking-ticket_late_fee_value').val(0);

            }

            if (late_fee_value < 0) {
                $('#woocommerce_iopay-banking-ticket_late_fee_value').val(0);

            }

            if (late_fee_value > 100) {
                $('#woocommerce_iopay-banking-ticket_late_fee_value').val(100);
            }

            if (!$.isNumeric(interest_rate_value)) {
                $('#woocommerce_iopay-banking-ticket_interest_rate_value').val(0);

            }
            if (interest_rate_value < 0) {
                $('#woocommerce_iopay-banking-ticket_interest_rate_value').val(0);
            }

            if (interest_rate_value > 3.3) {
                $('#woocommerce_iopay-banking-ticket_interest_rate_value').val('3,3');
            }



        });




        $('body').on('change', '#select2-woocommerce_iopay-credit-card_max_installment-container', function () {


            var number_installment = $("#select2-woocommerce_iopay-credit-card_max_installment-container").val();
            alert(number_installment);
            //$("#dynamicTable_bank").append('teste');



            $(document).on('click', '.remove-tr', function () {
                $(this).parents('tbody').remove();
            });
            $(document).on('click', '.remove-tr2', function () {
                $(this).parents('tr').remove();
            });



        });





    });

}(jQuery));








