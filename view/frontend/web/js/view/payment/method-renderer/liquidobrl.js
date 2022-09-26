define(
    [
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'jquery',
    ],
    function (quote, Component, urlBuilder, jQuery) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Liquido_PayIn/payment/liquido-brl-checkout-form'
            },
            afterPlaceOrder: function () {
                jQuery("body").trigger("processStart");
                var url = urlBuilder.build("checkout/liquidobrl/index");
                window.location.href = url;
            },
        });
    }
);
