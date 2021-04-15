define(['jquery',
    'jquery/ui',
    'mage/url'
], function ($, jUI, urlBuilder) {
    "use strict";

    const dQS = query => document.querySelector(query);
    const dQSA = query => document.querySelectorAll(query);

    const renderPromoTableFromList = subscriptionPromos => {
        const table = dQS(".subscription-customer-info-block__promo-list");
        if (subscriptionPromos.length == 0) {
            setEmptyMessageText("No promos for customer");
            return;
        } else {
            setEmptyMessageText("");
        }

        const headerRow = dQS(".subscription-customer-info-block__heading-row");

        Object.keys(subscriptionPromos[0]).map(key => {
            const tableHeader = document.createElement("th");
            tableHeader.innerText = key;
            headerRow.appendChild(tableHeader);
        });

        subscriptionPromos.map(subscriptionPromo => {
            const row = document.createElement("tr");
            for (const field in subscriptionPromo) {
                const cell = document.createElement("td");
                if(field === 'Id'){
                    const deleteButton = createPromoDeleteButton(subscriptionPromo[field]);
                    cell.appendChild(deleteButton);
                } else {
                    cell.innerText = subscriptionPromo[field];
                }
                row.appendChild(cell);
            }
            table.appendChild(row);
        });
    }

    const createPromoDeleteButton = promoId => {
        const deleteButton = document.createElement("button");
        deleteButton.innerText = "Delete";
        deleteButton.classList.add('subscription-customer-info-block__delete-promo-button');
        deleteButton.setAttribute('data-promoId', promoId);
        deleteButton.setAttribute('type', 'button');
        return deleteButton;
    }

    const showSuccessMessage = message => {
        const messageBox = dQS('.subscription-customer-info-block__add-promo-message');
        messageBox.innerHTML = message;
        messageBox.style.color = 'green';
        setTimeout(function () {
            clearMessage();
        }, 5000)
    }

    const showErrorMessage = message => {
        const messageBox = dQS('.subscription-customer-info-block__add-promo-message');
        messageBox.innerHTML = message;
        messageBox.style.color = 'red';
        setTimeout(function () {
            clearMessage();
        }, 5000)
    }

    const setEmptyMessageText = message => {
        const emptyMessage = dQS(".subscription-customer-info-block__promo-empty-list");
        emptyMessage.innerHTML = message;
    }

    const clearMessage = () => {
        const messageBox = dQS('.subscription-customer-info-block__add-promo-message');
        messageBox.innerHTML = "";
    }

    const clearTable = () => {
        const table = dQS(".subscription-customer-info-block__promo-list");
        table.innerHTML = '';
        const tableHeader = document.createElement("tr");
        tableHeader.classList.add('subscription-customer-info-block__heading-row');
        table.appendChild(tableHeader);
    }

    const clearInput = () => {
        const promoInput = dQS(".subscription-customer-info-block__add-promo-input");
        promoInput.selectedIndex = 0;
    }

    const initialize = subscriptionPromos => {
        subscriptionPromos.sort((a,b) => {
            return new Date(a['Created At']) - new Date(b['Created At']);
        });
        renderPromoTableFromList(subscriptionPromos);

        const addPromoButton = dQS(".subscription-customer-info-block__add-promo-button");
        addPromoButton.onclick = function () {
            const promoInput = dQS(".subscription-customer-info-block__add-promo-input");
            const value = promoInput.options[promoInput.selectedIndex].value;
            const subscriptionCustomerId = event.target.getAttribute('data-subscriptioncustomerid');
            submitSubscriptionPromo(subscriptionCustomerId, value, 'promo');
        }

        const addCouponButton = dQS(".subscription-customer-info-block__add-coupon-button");
        addCouponButton.onclick = function () {
            const couponInput = dQS(".subscription-customer-info-block__add-coupon-input");
            const value = couponInput.value;
            const subscriptionCustomerId = event.target.getAttribute('data-subscriptioncustomerid');
            submitSubscriptionPromo(subscriptionCustomerId, value, 'coupon');
        }

        setClickListenersOnDeleteButtons();
    }

    const setClickListenersOnDeleteButtons = () => {
        const deletePromoButtons = dQSA(".subscription-customer-info-block__delete-promo-button");
        deletePromoButtons.forEach(deleteButton => {
            deleteButton.onclick = function(e){
                const promoId = e.target.getAttribute('data-promoId');
                deleteSubscriptionPromo(window.subscriptionCustomerId, promoId);
            }
        });
    }

    const submitSubscriptionPromo = (subscriptionCustomerId, value, type) => {
        const postData = {
            subscriptionCustomerId: subscriptionCustomerId,
            value: value,
            type: type,
            form_key: window.FORM_KEY
        }

        const request = $.ajax({
            type: 'POST',
            url: window.backEndPromoUrl,
            data: postData
        })
            .success(function (data) {
                if (data.Status === 'success') {
                    clearTable();
                    clearInput();
                    window.subscriptionPromos = 
                        [...window.subscriptionPromos, data.subscriptionPromo].sort((a,b) => {
                        return new Date(a['Created At']) - new Date(b['Created At']);
                    });
                    renderPromoTableFromList(window.subscriptionPromos);
                    setClickListenersOnDeleteButtons();
                    showSuccessMessage("Promo code succesfully added");
                } else {
                    showErrorMessage(data.message);
                }
            })
            .error(function (err) {
                console.log(err.message);
                showErrorMessage("There was an error, try again later");
            });
    }

    const deleteSubscriptionPromo = (subscriptionCustomerId, subscriptionPromoId) => {        
        const postData = {
            subscriptionPromoId: subscriptionPromoId,
            subscriptionCustomerId: subscriptionCustomerId
        }

        const request = $.ajax({
            type: 'POST',
            url: window.backEndDeletePromoUrl,
            data: postData
        })
            .success(function (data) {
                if (data.Status === 'success') {
                    clearTable();
                    clearInput();
                    window.subscriptionPromos = [...window.subscriptionPromos].filter( promo => {
                        return promo.Id != data.subscriptionPromo.Id;
                    }).sort((a,b) => {
                        return new Date(a['Created At']) - new Date(b['Created At']);
                    });
                    renderPromoTableFromList(window.subscriptionPromos);
                    setClickListenersOnDeleteButtons();
                    showSuccessMessage("Promo code succesfully deleted");
                } else {
                    showErrorMessage(data.message);
                }
            })
            .error(function (err) {
                console.log(err.message);
                showErrorMessage("There was an error, try again later");
            });
    }

    initialize(window.subscriptionPromos);
});