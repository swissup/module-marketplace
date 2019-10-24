define([
    'mageUtils',
    'moment',
    'Magento_Ui/js/grid/columns/column'
], function (utils, moment, Column) {
    'use strict';

    return Column.extend({
        defaults: {
            dateFormat: 'MMM d, YYYY'
        },

        /**
         * Overrides base method to normalize date format.
         *
         * @returns {StatusColumn} Chainable.
         */
        initConfig: function () {
            this._super();

            this.dateFormat = utils.normalizeDate(this.dateFormat ? this.dateFormat : this.options.dateFormat);

            return this;
        },

        /**
         * @param {Object} row
         * @return {String}
         */
        getTitle: function (row) {
            var title = '';

            switch (row.state) {
                case 'outdated':
                    title = row.remote.version + ' is available since ' +
                        this._renderDate(row.remote.time);
                    break;

                case 'updated':
                    title = 'The module is up to date';
                    break;

                case 'na':
                    title = 'This module is not installed yet';
                    break;
            }

            return title;
        },

        /**
         * @param {Object} row
         * @return {String}
         */
        getVersion: function (row) {
            return row.version ? row.version : row.remote.version;
        },

        /**
         * @param {Object} row
         * @return {String}
         */
        getDate: function (row) {
            return row.time ? this._renderDate(row.time) : this._renderDate(row.remote.time);
        },

        /**
         * @param {String} value
         * @return {String}
         */
        _renderDate: function (value) {
            var date;

            if (this.storeLocale !== undefined) {
                moment.locale(this.storeLocale, utils.extend({}, []));
            }
            date = moment(value);

            date = date.isValid() && value ?
                date.format(this.dateFormat) : '';

            return date;
        }
    });
});
