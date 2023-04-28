/* global wcIopayParams, IoPay */
(function ($) {
    'use strict';

    $(function () {
        window.iopayAntifraudPublicKey = wcIopayParams2.public_key;
        window.iopaySecurityPlan = wcIopayParams2.plan;
        window.iopaySessionId = wcIopayParams2.session_id; // ID DE SESSÃO [Esse id de sessão também deverá ser enviado na endpoint de criação de uma nova transação no parâmetro 'antifraud_sessid']

        (function () {
            var iopay = document.createElement('script');
            iopay.id = 'iopayjs';
            iopay.type = 'text/javascript';
            iopay.async = true;
            iopay.src = 'https://checkout.iopay.com.br/assets/js/behaviour_security.js';
            var s = document.getElementsByTagName('body')[0];
            s.parentNode.insertBefore(iopay, s);
        })();


    });

}(jQuery));