define(
    [
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/view/payment/default',
        'mage/storage',
        'mage/url',
        'jquery',
    ],
    function (quote, Component, storage, urlBuilder, jQuery) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Liquido_PayIn/payment/liquido-checkout-form'
            },
            afterPlaceOrder: function () {
                jQuery("body").trigger("processStart"); 
                var serviceUrl = urlBuilder.build('checkout/storecountry/country');
                storage.get(serviceUrl).done(
                    function(response) {
                        if (response.success) {
                            var url = '';
                            var country = response.value;
                            if (country == 'BR') {
                                url = urlBuilder.build("checkout/liquidobrl/index");
                            }

                            if (country == 'CO') {
                                url = urlBuilder.build("checkout/liquidoco/index");
                            }

                            window.location.href = url;
                        }
                    }
                ).fail(
                    function(response) {
                        console.log(response.value);
                    }
                )
            },
        });
    }
);
