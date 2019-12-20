define([
    'jquery',
    'underscore',
    'Swissup_Marketplace/js/activity/status',
    'Magento_Ui/js/grid/provider'
], function ($, _, status, Provider) {
    'use strict';

    return Provider.extend({
        defaults: {
            interval: {
                normal: 5000,
                slow: 10000,
                fast: 1500
            },
            busyClass: 'busy'
        },
        watchers: [],
        timer: null,

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

                if (!item || !item.finished_at) {
                    return;
                }

                if (watcher.deferred) {
                    if (status.is('errored', item)) {
                        watcher.deferred.fail(item);
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
        }
    });
});
