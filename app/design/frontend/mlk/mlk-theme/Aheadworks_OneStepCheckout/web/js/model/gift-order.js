/**
 * Copyright 2019 aheadWorks. All rights reserved.\nSee LICENSE.txt for license details.
 */

define(
    [
        'jquery',
        'ko',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
    ],
    function ($, ko, customer, quote, fullScreenLoader) {
        'use strict';

        var handleGiftOrderClickSuccess = function() {
            location.reload(true);
        }

        var handleGiftOrderClickFailure = function(data) {
            console.error("Failure: ", data);

        }

        return {
            isGiftOrder:function() {
                var giftOrder = window.checkoutConfig.quoteData.gift_order;
                if(!giftOrder || giftOrder === "0"){
                    return false
                }
                
                return true;
            },
            
            handleGiftOrderClick: function() {
                var url = window.BASE_URL + "checkout/onepage/togglegiftorder"
                var data = JSON.stringify({test: true});
                
                fullScreenLoader.startLoader();
                
                var request = $.ajax({
                    type: 'POST',
                    url: url,
                    data: data
                })
                .success(function (data) {
                    const checkbox = document.querySelector("#is_gift_order");
                    checkbox.checked = !checkbox.checked;
                    if (!data.errors) {
                        return handleGiftOrderClickSuccess();
                    }

                    handleGiftOrderClickFailure(data);
                    fullScreenLoader.stopLoader();
                })
                .error(function (err) {
                    handleGiftOrderClickFailure(err);
                    console.log(err);
                    fullScreenLoader.stopLoader();
                });
            }
        };
    }
);
