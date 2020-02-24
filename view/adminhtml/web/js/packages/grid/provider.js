define([
    'jquery',
    'underscore',
    'mage/translate',
    'mage/template',
    'Magento_Ui/js/grid/provider',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'Swissup_Marketplace/js/utils/request',
    'Swissup_Marketplace/js/utils/job-watcher'
], function ($, _, $t, template, Provider, uiAlert, uiConfirm, request, watcher) {
    'use strict';

    return Provider.extend({
        defaults: {
            rows: [],
            imports: {
                rows: '${ $.rowsProvider }:rows'
            },
            constraintMessages: {
                conflicts: {
                    default: 'Cannot process <%= packages %> because of conflicts with: <%= conflicts %>',
                    enable: 'Cannot enable <%= packages %> because it conflicts with: <%= conflicts %>'
                },
                dependencies: {
                    enable: 'Cannot enable <%= packages %> because it requires the following dependencies: <%= dependencies %>',
                    enableConfirm: 'Enable All',
                    disable: 'Cannot disable <%= packages %> because other modules uses it: <%= dependencies %>',
                    disableConfirm: 'Disable All',
                    uninstall: 'Cannot remove <%= packages %> because other modules uses it: <%= dependencies %>',
                    uninstallConfirm: 'Remove All',
                    default: 'Cannot process <%= packages %> because of unresolved dependencies: <%= dependencies %>',
                    defaultConfirm: 'Process All'
                }
            }
        },

        /**
         * Reload data but don't update the grid.
         *
         * @return {$.Deferred}
         */
        softReload: _.debounce(function () {
            var data,
                params = $.extend({}, this.params, {
                    search: '',
                    paging: {
                        pageSize: 0 // load all packages because currently edited package may change its position
                    }
                });

            data = this.storage().getData(params, {
                refresh: true
            });

            // this.trigger('reload');

            data
                .done(this.onSoftReload.bind(this))
                .fail(this.onError.bind(this));

            return data;
        }, 500, true),

        /**
         * Callback
         */
        onSoftReload: function () {
            this.trigger('reloaded');
        },

        /**
         * @param {Object} action
         * @param {Array} packages
         * @return {$.Deferred}
         */
        submit: function (action, packages) {
            var result = $.Deferred();

            this.toggleLoader(packages, true);

            result.initialRequest = request.post(action.href, {
                    packages: packages
                })
                .done(function (response) {
                    if (response.id) {
                        watcher.watch(response.id)
                            .done(function () {
                                result.resolve();
                            })
                            .fail(function () {
                                result.reject();
                            })
                            .always(function () {
                                this.updateRowsData();
                            }.bind(this));

                        return;
                    }

                    this.toggleLoader(packages, false);

                    if (this.validateResponse(response, packages, action)) {
                        result.resolve();
                    } else {
                        result.reject();
                    }
                }.bind(this))
                .fail(function () {
                    this.toggleLoader(packages, false);
                    result.reject();
                }.bind(this));

            return result;
        },

        /**
         * @param {Object} response
         * @param {Array} packages
         * @param {Object} action
         */
        validateResponse: function (response, packages, action) {
            var self = this,
                content,
                confirm;

            if (response.conflicts) {
                content = this.constraintMessages.conflicts[action.index] ?
                    this.constraintMessages.conflicts[action.index] :
                    this.constraintMessages.conflicts['default'];

                uiAlert({
                    title: $t('Operation failed'),
                    content: template(content, {
                        packages: '<strong>' + packages.join(', ') + '</strong>',
                        conflicts: '<pre><code>' + response.conflicts.join('\n') + '</code></pre>'
                    })
                });

                return false;
            }

            if (response.dependencies) {
                content = this.constraintMessages.dependencies[action.index] ?
                    this.constraintMessages.dependencies[action.index] :
                    this.constraintMessages.dependencies.default;
                confirm = this.constraintMessages.dependencies[action.index + 'Confirm'] ?
                    this.constraintMessages.dependencies[action.index + 'Confirm'] :
                    this.constraintMessages.dependencies.defaultConfirm;

                uiConfirm({
                    title: $t('Operation failed'),
                    content: template(content, {
                        packages: '<strong>' + packages.join(', ') + '</strong>',
                        dependencies: '<pre><code>' + response.dependencies.join('\n') + '</code></pre>'
                    }),
                    actions: {
                        /**
                         * Submit updated data
                         */
                        confirm: function () {
                            self.submit(packages.concat(response.dependencies), action);
                        }
                    },
                    buttons: [{
                        text: $t('Cancel'),
                        class: 'action-secondary action-dismiss'
                    }, {
                        text: confirm,
                        class: 'action-primary action-accept',

                        /**
                         * Click handler.
                         */
                        click: function (event) {
                            this.closeModal(event, true);
                        }
                    }]
                });

                return false;
            }

            return true;
        },

        /**
         * @param {Array} packages
         * @param {Boolean} flag
         */
        toggleLoader: function (packages, flag) {
            var counter = 0;

            _.every(this.rows, function (row) {
                if (packages.indexOf(row.name) !== -1) {
                    this.rows[row._rowIndex].busy = flag;
                    counter++;
                }

                return counter < packages.length;
            }, this);

            this.rows.splice(0, 0); // trigger grid re-render
        },

        /**
         * Update outdated packages data
         */
        updateRowsData: function () {
            var keys = [
                'version',
                'time',
                'installed',
                'enabled',
                'accessible'
            ];

            this.softReload().done(function (response) {
                var cached = this.storage().data;

                _.each(this.rows, function (row) {
                    var data = _.find(response.items, function (item) {
                        return item.name === row.name;
                    });

                    this.rows[row._rowIndex].busy = false;
                    this.rows[row._rowIndex].hidden = false;

                    if (!data) {
                        // item was filtered out by the server
                        this.rows[row._rowIndex].hidden = true;
                    } else if (!_.isMatch(row, _.pick(data, keys))) {
                        this.rows[row._rowIndex] = $.extend(
                            this.rows[row._rowIndex],
                            data || {}
                        );
                    }
                }, this);

                // update cached records
                _.each(response.items, function (item) {
                    var ids = _.filter(_.keys(cached), function (key) {
                        var keyLength = key.length,
                            nameLength = item.name.length;

                        return keyLength >= nameLength && key.substr(keyLength - nameLength) === item.name;
                    });

                    _.each(ids, function (key) {
                        cached[key] = $.extend(cached[key], item);
                    });
                });

                this.rows.splice(0, 0); // trigger grid re-render
            }.bind(this));
        }
    });
});
