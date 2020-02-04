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
            uiAlert({
                title: response.message,
                content: '<strong>%1</strong><br/>%2'
                    .replace('%1', $t('Installation Report'))
                    .replace('%2', response.log.join('<br/>'))
            });
        }
    });
});
