define([
    'Magento_Ui/js/lib/view/utils/async',
    'underscore'
], function ($, _) {
    'use strict';

    return function (data, el) {
        var wrapper = $(el),
            button = $('.admin__action-dropdown', wrapper),
            sourceEl;

        /**
         * Add onclick observers
         */
        function addObservers() {
            var items = $('.action-menu-item', wrapper);

            items.click(function () {
                $(sourceEl).val($(this).data('channel')).change();
                $(button).text($(this).text());
                $('[data-action="grid-filter-apply"]').click();
                $('[data-toggle=dropdown].active', wrapper).trigger('close.dropdown');
            });
        }

        /**
         * Copy channels into switcher values.
         */
        function copyChannels(source) {
            var dropdown = $('.dropdown-menu', wrapper),
                selected = false,
                template = _.template(
                    '<li class="<%= css %>">' +
                        '<a class="action-menu-item" data-channel="<%= id %>" href="#">' +
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
                    css: selected ? 'disabled current' : '',
                    title: $(option).text(),
                    id: $(option).attr('value')
                }));
            });

            if (selected) {
                $(button).text($(selected).text());
                wrapper.show();
                addObservers();
            }
        }

        $.async('.admin__data-grid-filters [name="channel"]', function (select) {
            _.delay(copyChannels, 100, select);
        });
    };
});
