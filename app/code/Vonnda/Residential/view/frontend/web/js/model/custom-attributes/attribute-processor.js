/**
 * attribute-processor.js
 */
define([], function () {
    'use strict';

    return {
        /** @property {Object} data */
        data: {},
        /**
         * @param {String} key
         * @param {Array} data
         * @return {Object|null}
         */
        getCustomAttribute: function (key, data) {
            var attr, index, length;

            data = (data instanceof Array)
                ? data
                : [];

            /** @var {Number} length */
            length = data.length;

            /** @var {Number} index */
            for (index = 0; index < length; index += 1) {
                /** @var {Object} attr */
                attr = data[index];

                if (attr['attribute_code'] === attribute) {
                    return attr;
                }
            }

            return null;
        },
        /**
         * @param {String} key
         * @param {String|Boolean|Number|null} value
         * @param {Array} data
         * @return {Object}
         */
        setCustomAttribute: function (key, value, data) {
            var attr, index, length;

            data = (data instanceof Array)
                ? data
                : [];

            /** @var {Number} length */
            length = data.length;

            for (index = 0; index < length; index += 1) {
                /** @var {Object} attr */
                attr = data[index];

                if (attr['attribute_code'] === key) {
                    attr['value'] = value;
                    data[index] = attr;
                }
            }

            return data;
        },
        /**
         * @param {String} needle
         * @param {Array} haystack
         * @return {Boolean}
         */
        hasCustomAttribute: function (needle, haystack) {
            var attr, index, length;

            haystack = (haystack instanceof Array)
                ? haystack
                : [];

            /** @var {Number} length */
            length = haystack.length;

            /** @var {Number} index */
            for (index = 0; index < length; index += 1) {
                /** @var {Object} attr */
                attr = haystack[index];

                if (attr['attribute_code'] === needle) {
                    return true;
                }
            }

            return false;
        }
    };
});
