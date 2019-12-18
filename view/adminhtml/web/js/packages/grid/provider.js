define([
    'jquery',
    'underscore',
    'Magento_Ui/js/grid/provider'
], function ($, _, Provider) {
    'use strict';

    return Provider.extend({
        /**
         * Reload data but don't update the grid.
         *
         * @return {$.Deferred}
         */
        softReload: _.debounce(function () {
            var request = this.storage().getData(this.params, {
                refresh: true
            });

            // this.trigger('reload');

            request
                .done(this.onSoftReload.bind(this))
                .fail(this.onError.bind(this));

            return request;
        }, 500, true),

        /**
         * Callback
         */
        onSoftReload: function () {
            this.trigger('reloaded');
        }
    });
});
