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
            return registry
                .get([
                    'swissup_marketplace_job_activity_listing',
                    'swissup_marketplace_job_activity_listing_data_source'
                ].join('.'))
                .watch(id, settings);
        }
    };
});
