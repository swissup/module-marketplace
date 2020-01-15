define([
    'Magento_Ui/js/grid/massactions'
], function (Massactions) {
    'use strict';

    return Massactions.extend({
        /**
         * @param {Object} action - Action data.
         * @param {Object} data - Selections data.
         */
        defaultCallback: function (action, data) {
            action.index = action.type;
            action.href = action.url;

            if (data.selected) {
                this.source
                    .submit(action, data.selected)
                    .done(function () {
                        this.selections().deselectAll();
                    }.bind(this));
            }
        }
    });
});
