define([
    'jquery',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract',
    'mage/translate'
], function ($, _, registry, Element, $t) {
    'use strict';

    return Element.extend({
        defaults: {
            elementTmpl: 'Swissup_Marketplace/channel-form/keys',
            keysSeparator: ' ',
            partsSeparator: ':'
        },

        /**
         * @return {Boolean}
         */
        hasKeys: function () {
            var keys = this.value().split(this.keysSeparator);

            keys = keys.filter(function (key) {
                return key;
            });

            return keys.length > 0;
        },

        /**
         * @return {Array}
         */
        getKeys: function () {
            var keys = this.value().split(this.keysSeparator),
                result = [];

            keys = keys.filter(function (key) {
                return key;
            });

            _.each(keys, function (key) {
                var parts = key.split(this.partsSeparator),
                    domain = '',
                    cleanDomain = '';

                if (parts.length > 1) {
                    try {
                        domain = atob(parts[0]);
                    } catch (e) {
                        domain = '';
                    }
                }

                cleanDomain = domain.replace('www.', '');

                result.push({
                    domain: domain,
                    title: $t('A key from ' + (cleanDomain ? cleanDomain : $t('unknown source'))),
                    letter: cleanDomain[0] ? cleanDomain[0] : '?',
                    key: parts.join(this.partsSeparator)
                });
            }.bind(this));

            return result;
        },

        /**
         * @param {String} key
         */
        removeKey: function (key) {
            var keys = this.value().split(this.keysSeparator);

            keys = _.reject(keys, function (current) {
                return current === key;
            });

            this.value(keys.join(this.keysSeparator));
        },

        /**
         * @param {String} key
         */
        validateKey: function (key) {
            registry.get(this.parentName + '.password_wrapper.key').value(key);
            registry.get(this.parentName + '.password_wrapper.button').action();
        }
    });
});
