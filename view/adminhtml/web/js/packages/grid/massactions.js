define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/grid/massactions',
    'Swissup_Marketplace/js/packages/helper',
    'Swissup_Marketplace/js/installer/helper'
], function (_, registry, Massactions, packageHelper, installer) {
    'use strict';

    return Massactions.extend({
        /**
         * @param {Object} action - Action data.
         * @param {Object} data - Selections data.
         */
        defaultCallback: function (action, data) {
            var isDownloaded, isActionVisible;

            action.index = action.type;
            action.href = action.url;

            if (!data.selected) {
                return;
            }

            isActionVisible = _.every(data.selected, function (packageName) {
                var packageData = _.find(this.source.rows, function (row) {
                    return row.name === packageName;
                });

                return packageData && packageHelper.isActionVisible(packageData, action);
            }, this);

            if (!isActionVisible) {
                return;
            }

            isDownloaded = _.every(data.selected, function (packageName) {
                var packageData = _.find(this.source.rows, function (row) {
                    return row.name === packageName;
                });

                return packageData && packageData.downloaded;
            }, this);

            if (action.index === 'install' && isDownloaded) {
                installer.render(data.selected);
            } else {
                this.source
                    .submit(action, data.selected)
                    .done(function () {
                        if (action.index === 'install') {
                            installer.render(data.selected);
                        }

                        setTimeout(function () {
                            this.selections().deselectAll();
                        }.bind(this), 300);
                    }.bind(this));
            }
        }
    });
});
