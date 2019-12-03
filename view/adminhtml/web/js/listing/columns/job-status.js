/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'Magento_Ui/js/grid/columns/column',
    'mage/translate',
    'Magento_Ui/js/modal/modal'
], function ($, Column, $t) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'ui/grid/cells/html'
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
