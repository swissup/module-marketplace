define([
    'jquery',
    'Magento_Ui/js/form/components/insert-form',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, Insert, uiAlert, $t) {
    'use strict';

    return Insert.extend({
        defaults: {
            listens: {
                responseData: 'onResponse'
            },
            modules: {
                modal: '${ $.modalProvider }'
            }
        },

        /**
         * @param {Object} params
         * @param {Object} ajaxSettings
         * @return {Object}
         */
        requestData: function (params, ajaxSettings) {
            var result = this._super(params, ajaxSettings);

            this.params.packages = [];

            return result;
        },

        /**
         * @param {Object} response
         */
        onResponse: function (response) {
            if (response.error) {
                return uiAlert({
                    title: $t('Attention'),
                    content: response.message
                });
            }

            if (response.reload === true) {
                return window.location.reload();
            }

            if (response.message && response.log) {
                this.report(response);
            }

            this.modal().closeModal();
        },

        /**
         * @param {Object} response
         */
        report: function (response) {
            var template = [
                '<details>',
                    '<summary>%1</summary>',
                    '<pre><code>%2</code></pre>',
                '</details>'
            ].join('');

            uiAlert({
                modalClass: 'marketplace-installer-report-popup',
                title: response.message,
                content: template
                    .replace('%1', $t('View Log'))
                    .replace('%2', response.log.join('<br/>'))
            });
        }
    });
});
