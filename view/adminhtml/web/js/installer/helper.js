define([
    'underscore',
    'uiRegistry'
], function (_, registry) {
    'use strict';

    return {
        /**
         * @param {Array} packages
         */
        render: function (packages) {
            var modal = [
                    'swissup_marketplace_package_listing',
                    'swissup_marketplace_package_listing',
                    'marketplace_installer'
                ].join('.'),
                form = registry.get(modal + '.installer_form'),
                source = [
                    'swissup_marketplace_package_listing',
                    'swissup_marketplace_package_listing_data_source'
                ].join('.'),
                hasInstaller;

            source = registry.get(source);

            hasInstaller = _.every(packages, function (packageName) {
                var packageData = _.find(source.rows, function (row) {
                    return row.name === packageName;
                });

                return packageData && packageData.installer;
            }, this);

            if (!hasInstaller) {
                return;
            }

            form.params.packages = packages;

            form.destroyInserted();
            registry.get(modal).openModal();
            form.render();
        }
    };
});
