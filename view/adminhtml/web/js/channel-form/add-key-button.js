define([
    'underscore',
    'Swissup_Marketplace/js/channel-form/validate-button'
], function (_, Button) {
    'use strict';

    return Button.extend({
        /** @inheritdoc */
        initObservable: function () {
            this._super();

            this.responseSuccess.subscribe(function (flag) {
                console.log(flag);

                if (!flag) {
                    return;
                }

                // 1. add key to password field

                // 2. cleanup key field
            });

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
                password: data.key ? data.key : data.password
            });
        }
    });
});
