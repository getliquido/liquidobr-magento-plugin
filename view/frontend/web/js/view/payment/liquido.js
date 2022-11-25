define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'liquido',
                component: 'Liquido_PayIn/js/view/payment/method-renderer/liquido'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);