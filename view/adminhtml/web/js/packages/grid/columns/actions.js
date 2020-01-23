define([
    'Magento_Ui/js/grid/columns/actions'
], function (Column) {
    'use strict';

    return Column.extend({
        defaults: {
            forceMultiple: false
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
                    return this.rows[action.rowIndex].enabled && this.rows[action.rowIndex].downloaded;

                case 'enable':
                    return !this.rows[action.rowIndex].enabled && this.rows[action.rowIndex].downloaded;

                case 'uninstall':
                    return this.rows[action.rowIndex].composer && this.rows[action.rowIndex].downloaded;

                case 'install':
                    return !this.rows[action.rowIndex].downloaded;
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

            this.source().submit(action, [this.rows[action.rowIndex].name]);
        }
    });
});
