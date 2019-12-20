define([], function () {
    'use strict';

    var mapping = {
        '0': 'pending',
        '1': 'queued',
        '2': 'running',
        '3': 'success',
        '4': 'skipped',
        '5': 'errored',
        '6': 'canceled'
    };

    /**
     * @param {Mixed} id
     * @return {String|Number}
     */
    function getId(id) {
        if (typeof id === 'object') {
            id = id.status;
        }

        return id;
    }

    return {
        /**
         * @param {Number|String|Object} id
         * @return {String} [description]
         */
        getCode: function (id) {
            return mapping[getId(id)];
        },

        /**
         * @param {String} code
         * @param {Number|String|Object} id
         * @return {Boolean}
         */
        is: function (code, id) {
            return code === mapping[getId(id)];
        }
    };
});
