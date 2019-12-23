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
            if (!action.isAjax) {
                return this._super();
            }

            this.submit([this.rows[action.rowIndex].id], action);
        },

        /**
         * @param {Array} packages
         * @param {Object} action
         */
        submit: function (packages, action) {
            var indexes = [];

            // mark modules as 'busy'
            _.every(this.rows, function (row) {
                if (row.name.indexOf(packages) !== -1) {
                    this.rows[row._rowIndex].busy = true;
                    indexes.push(row._rowIndex);
                }

                return indexes.length < packages.length;
            }, this);

            this.rows.splice(0, 0); // trigger grid re-render

            request.post(action.href, {
                    packages: packages
                })
                .done(function (response) {
                    if (response.id) {
                        watcher.watch(response.id).always(function () {
                            this.updateRowsData(packages);
                        }.bind(this));
                    }
                }.bind(this))
                .fail(function () {
                    _.each(indexes, function (index) {
                        this.rows[index].busy = false;
                    }, this);
                    this.rows.splice(0, 0); // trigger grid re-render
                }.bind(this));
        },

        /**
         * @param {Array} packages
         */
        updateRowsData: function (packages) {
            this.source().softReload().done(function (response) {
                var processed = 0;

                _.every(this.rows, function (row) {
                    var data;

                    if (row.name.indexOf(packages) !== -1) {
                        data = _.find(response.items, function (item) {
                            return item.name === row.name;
                        });

                        this.rows[row._rowIndex] = $.extend(
                            this.rows[row._rowIndex],
                            {
                                busy: false
                            },
                            data || {}
                        );

                        processed++;
                    }

                    return processed < packages.length;
                }, this);

                this.rows.splice(0, 0); // trigger grid re-render
            }.bind(this));
        }
    });
});
