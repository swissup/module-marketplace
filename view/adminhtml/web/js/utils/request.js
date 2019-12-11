define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, uiAlert, $t) {
    'use strict';

    var defaultSettings = {
        method: 'POST',
        dataType: 'json'
    };

    return {
        /**
         * @param {String} url
         * @param {Object} data
         * @return {$.Deferred}
         */
        get: function (url, data) {
            return this.request($.extend({}, defaultSettings, {
                url: url,
                method: 'GET',
                data: data || {}
            }));
        },

        /**
         * @param {String} url
         * @param {Object} data
         * @return {$.Deferred}
         */
        post: function (url, data) {
            return this.request($.extend({}, defaultSettings, {
                url: url,
                data: $.extend(data || {}, {
                    'form_key': window.FORM_KEY
                })
            }));
        },

        /**
         * @param {Object} settings
         * @return {$.Deferred}
         */
        request: function (settings) {
            return $.ajax(settings)
                .done(function (response) {
                    if (response.error && response.message) {
                        uiAlert({
                            content: response.message
                        });
                    }
                })
                .fail(function (response) {
                    var title = $t('Attention'),
                        content = $t('Sorry, there has been an error processing your request. Please try again later.');

                    if (response.status === 403) {
                        title = $t(response.statusText);
                        content = $t('Sorry, you do not have permission for this operation.');
                    }

                    uiAlert({
                        title: title,
                        content: content
                    });
                });
        }
    };
});
