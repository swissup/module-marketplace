define([
    'ko',
    'Magento_Ui/js/grid/listing',
    'mage/translate'
], function (ko, Listing, $t) {
    'use strict';

    return Listing.extend({
        filters: ko.observableArray(),

        /**
         * Handler of the data providers' 'reloaded' event.
         */
        onDataReloaded: function () {
            this._super();

            this.filters().map(function (filter) {
                filter.options(this.getFilterOptions(filter.type));
            }, this);

            if (!this.filters().length) {
                this.filters([{
                    type: 'type',
                    value: ko.observable('metapackage'),
                    options: ko.observable(this.getFilterOptions('type'))
                }]);
            }
        },

        /**
         * @param {String} filterType
         * @return {Array}
         */
        getFilterOptions: function (filterType) {
            switch (filterType) {
                case 'type':
                    return this.getPackageTypes();
            }

            return [];
        },

        /**
         * @param {Object} filter
         * @param {Object} option
         */
        activateFilter: function (filter, option) {
            this.filters().forEach(function (el) {
                if (el.type !== filter.type) {
                    return;
                }
                el.value(option.value);
            });
        },

        /**
         * @param {Object} filter
         * @param {Object} option
         * @return {Boolean}
         */
        isFilterActive: function (filter, option) {
            return filter.value() === option.value;
        },

        /**
         * @param {Object} row
         * @return {Boolean}
         */
        isRowVisible: function (row) {
            return this.filters().findIndex(function (filter) {
                if (filter.value() === undefined) {
                    return false;
                }

                if (filter.value().indexOf('!') === 0) {
                    return row[filter.type] !== filter.value().substr(1);
                }

                return row[filter.type] === filter.value();
            }) > -1;
        },

        /**
         * @return {Boolean}
         */
        hasVisibleRows: function () {
            return this.rows.findIndex(function (row) {
                return this.isRowVisible(row);
            }, this) > -1;
        },

        /**
         * @return {Array}
         */
        getPackageTypes: function () {
            var metapackages = this.rows.filter(function (row) {
                return row.type === 'metapackage';
            });

            return [{
                value: 'metapackage',
                label: $t('Bundles'),
                title: $t('Bundles — are recommended way to install themes and modules.'),
                count: metapackages.length
            }, {
                value: '!metapackage',
                label: $t('Components'),
                title: $t('Install the only things you need. Recommended for advanced users.'),
                count: this.rows.length - metapackages.length
            }];
        },

        /**
         * @return {String}
         */
        getEmptyMessage: function () {
            return $t('No items found.');
        }
    });
});
