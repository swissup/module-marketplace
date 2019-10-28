define([
    'ko',
    'jquery',
    'underscore',
    'Magento_Ui/js/grid/columns/actions',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function (ko, $, _, Column, uiAlert, $t) {
    'use strict';

    return Column.extend({
        defaults: {
            ajaxSettings: {
                method: 'POST',
                dataType: 'json'
            },
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
                data = _.findWhere(this.rows, {
                    _rowIndex: action.rowIndex
                });

                this.request(action.href, {
                        package: data.id
                    })
                    .done(function (response) {
                        if (response.error) {
                            return;
                        }

                        this.rows[action.rowIndex].enabled = response.package.enabled;
                        this.rows.splice(0, 0); // trigger grid re-render
                    }.bind(this));
            } else {
                this._super();
            }
        },

        /**
         * @param {String} href
         * @param {Object} data
         */
        request: function (href, data) {
            var settings = _.extend({}, this.ajaxSettings, {
                url: href,
                data: _.extend(data || {}, {
                    'form_key': window.FORM_KEY
                })
            });

            $('body').trigger('processStart');

            return $.ajax(settings)
                .done(function (response) {
                    if (!response.error) {
                        return;
                    }

                    uiAlert({
                        content: response.message
                    });
                })
                .fail(function () {
                    uiAlert({
                        content: $t('Sorry, there has been an error processing your request. Please try again later.')
                    });
                })
                .always(function () {
                    $('body').trigger('processStop');
                });
        }
    });
});
