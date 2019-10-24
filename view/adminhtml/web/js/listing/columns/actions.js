define([
    'Magento_Ui/js/grid/columns/actions'
], function (Column) {
    'use strict';

    return Column.extend({
        defaults: {
            forceMultiple: false
        },

        /**
         * Checks if row has only one visible action.
         *
         * @param {Number} rowIndex - Row index.
         * @returns {Boolean}
         */
        isSingle: function (rowIndex) {
            if (this.forceMultiple) {
                return false;
            }

            return this._super(rowIndex);
        },

        /**
         * Checks if row has more than one visible action.
         *
         * @param {Number} rowIndex - Row index.
         * @returns {Boolean}
         */
        isMultiple: function (rowIndex) {
            if (this.forceMultiple) {
                return true;
            }

            return this._super(rowIndex);
        }
    });
});
