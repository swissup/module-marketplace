define([
    'Magento_Ui/js/grid/columns/multiselect',
    'mage/translate'
], function (Multiselect, $t) {
    'use strict';

    return Multiselect.extend({
        defaults: {
            preserveSelectionsOnFilter: true
        },

        /**
         * Initializes column component.
         *
         * @returns {Column} Chainable.
         */
        initialize: function () {
            this._super();

            this.actions = [{
                value: 'selectAll',
                label: $t('Select All on This Page')
            }, {
                value: 'deselectPage',
                label: $t('Deselect All on This Page')
            }, {
                value: 'deselectAll',
                label: $t('Deselect All')
            }];

            return this;
        },

        /**
         * Initializes observable properties.
         *
         * @returns {Multiselect} Chainable.
         */
        initObservable: function () {
            this._super();

            /**
             * Disable exclude mode
             * @return {Boolean}
             */
            this.excludeMode = function () {
                return false;
            };

            /**
             * Disable allSelected mode
             * @return {Boolean}
             */
            this.allSelected = function () {
                return this.isPageSelected(true);
            };

            return this;
        },

        /**
         * @returns {Multiselect} Chainable.
         */
        selectAll: function () {
            return this.selectPage();
        },

        /**
         * Checks if current page has selected records.
         *
         * @param {Boolean} [all=false] - If set to 'true' checks that every
         *      record on the page is selected. Otherwise checks that
         *      page has some selected records.
         * @returns {Boolean}
         */
        isPageSelected: function (all) {
            var ids = this.getIds(),
                selected = this.selected(),
                iterator = all ? 'every' : 'some';

            return ids[iterator](function (id) {
                return !!~selected.indexOf(id);
            });
        }
    });
});
