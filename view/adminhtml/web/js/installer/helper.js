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

            hasInstaller = _.some(packages, function (packageName) {
                var packageData = _.find(source.storage().data, function (row) {
                    return row.name === packageName;
                });

                return packageData && packageData.installer;
            }, this);

            if (!hasInstaller) {
                return;
            }

            if (!form.params.packages) {
                form.params.packages = [];
            }

            form.params.packages.push.apply(form.params.packages, packages);

            this._renderForm(form, modal);
        },

        _renderForm: _.debounce(function (form, modal) {
            form.destroyInserted();
            registry.get(modal).openModal();
            form.render();
        }, 300)
    };
});
