define([
    'uiRegistry',
    'mageUtils',
    'moment',
    'Magento_Ui/js/grid/columns/column',
    'mage/translate'
], function (registry, utils, moment, Column, $t) {
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
         * @return {Array}
         */
        getActions: function (row) {
            var installLabel = $t('Install'),
                suffix = row.accessible ? '' : '*';

            if (row.installer && row.downloaded) {
                installLabel = $t('Run installer');
            }

            return [{
                index: 'update',
                class: 'action update',
                rowIndex: row._rowIndex,
                label: $t('Update') + suffix,
                title: row.accessible ? '' : $t('Latest version is not accessible')
            }, {
                index: 'install',
                class: row.installer && row.downloaded ? 'action primary install' : 'action install',
                rowIndex: row._rowIndex,
                label: installLabel + suffix,
                title: row.accessible ? '' : $t('Latest version is not accessible')
            }, {
                index: 'enable',
                class: 'action primary enable',
                rowIndex: row._rowIndex,
                label: $t('Enable')
            }, {
                index: 'disable',
                class: 'action disable',
                hidden: true,
                rowIndex: row._rowIndex,
                label: $t('Disable')
            }];
        },

        /**
         * @param {Object} action
         */
        getActionHandler: function (action) {
            return registry.get(this.parentName + '.links').getActionHandler(action);
        },

        /**
         * @param {Object} action
         * @return {Boolean}
         */
        isActionVisible: function (action) {
            var links = registry.get(this.parentName + '.links');

            return links.getAction(action.rowIndex, action.index) && links.isActionVisible(action);
        },

        /**
         * @param {Object} row
         * @return {String}
         */
        getTitle: function (row) {
            var title = '';

            switch (row.state) {
                case 'outdated':
                    if (row.remote.time) {
                        title = $t('%1 is available since %2')
                            .replace('%1', row.remote.version)
                            .replace('%2', this._renderDate(row.remote.time));
                    } else {
                        title = $t('%1 is available')
                            .replace('%1', row.remote.version);
                    }
                    break;

                case 'updated':
                    title = $t('The module is up to date');
                    break;

                case 'na':
                    title = $t('This module is not downloaded yet');
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
