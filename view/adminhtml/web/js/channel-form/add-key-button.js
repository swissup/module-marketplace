define([
    'underscore',
    'uiRegistry',
    'Swissup_Marketplace/js/channel-form/validate-button'
], function (_, registry, Button) {
    'use strict';

    return Button.extend({
        /** @inheritdoc */
        initObservable: function () {
            this._super();

            this.responseSuccess.subscribe(function (flag) {
                var provider = registry.get(this.provider),
                    data = this.getData(),
                    password = data._password;

                if (!flag) {
                    return;
                }

                if (password.indexOf(data.key) === -1) {
                    provider.set(this.dataScope + '.password', password + ' ' + data.key);
                }

                provider.set(this.dataScope + '.key', '');
            }.bind(this));

            return this;
        },

        /**
         * When access_key is not empty - validate it.
         * Otherwise - validate password field.
         *
         * @return {Object}
         */
        getData: function () {
            var data = this._super();

            return _.extend(data, {
                password: data.key ? data.key : data.password,
                _password: data.password
            });
        }
    });
});
