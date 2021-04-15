/**
 * VONNDA-UPDATE: this was modified to follow cognito password requirements.
 * ideally this should be in the cognito extension and not the theme.
 */
define([
    'jquery',
    'mage/translate',
    'mage/validation'
], function($, $t) {
    'use strict';

    $.widget('mage.passwordStrengthIndicator', {
        options: {
            cache: {},
            passwordSelector: '[type=password]',
            passwordStrengthMeterSelector: '[data-role=password-strength-meter]',
            passwordStrengthMeterLabelSelector: '[data-role=password-strength-meter-label]',
            formSelector: 'form',
            emailSelector: 'input[type="email"]'
        },
        requirements: {
            length: 8,
            uppercase: 1,
            lowercase: 1,
            number: 1,
            special: 1
        },

        /**
         * Widget initialization
         * @private
         */
        _create: function() {
            this.options.cache.input = $(this.options.passwordSelector, this.element);
            this.options.cache.meter = $(this.options.passwordStrengthMeterSelector, this.element);
            this.options.cache.label = $(this.options.passwordStrengthMeterLabelSelector, this.element);

            // We need to look outside the module for backward compatibility, since someone can already use the module.
            // @todo Narrow this selector in 2.3 so it doesn't accidentally finds the email field from the
            // newsletter email field or any other "email" field.
            this.options.cache.email = $(this.options.formSelector).find(this.options.emailSelector);
            this.options.cache.input.data('score', 0);
            this._bind();
        },

        /**
         * Event binding, will monitor change, keyup and paste events.
         * @private
         */
        _bind: function() {
            this._on(this.options.cache.input, {
                'change': this._calculateStrength,
                'keyup': this._calculateStrength,
                'paste': this._calculateStrength
            });
            if (this.options.cache.email.length) {
                this._on(this.options.cache.email, {
                    'change': this._calculateStrength,
                    'keyup': this._calculateStrength,
                    'paste': this._calculateStrength
                });
            }
        },

        /**
         * Calculate password strength
         * @private
         */
        _calculateStrength: function() {
            var password = this._getPassword(),
                displayScore = 0;
            if (password.length !== 0) {
                this.options.cache.input.rules('add', {
                    'password-not-equal-to-user-name': this.options.cache.email.val()
                });
                // We should only perform this check in case there is an email field on screen
                if (this.options.cache.email.length && password.toLowerCase() === this.options.cache.email.val().toLowerCase()) {
                    displayScore = 2;
                } else {
                    /**
                     * rules passwords must follow
                     * 1. password length must be at least n characters
                     * PASSWORD MUST CONTAIN AT LEAST...
                     * 2. n uppercase letter(s)
                     * 3. n lowercase letter(s)
                     * 4. n number(s)
                     * 5. n special character(s)
                     *    the regex here could be changed to /[^A-Za-z0-9]/ to check for any non-alphanumeric character
                     *    but I took this list of special characters from the Cognito password requirement options here:
                     *    https://docs.aws.amazon.com/cognito/latest/developerguide/user-pool-settings-policies.html
                     * 6. no whitespace allowed
                     * where n is the integer value used in the if below
                     * If these fail, set the score to 1 and display an error message outlining these requirements
                     */
                    if (password.length >= this.requirements.length &&
                        password.match(/[A-Z]/g) !== null && password.match(/[A-Z]/g).length >= this.requirements.uppercase &&
                        password.match(/[a-z]/g) !== null && password.match(/[a-z]/g).length >= this.requirements.lowercase &&
                        password.match(/[0-9]/g) !== null && password.match(/[0-9]/g).length >= this.requirements.number &&
                        password.match(/[\^$*.[\]{}()?\-"!@#%&/\\,><':;|_~`]/g) !== null && password.match(/[\^$*.[\]{}()?\-"!@#%&/\\,><':;|_~`]/g).length >= this.requirements.special &&
                        password.match(/\s/g) == null
                    ){
                        displayScore = 3;
                    } else {
                        displayScore = 1;
                    }
                }
            }
            this._displayStrength(displayScore);
        },

        /**
         * Display strength
         * @param {Number} displayScore
         * @private
         */
        _displayStrength: function(displayScore) {
            var strengthLabel = '',
                className = 'password-none';
            switch (displayScore) {
                case 1:
                    strengthLabel = $t('Must be a minimum of '+
                        this.requirements.length+' characters, '+
                        this.requirements.uppercase+' uppercase, '+
                        this.requirements.lowercase+' lowercase, '+
                        this.requirements.number+' number'+(this.requirements.number > 1 ? 's' : '')+', and '+
                        this.requirements.special+' special character'+(this.requirements.special > 1 ? 's' : '')+
                        ', and no spaces.');
                    className = 'password-fail';
                    break;
                case 2:
                    strengthLabel = $t('Your password cannot be the same as your email address.');
                    className = 'password-fail';
                    break;
            }
            this.options.cache.input.data('score', displayScore);
            this.options.cache.meter.removeClass().addClass(className);
            this.options.cache.label.text(strengthLabel);
        },

        /**
         * Get password value
         * @returns {*}
         * @private
         */
        _getPassword: function() {
            return this.options.cache.input.val();
        }
    });

    $.validator.addMethod(
        'passwordStrength',
        function(value, element) {
            return $(element).data('score') > 2
        },
        $.mage.__('Your password is invalid')
    );

    return $.mage.passwordStrengthIndicator;
});