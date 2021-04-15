define(['jquery',
        'jquery/ui',
        'mage/url'
], function ($, jUI, urlBuilder) {
    "use strict";
    const subscriptionPlanPromos = window.subscriptionPlanPromos;
    const initialPromos = [...window.subscriptionPlanPromos];
    const promos = [...window.subscriptionPlanPromos];

    const dQS = query => document.querySelector(query);
    const dQSA = query => document.querySelectorAll(query);
    //Mutable array rearrangement
    const moveArrayElement = offset => array => currentIndex => {
        const newIndex = currentIndex + offset;
        if(newIndex > -1 && newIndex < array.length){
            const element = array.splice(currentIndex, 1)[0];
            array.splice(newIndex, 0, element);
        }
    }

    const moveUp = array => currentIndex => moveArrayElement(-1)(array)(currentIndex);
    const moveDown = array => currentIndex => moveArrayElement(1)(array)(currentIndex);

    const renderPromoTableFromList = promos => {
        const table = dQS(".subscription-plan-promo-chooser__promo-list");
        if (promos.length == 0) {
            setEmptyMessageText("No promos for tier");
            return;
        } else {
            setEmptyMessageText("");
        }

        const headerRow = dQS(".subscription-plan-promo-chooser__heading-row");

        Object.keys(promos[0]).map(key => {
            const tableHeader = document.createElement("th");
            if(key === 'id'){
                tableHeader.innerText = '';
            } else {
                tableHeader.innerText = key;
            }
            headerRow.appendChild(tableHeader);
        });

        let index = 0;

        promos.forEach(subscriptionPromo => {
            const row = document.createElement("tr");
            for (const field in subscriptionPromo) {
                const cell = document.createElement("td");
                if(field === 'id'){
                    //TODO - break into function
                    const deleteButton = createPromoDeleteButton(subscriptionPromo[field], index);
                    const upButton = createPromoUpButton(index);
                    const downButton = createPromoDownButton(index);
                    cell.appendChild(deleteButton);
                    cell.appendChild(upButton);
                    cell.appendChild(downButton);
                } else {
                    cell.innerText = subscriptionPromo[field];
                }
                row.appendChild(cell);
            }
            table.appendChild(row);
            index = index + 1;
        });

    }

    //TODO - dry this up
    const createPromoDeleteButton = (ruleid, index) => {
        const deleteButton = document.createElement("button");
        deleteButton.innerText = "Delete";
        deleteButton.classList.add('subscription-plan-promo-chooser__delete-promo-button');
        deleteButton.setAttribute('data-ruleid', ruleid);
        deleteButton.setAttribute('data-index', index);
        deleteButton.setAttribute('type', 'button');
        return deleteButton;
    }

    const createPromoUpButton = index => {
        const upButton = document.createElement("button");
        upButton.innerText = "Up";
        upButton.classList.add('subscription-plan-promo-chooser__up-button');
        upButton.setAttribute('data-index', index);
        upButton.setAttribute('type', 'button');
        return upButton;
    }

    const createPromoDownButton = index => {
        const downButton = document.createElement("button");
        downButton.innerText = "Down";
        downButton.classList.add('subscription-plan-promo-chooser__down-button');
        downButton.setAttribute('data-index', index);
        downButton.setAttribute('type', 'button');
        return downButton;
    }

    const setEmptyMessageText = message => {
        const emptyMessage = dQS(".subscription-plan-promo-chooser__promo-empty-list");
        emptyMessage.innerHTML = message;
    }

    const clearTable = () => {
        const table = dQS(".subscription-plan-promo-chooser__promo-list");
        table.innerHTML = '';
        const tableHeader = document.createElement("tr");
        tableHeader.classList.add('subscription-plan-promo-chooser__heading-row');
        table.appendChild(tableHeader);
    }

    const clearInput = () => {
        const promoInput = dQS(".subscription-plan-promo-chooser__add-promo-input");
        promoInput.selectedIndex = 0;
    }

    const clearChooser = () => (clearTable(), clearInput());

    const setDefaultRuleIds = value => $('input[name="subscriptionPlan[default_promo_ids]"]').val(value).change();

    const setInputFromPromoArray = promos => {
        if(promos.length > 0){
            const idString = promos.map(element => element.id)
                                                   .reduce((acc,el) => `${acc},${el}`);
            return setDefaultRuleIds(idString);
        }
        return setDefaultRuleIds('');
    }

    //TODO - rename this
    const setAll = promos => {
        clearChooser();
        renderPromoTableFromList(promos);
        setInputFromPromoArray(promos);
        setGridClickListeners(promos);
    }

    const setGridClickListeners = promos => {
        setClickListenersOnDeleteButtons(promos);
        setClickListenersOnUpButtons(promos);
        setClickListenersOnDownButtons(promos);
    }

    const initialize = promos => {
        renderPromoTableFromList(promos);

        const addPromoButton = dQS(".subscription-plan-promo-chooser__add-promo-button");
        addPromoButton.onclick = function () {
            const promoInput = dQS(".subscription-plan-promo-chooser__add-promo-input");
            const selectedOption = promoInput.options[promoInput.selectedIndex];
            const id = selectedOption.value;
            if(!id){return;}
            const name = selectedOption.getAttribute('data-rulename');
            const description = selectedOption.getAttribute('data-ruledescription');
            promos.push({id, name, description});
            setAll(promos);
        }

        const resetPromoButton = dQS(".subscription-plan-promo-chooser__reset-button");
        resetPromoButton.onclick = function () {
            promos = [...initialPromos];
            setAll(promos);
        }

        setGridClickListeners(promos);
    }

    //TODO - dry up
    const setClickListenersOnDeleteButtons = promos => {
        const deletePromoButtons = dQSA(".subscription-plan-promo-chooser__delete-promo-button");
        deletePromoButtons.forEach(deleteButton => {
            deleteButton.onclick = function(e){
                removePromo(e.target.getAttribute('data-index'), promos);
            }
        });
    }

    const setClickListenersOnUpButtons = promos => {
        const upButtons = dQSA(".subscription-plan-promo-chooser__up-button");
        upButtons.forEach(upButton => {
            upButton.onclick = function(e){
                moveRowUp(e.target.getAttribute('data-index'), promos);
            }
        });
    }

    const setClickListenersOnDownButtons = promos => {
        const downButtons = dQSA(".subscription-plan-promo-chooser__down-button");
        downButtons.forEach(downButton => {
            downButton.onclick = function(e){
                moveRowDown(e.target.getAttribute('data-index'), promos);
            }
        });
    }

    //TODO - dry up
    const removePromo = (index, promos) => {
        if(index == 0 ){
            promos.shift();
        } else {
            promos.splice(index, index);
        }
        setAll(promos);
    }

    const moveRowUp= (index, promos) => {
        if(index == 0 ){
            return;
        } else {
            moveUp(promos)(parseInt(index));
        }
        setAll(promos);
    }

    const moveRowDown = (index, promos) => {
        if(promos.length == parseInt(index) + 1 ){
            return;
        } else {
            moveDown(promos)(parseInt(index));
        }
        setAll(promos);
    }

    initialize(promos);
});