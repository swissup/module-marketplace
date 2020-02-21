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
         * @param {Object} settings
         * @return {$.Deferred}
         */
        get: function (url, data, settings) {
            return this.request($.extend({}, defaultSettings, settings || {}, {
                url: url,
                method: 'GET',
                data: data || {}
            }));
        },

        /**
         * @param {String} url
         * @param {Object} data
         * @param {Object} settings
         * @return {$.Deferred}
         */
        post: function (url, data, settings) {
            return this.request($.extend({}, defaultSettings, settings || {}, {
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
                    if (!response) {
                        return;
                    }

                    if (response.reload === true) {
                        return window.location.reload();
                    }

                    if (response.error && response.message) {
                        uiAlert({
                            content: response.message
                        });
                    }
                })
                .fail(function (response) {
                    var title = $t('Attention'),
                        content = $t('Sorry, there has been an error processing your request. Please try again later.');

                    if (settings.quiet) {
                        // cleanup messages added to the body by boostrap.js
                        $('body').notification('clear');

                        return;
                    }

                    if (!response) {
                        return;
                    }

                    if (response.status === 200) {
                        return;
                    }

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
