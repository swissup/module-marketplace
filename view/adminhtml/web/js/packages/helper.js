define([], function () {
    'use strict';

    return {
        /**
         * @param {Object} packageData
         * @param {Object} action
         * @return {Boolean}
         */
        isActionVisible: function (packageData, action) {
            if (action.hidden) {
                return false;
            }

            switch (action.index) {
                case 'update':
                    return packageData.state === 'outdated';

                case 'disable':
                    return packageData.enabled && packageData.downloaded;

                case 'enable':
                    return !packageData.enabled && packageData.downloaded;

                case 'uninstall':
                    return packageData.composer && packageData.downloaded;

                case 'install':
                    return !packageData.downloaded ||
                        packageData.enabled && packageData.installer;
            }

            return true;
        }
    };
});
