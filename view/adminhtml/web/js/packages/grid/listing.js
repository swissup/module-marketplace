define([
    'Magento_Ui/js/lib/view/utils/async',
    'Magento_Ui/js/grid/listing'
], function ($, Component) {
    'use strict';

    return Component.extend({
        /**
         * Initializes Listing component.
         *
         * @returns {Listing} Chainable.
         */
        initialize: function () {
            $.async('.marketplace-mode-switcher .admin__action-dropdown-wrap', function () {
                var dest = $('.admin__data-grid-pager-wrap');

                if (!dest.length) {
                    return;
                }

                dest.append($('.marketplace-mode-switcher'));
            });

            return this._super();
        },

        /**
         * No need to re-render the grid.
         *
         * @returns {String} Path to the template.
         */
        getTemplate: function () {
            return this.template;
        }
    });
});
