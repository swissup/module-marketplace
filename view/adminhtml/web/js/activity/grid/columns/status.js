define([
    'jquery',
    'Magento_Ui/js/grid/columns/select',
    'mage/translate',
    'Magento_Ui/js/modal/modal'
], function ($, Column, $t) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'Swissup_Marketplace/activity/cells/status'
        },

        /**
         * Retrieves css class associated with a provided value.
         *
         * @param {Object} row
         * @returns {String}
         */
        getClass: function (row) {
            var status = {
                    '0': 'pending',
                    '1': 'queued',
                    '2': 'running',
                    '3': 'success',
                    '4': 'skipped',
                    '5': 'errored'
                },
                classes = [
                    'grid-severity-notice',
                    'marketplace-job-status',
                    'marketplace-job-status-' + status[row.status],
                    row.output ? 'marketplace-job-with-output' : ''
                ];

            return classes.join(' ');
        },

        /**
         * Build preview.
         *
         * @param {Object} row
         */
        preview: function (row) {
            var modalHtml = row.output,
                previewPopup = $('<pre/>').html(modalHtml);

            previewPopup.modal({
                title: $t('Result'),
                innerScroll: true,
                modalClass: 'marketplace-job-output'
            }).trigger('openModal');
        },

        /**
         * Get field handler per row.
         *
         * @param {Object} row
         * @returns {Function}
         */
        getFieldHandler: function (row) {
            if (row.output) {
                return this.preview.bind(this, row);
            }
        }
    });
});
