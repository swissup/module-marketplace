define([
    'uiRegistry',
    'Magento_Ui/js/grid/columns/actions',
    'Swissup_Marketplace/js/packages/helper',
    'Swissup_Marketplace/js/installer/helper'
], function (registry, Column, packageHelper, installer) {
    'use strict';

    return Column.extend({
        defaults: {
            forceMultiple: false
        },

        /**
         * Returns `rel` attribute for the action.
         *
         * @param {Object} action - Action object.
         * @returns {String}
         */
        getRel: function (action) {
            if (action.rel) {
                return action.rel;
            }

            return null;
        },

        /**
         * @param {Object} action
         * @return {String}
         */
        getActionCss: function (action) {
            if (action.index === 'separator') {
                return 'action-menu-item-separator';
            }

            return '';
        },

        /**
         * Returns visible actions for a specified row.
         *
         * @param {Number} rowIndex - Index of a row.
         * @returns {Array} Visible actions.
         */
        getVisibleActions: function (rowIndex) {
            var actions = this._super(rowIndex);

            if (actions.length === 1 && actions[0].index === 'separator') {
                return [];
            }

            return actions;
        },

        /**
         * Checks if action should be displayed.
         *
         * @param {Object} action - Action object.
         * @returns {Boolean}
         */
        isActionVisible: function (action) {
            return packageHelper.isActionVisible(
                this.rows[action.rowIndex],
                action
            );
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
            var data = this.rows[action.rowIndex];

            if (!action.isAjax) {
                return this._super();
            }

            if (action.index === 'install' && data.downloaded) {
                installer.render([data.name]);
            } else {
                this.source()
                    .submit(action, [data.name])
                    .done(function () {
                        if (actionIndex === 'install') {
                            installer.render([data.name]);
                        }
                    });
            }
        }
    });
});
