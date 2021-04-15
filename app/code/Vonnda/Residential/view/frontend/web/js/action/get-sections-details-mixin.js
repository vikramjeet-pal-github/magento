/**
 * get-sections-details-mixin.js
 */
define([
    'mage/utils/wrapper'
], function (wrapper) {
    'use strict';

    return function (getSectionsDetailsAction) {
        return wrapper.wrap(getSectionsDetailsAction, function (originalAction, sections, useCache, messageContainer) {
            return originalAction(sections, false, messageContainer);
        });
    };
});
