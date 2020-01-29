define([
    'uiRegistry'
], function (registry) {
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
                form = registry.get(modal + '.installer_form');

            form.params.packages = packages;

            form.destroyInserted();
            registry.get(modal).openModal();
            form.render();
        }
    };
});
