define([
    'ko',
    'jquery',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/grid/columns/actions',
    'Swissup_Marketplace/js/utils/request',
    'Swissup_Marketplace/js/utils/job-watcher'
], function (ko, $, _, registry, Column, request, watcher) {
    'use strict';

    return Column.extend({
        defaults: {
            forceMultiple: false
        },

        /**
         * Checks if action should be displayed.
         *
         * @param {Object} action - Action object.
         * @returns {Boolean}
         */
        isActionVisible: function (action) {
            if (action.hidden) {
                return false;
            }

            switch (action.index) {
                case 'update':
                    return this.rows[action.rowIndex].state === 'outdated';

                case 'disable':
                    return this.rows[action.rowIndex].enabled && this.rows[action.rowIndex].installed;

                case 'enable':
                    return !this.rows[action.rowIndex].enabled && this.rows[action.rowIndex].installed;

                case 'uninstall':
                    return this.rows[action.rowIndex].installed;

                case 'install':
                    return !this.rows[action.rowIndex].installed;
            }

            return this._super(action);
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
        },

        /**
         * Checks if specified action requires a handler function.
         *
         * @param {String} actionIndex - Actions' identifier.
         * @param {Number} rowIndex - Index of a row.
         * @returns {Boolean}
         */
        isHandlerRequired: function (actionIndex, rowIndex) {
            var action = this.getAction(rowIndex, actionIndex);

            if (action.isAjax) {
                return true;
            }

            return this._super(actionIndex, rowIndex);
        },

        /**
         * Default action callback. Redirects to
         * the specified in action's data url.
         *
         * @param {String} actionIndex - Action's identifier.
         * @param {(Number|String)} recordId - Id of the record associated
         *      with a specified action.
         * @param {Object} action - Action's data.
         */
        defaultCallback: function (actionIndex, recordId, action) {
            var data;

            if (action.isAjax) {
                data = this.rows[action.rowIndex];

                this.rows[action.rowIndex].busy = true;
                this.rows.splice(0, 0); // trigger grid re-render

                request.post(action.href, {
                        package: data.id,
                        channel: data.remote.channel
                    })
                    .done(function (response) {
                        if (response.reload === true) {
                            return window.location.reload();
                        }

                        if (response.id) {
                            watcher.watch(response.id).done(function () {
                                this.updateRowData(this.rows[action.rowIndex]);
                            }.bind(this));
                        }
                    }.bind(this))
                    .fail(function () {
                        this.rows[action.rowIndex].busy = false;
                        this.rows.splice(0, 0); // trigger grid re-render
                    }.bind(this));
            } else {
                this._super();
            }
        },

        /**
         * @param {Object} row
         */
        updateRowData: function (row) {
            this.source().softReload().done(function (response) {
                var data = _.find(response.items, function (item) {
                    return item.id === row.id;
                });

                this.rows[row._rowIndex] = $.extend(
                    this.rows[row._rowIndex],
                    {
                        busy: false
                    },
                    data
                );

                this.rows.splice(0, 0); // trigger grid re-render
            }.bind(this));
        }
    });
});
