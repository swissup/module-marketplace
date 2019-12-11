define([
    'jquery',
    'Magento_Ui/js/form/components/insert-form',
    'Swissup_Marketplace/js/utils/job-watcher',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, Insert, watcher, uiAlert, $t) {
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

            if (response.id) {
                watcher.watch(response.id, $('body')).done(function () {
                    window.location.reload();
                });
            }

            this.modal().closeModal();
        }
    });
});
