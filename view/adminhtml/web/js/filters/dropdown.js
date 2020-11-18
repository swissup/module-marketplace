define([
    'Magento_Ui/js/lib/view/utils/async',
    'underscore',
    'uiRegistry'
], function ($, _, registry) {
    'use strict';

    /**
     * Add onclick observers
     */
    function addObservers(wrapper, source) {
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
                source.name
            ].join('.'));

        filter.value.subscribe(function () {
            $('.admin__action-dropdown', wrapper).text(
                $(source).children('option').filter(':selected').text()
            );
        });

        items.click(function (event) {
            event.preventDefault();
            filter.value($(this).data('value'));
            filters.apply();
            $('[data-toggle=dropdown].active', wrapper).trigger('close.dropdown');
        });
    }

    /**
     * Copy options into dropdown values.
     */
    function prepareDropdownOptions(wrapper, source) {
        var dropdown = $('.dropdown-menu', wrapper),
            selected = false,
            template = _.template(
                '<li class="<%= css %>">' +
                    '<a class="action-menu-item" data-value="<%= value %>" href="#">' +
                        '<%= title %>' +
                    '</a>' +
                '</li>'
            );

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
            $('.admin__action-dropdown', wrapper).text($(selected).text());
            $(wrapper).show();
            addObservers(wrapper, source);
        }
    }

    return function (data, el) {
        var selector = '.admin__data-grid-filters [name="' + data.filter + '"]';

        $.async(selector, function (select) {
            _.delay(function () {
                prepareDropdownOptions(el, select);
            }, 100);
        });
    };
});
