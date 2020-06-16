define([
    'jquery',
    'underscore',
    'Swissup_Marketplace/js/activity/status',
    'Swissup_Marketplace/js/utils/request',
    'Magento_Ui/js/grid/provider'
], function ($, _, status, request, Provider) {
    'use strict';

    return Provider.extend({
        defaults: {
            interval: {
                normal: 5000,
                slow: 15000,
                fast: 3000
            },
            busyClass: 'busy'
        },
        watchers: [],
        timer: null,

        /**
         * Initializes provider component.
         *
         * @returns {Provider} Chainable.
         */
        initialize: function () {
            this._super();

            return this;
        },

        /**
         * @param {Number} id
         * @param {Object} settings
         * @return {$.Deferred}
         */
        watch: function (id, settings) {
            var target,
                watcher = {},
                deferred = $.Deferred();

            if (settings instanceof jQuery) {
                target = settings;
                settings = {};
            }

            if (target) {
                $(target).addClass(this.busyClass);
            }

            watcher = $.extend(watcher, {
                id: id,
                target: target,
                deferred: deferred
            }, settings || {});

            this.watchers.push(watcher);

            this.handlePeriodicalUpdate();

            return deferred;
        },

        /**
         * Handles successful data reload.
         *
         * @param {Object} data - Retrieved data object.
         */
        onReload: function (data) {
            $('body').trigger('processStop');

            if (!data.items) {
                return;
            }

            this.updateWatchers(data);

            this._super(data);

            this.handlePeriodicalUpdate();
        },

        /**
         * Hide alerts.
         */
        onError: function (xhr) {
            $('body').notification('clear');

            if (xhr.statusText === 'abort') {
                return;
            }

            this.set('lastError', true);

            this.firstLoad = false;

            this.handlePeriodicalUpdate();
        },

        /**
         * @param {Object} data
         */
        updateWatchers: function (data) {
            var ids = [];

            _.each(this.watchers, function (watcher) {
                //jscs:disable requireCamelCaseOrUpperCaseIdentifiers
                var item = _.find(data.items, function (job) {
                    return job.job_id === watcher.id;
                });

                if (item && !item.finished_at) {
                    return;
                }

                if (watcher.deferred) {
                    if (status.is('errored', item) || status.is('canceled', item)) {
                        watcher.deferred.reject(item);
                    } else {
                        watcher.deferred.resolve(item);
                    }
                }

                if (watcher.target) {
                    $(watcher.target).removeClass(this.busyClass);
                }

                ids.push(watcher.id);
                //jscs:enable requireCamelCaseOrUpperCaseIdentifiers
            }, this);

            this.watchers = _.reject(this.watchers, function (watcher) {
                return ids.indexOf(watcher.id) > -1;
            }, this);
        },

        /**
         * Update periodical update parameters
         */
        handlePeriodicalUpdate: function () {
            var interval = this.interval.normal;

            if (this.timer) {
                clearTimeout(this.timer);
            }

            if (this.watchers.length) {
                interval = this.interval.fast;
            } else if (!this.hasUnfinishedJobs()) {
                interval = this.interval.slow;
            }

            this.timer = setTimeout(this.reload.bind(this), interval);
        },

        /**
         * @return {Boolean}
         */
        hasUnfinishedJobs: function () {
            return _.find(this.data.items, function (job) {
                //jscs:disable requireCamelCaseOrUpperCaseIdentifiers
                return !job.finished_at;
                //jscs:enable requireCamelCaseOrUpperCaseIdentifiers
            }) !== undefined;
        },

        /**
         * @return {Boolean}
         */
        hasCompletedJobs: function () {
            return _.find(this.data.items, function (job) {
                //jscs:disable requireCamelCaseOrUpperCaseIdentifiers
                return job.finished_at;
                //jscs:enable requireCamelCaseOrUpperCaseIdentifiers
            }) !== undefined;
        },

        /**
         * Mark completed items as invisible in activity grid
         */
        hideCompleted: function () {
            var completed = _.filter(this.data.items, function (job) {
                //jscs:disable requireCamelCaseOrUpperCaseIdentifiers
                return job.finished_at;
                //jscs:enable requireCamelCaseOrUpperCaseIdentifiers
            });

            $('body').trigger('processStart');

            request
                .post(this.hideCompletedUrl, {
                    ids: _.pluck(completed, 'job_id')
                })
                .always(function () {
                    this.reload();
                }.bind(this));
        }
    });
});
