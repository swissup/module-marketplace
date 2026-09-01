define([
    'underscore',
    'Magento_Ui/js/grid/massactions',
    'Swissup_Marketplace/js/packages/helper',
    'Swissup_Marketplace/js/packages/command',
    'Swissup_Marketplace/js/installer/helper'
], function (_, Massactions, packageHelper, command, installer) {
    'use strict';

    return Massactions.extend({
        /**
         * @param {Object} action - Action data.
         * @param {Object} data - Selections data.
         */
        defaultCallback: function (action, data) {
            var packages = data.selected || [];

            action.index = action.type;

            packages = this.getActionPackages(packages, action);

            if (!packages.length) {
                return;
            }

            if (action.index === 'install' && this.isAllDownloaded(packages)) {
                installer.render(packages);

                return;
            }

            if (action.index === 'install') {
                packages = _.reject(packages, function (packageName) {
                    var packageData = this.findPackageData(packageName);

                    return packageData && packageData.downloaded;
                }, this);
            }

            command.show(action, packages);
        },

        /**
         * @param {Array} packages
         * @param {Object} action
         * @return {Array}
         */
        getActionPackages: function (packages, action) {
            return _.filter(packages, function (packageName) {
                var packageData = this.findPackageData(packageName);

                return packageData &&
                    this.hasActionLink(packageData, action) &&
                    packageHelper.isActionVisible(packageData, action);
            }, this);
        },

        /**
         * Row actions are registered per package type - enable and disable are
         * added to the modules only, for example.
         *
         * @param {Object} packageData
         * @param {Object} action
         * @return {Boolean}
         */
        hasActionLink: function (packageData, action) {
            return Boolean(packageData.links && packageData.links[action.index]);
        },

        /**
         * @param {Array} packages
         * @return {Boolean}
         */
        isAllDownloaded: function (packages) {
            return _.every(packages, function (packageName) {
                var packageData = this.findPackageData(packageName);

                return packageData && packageData.downloaded;
            }, this);
        },

        /**
         * @param {String} packageName
         * @return {Object|null}
         */
        findPackageData: function (packageName) {
            var packageData = _.find(this.source.rows, function (row) {
                return row.name === packageName;
            });

            if (!packageData) {
                packageData = _.find(this.source.storage().data, function (row) {
                    return row.name === packageName;
                });
            }

            return packageData;
        }
    });
});
