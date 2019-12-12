define([
    'jquery',
    'Magento_Ui/js/grid/provider'
], function ($, Provider) {
    'use strict';

    return Provider.extend({
        /**
         * Reload data but don't update the grid.
         *
         * @return {$.Deferred}
         */
        softReload: function () {
            var request = this.storage().getData(this.params, {
                refresh: true
            });

            // this.trigger('reload');

            request
                .done(this.onSoftReload.bind(this))
                .fail(this.onError.bind(this));

            return request;
        },

        /**
         * Callback
         */
        onSoftReload: function () {
            this.trigger('reloaded');
        }
    });
});
