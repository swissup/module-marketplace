define([
    'underscore',
    'Magento_Ui/js/form/element/ui-select'
], function (_, Select) {
    'use strict';

    return Select.extend({
        /**
         * Initializes UISelect component.
         *
         * @returns {UISelect} Chainable.
         */
        initialize: function () {
            this._super();

            this.rules.unselectable = _.extend({}, this.rules.unselectable || {});
            this.rules.selectable = _.extend({}, this.rules.selectable || {});

            return this;
        },

        /**
         * Disallow to select unselectable elements
         *
         * @param {Object} data - selected option data
         * @returns {Object} Chainable
         */
        toggleOptionSelected: function (data) {
            var options = this.options(),
                scope = _.find(options, function (el) {
                    return el.label === data.path;
                });

            if (this.isSelectable(data)) {
                if (scope &&
                    scope.optgroup &&
                    scope.multiple === false &&
                    !this.isSelected(data.value)
                ) {
                    _.each(_.pluck(scope.optgroup, 'value'), function (value) {
                        this.value(_.without(this.value(), value));
                    }.bind(this));
                }

                return this._super(data);
            }

            return this;
        },

        /**
         * Use _unclickable to indicate unselectable elements
         */
        isLabelDecoration: function (data) {
            return !this.isSelectable(data);
        },

        /**
         * Check if item is selectable
         *
         * @param  {Object} data
         * @return {Boolean}
         */
        isSelectable: function (data) {
            var self = this,
                match = _.find(this.rules.unselectable, function (rule) {
                    return self._compareWithRule(data, rule);
                });

            if (match) {
                return false;
            }

            if (_.isEmpty(this.rules.selectable)) {
                return true;
            }

            match = _.find(this.rules.selectable, function (rule) {
                return self._compareWithRule(data, rule);
            });

            return match;
        },

        /**
         * @param  {Object} data
         * @param  {Object} rule
         * @return {Boolean}
         */
        _compareWithRule: function (data, rule) {
            var dataValue = data[rule.property];

            switch (rule.comparator) {
                case '==':
                    return dataValue == rule.value; // eslint-disable-line eqeqeq

                case '!=':
                    return dataValue != rule.value; // eslint-disable-line eqeqeq

                case '!==':
                    return dataValue !== rule.value;

                case 'has':
                    return dataValue.indexOf(rule.value) > -1;

                default:
                    return dataValue === rule.value;
            }
        }
    });
});
