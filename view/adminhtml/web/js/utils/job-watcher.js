define([
    'uiRegistry'
], function (registry) {
    'use strict';

    return {
        /**
         * @param {Number} id
         * @param {Object} settings
         * @return {$.Deferred}
         */
        watch: function (id, settings) {
            var activity = registry.get([
                'swissup_marketplace_job_activity_listing',
                'swissup_marketplace_job_activity_listing',
                'swissup_marketplace_job_activity_columns'
            ].join('.'));

            return activity.source.watch(id, settings);
        }
    };
});
