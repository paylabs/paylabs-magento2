define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Magento_Ui/js/model/messageList'
    ],
    function (Component, $, quote, urlBuilder, storage, errorProcessor, customer, fullScreenLoader, additionalValidators, url, messageList) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Paylabs_Payment/payment/checkout_seamless',
                redirectAfterPlaceOrder: false
            },

            getIcon: function () {
                return require.toUrl('Paylabs_Payment/images/methods/paylabs-logo.svg');
            },

            afterPlaceOrder: function () {
                const self = this;
                
                // Validasi data sebelum membuat payload
                if (!quote.getQuoteId() || !quote.totals() || !quote.totals().grand_total) {
                    messageList.addErrorMessage({ message: 'Unable to process payment due to missing data.' });
                    return; // Hentikan eksekusi jika data tidak lengkap
                }
                
                console.log("OK 1");

                // Show a loader while processing
                fullScreenLoader.startLoader();

                const payload = {
                    paymentMethod: {
                        method: self.getCode(),
                    },
                    orderData: {
                        // Add necessary data from the quote here
                        quote_id: quote.getQuoteId(),
                        grand_total: quote.totals().grand_total,
                        currency: quote.totals().quote_currency_code,
                        customer_email: quote.guestEmail || customer.customerData.email,
                    }
                };
                console.log(payload);

                $.ajax({
                    url: url.build('paylabs/payment/checkout'),
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(payload),
                    success: function (response) {
                        console.log('res :', response);
                        if (response && response.status_code === 201 && response.payment_url) {
                            window.location.href = response.payment_url;
                        } else if (response && response.message) {
                            messageList.addErrorMessage({ message: response.message });
                        } else {
                            messageList.addErrorMessage({ message: 'An unexpected error occurred. Please try again later.' });
                        }
                    },
                    error: function () {
                        messageList.addErrorMessage({ message: 'An error occurred during the payment process.' });
                    },
                    complete: function () {
                        fullScreenLoader.stopLoader();
                    }
                });
            },
        });
    }
);
