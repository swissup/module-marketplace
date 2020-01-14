define([
    'Magento_Ui/js/lib/view/utils/async',
    'underscore',
    'uiRegistry'
], function ($, _, registry) {
    'use strict';

    return function (data, el) {
        var wrapper = $(el),
            button = $('.admin__action-dropdown', wrapper),
            sourceEl;

        /**
         * Add onclick observers
         */
        function addObservers() {
            var items = $('.action-menu-item', wrapper),
                filters = registry.get([
                    'swissup_marketplace_package_listing',
                    'swissup_marketplace_package_listing',
                    'listing_top',
                    'listing_filters'
                ].join('.')),
                filter = registry.get([
                    'swissup_marketplace_package_listing',
                    'swissup_marketplace_package_listing',
                    'listing_top',
                    'listing_filters',
                    'type'
                ].join('.'));

            filter.value.subscribe(function () {
                $(button).text(
                    $(sourceEl).children('option').filter(':selected').text()
                );
            });

            items.click(function () {
                filter.value($(this).data('type'));
                filters.apply();
                $('[data-toggle=dropdown].active', wrapper).trigger('close.dropdown');
            });
        }

        /**
         * Copy channels into switcher values.
         */
        function copyPackageTypes(source) {
            var dropdown = $('.dropdown-menu', wrapper),
                selected = false,
                template = _.template(
                    '<li class="<%= css %>">' +
                        '<a class="action-menu-item" data-type="<%= value %>" href="#">' +
                            '<%= title %>' +
                        '</a>' +
                    '</li>'
                );

            sourceEl = source;

            _.each(source.options, function (option) {
                if (!selected) {
                    selected = option;
                } else if ($(option).is(':selected')) {
                    selected = option;
                }

                dropdown.append(template({
                    css: $(option).is(':selected') ? 'current' : '',
                    title: $(option).text(),
                    value: $(option).attr('value')
                }));
            });

            if (selected) {
                $(button).text($(selected).text());
                wrapper.show();
                addObservers();
            }
        }

        $.async('.admin__data-grid-filters [name="type"]', function (select) {
            _.delay(copyPackageTypes, 100, select);
        });
    };
});
