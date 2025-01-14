
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
                type: 'paylabs_payment_seamless',
                component: 'Paylabs_Payment/js/view/payment/method-renderer/checkout-seamless'
            }
        );
        return Component.extend({});
    }
);
