define([
    'jquery',
    'Swissup_Marketplace/js/activity/status',
    'Magento_Ui/js/grid/columns/select',
    'mage/translate',
    'Magento_Ui/js/modal/modal'
], function ($, status, Column, $t) {
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
            var classes = [
                    'grid-severity-notice',
                    'marketplace-job-status',
                    'marketplace-job-status-' + status.getCode(row.status),
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
