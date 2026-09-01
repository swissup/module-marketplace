define([
    'Magento_Ui/js/grid/provider'
], function (Provider) {
    'use strict';

    return Provider.extend({
        defaults: {
            rows: [],
            imports: {
                rows: '${ $.rowsProvider }:rows'
            }
        }
    });
});
