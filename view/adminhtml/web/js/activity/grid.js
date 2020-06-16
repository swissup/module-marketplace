define([
    'jquery',
    'ko',
    'Magento_Ui/js/grid/listing',
    'mage/translate'
], function ($, ko, Listing, $t) {
    'use strict';

    return Listing.extend({
        timer: false,

        /**
         * Initializes observable properties.
         *
         * @returns {Listing} Chainable.
         */
        initObservable: function () {
            this._super()
                .observe(['secondsToNextQueue']);

            return this;
        },

        /**
         * Handler of the data providers' 'reloaded' event.
         */
        onDataReloaded: function () {
            this._super();

            this.secondsToNextQueue(this.source.data.secondsToNextQueue);

            if (this.timer) {
                clearInterval(this.timer);
            }

            this.timer = setInterval(function () {
                this.secondsToNextQueue(this.secondsToNextQueue() - 1);
            }.bind(this), 1000);
        },

        /**
         * @return {String}
         */
        secondsToNextQueuePhrase: function () {
            var seconds = this.secondsToNextQueue();

            if (!this.source.hasUnfinishedJobs()) {
                return $t('All Tasks Completed');
            }

            if (seconds <= 0) {
                return $t('Running..');
            }

            return $t('Queue Starts in %1 seconds').replace('%1', seconds);
        },

        /**
         * @return {Boolean}
         */
        isRunning: function () {
            return this.secondsToNextQueue() <= 0 && this.source.hasUnfinishedJobs();
        },

        /**
         * @return {Boolean}
         */
        canHideCompleted: function () {
            return !this.isRunning() && this.source.hasCompletedJobs();
        },

        /**
         * Mark completed items as hidden
         */
        hideCompleted: function () {
            this.source.hideCompleted();
        }
    });
});
