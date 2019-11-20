define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/components/button',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, _, Button, uiAlert, $t) {
    'use strict';

    return Button.extend({
        defaults: {
            ajaxSettings: {
                method: 'POST',
                dataType: 'json'
            },
            elementTmpl: 'Swissup_Marketplace/form/element/button'
        },

        /** @inheritdoc */
        initObservable: function () {
            return this._super()
                .observe([
                    'responseError',
                    'responseSuccess',
                    'loading'
                ]);
        },

        /**
         * @return {Object}
         */
        getData: function () {
            var path = this.dataScope.split('.');

            return _.extend(
                {
                    channel: path[path.length - 1],
                    'form_key': window.FORM_KEY
                },
                path.reduce(function (data, key) {
                    return data[key];
                }, this.source)
            );
        },

        /**
         * Performs configured actions
         */
        action: function () {
            var settings = _.extend({}, this.ajaxSettings, {
                url: this.url,
                data: this.getData()
            });

            this.responseError(false);
            this.responseSuccess(false);
            this.loading(true);

            return $.ajax(settings)
                .done(function (response) {
                    if (response.error && response.message) {
                        this.responseError(response.message);
                    } else {
                        this.responseSuccess(
                            response.total > 1 ?
                                $t('Remote channel returned %1 packages').replace('%1', response.total) :
                                $t('Remote channel returned %1 package').replace('%1', response.total)
                        );
                    }
                }.bind(this))
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
                })
                .always(function () {
                    this.loading(false);
                }.bind(this));
        }
    });
});
