define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/alert'
], function ($, $t, uiAlert) {
    'use strict';

    return {
        /**
         * @param {String} command
         * @param {Array} packages
         * @return {String}
         */
        build: function (command, packages) {
            return ['bin/magento', command].concat(packages).join(' ');
        },

        /**
         * @param {Object} action
         * @param {Array} packages
         */
        show: function (action, packages) {
            var self = this,
                command = this.build(action.command, packages);

            uiAlert({
                title: action.label || $t('Run the command'),
                modalClass: 'marketplace-command-popup',
                content: this.render(command),
                buttons: [{
                    text: $t('Copy'),
                    class: 'action-secondary action-copy',

                    /**
                     * Click handler.
                     *
                     * @param {Object} event
                     */
                    click: function (event) {
                        var button = $(event.currentTarget);

                        self.copy(command).done(function () {
                            self.flash(button);
                        });
                    }
                }, {
                    text: $t('OK'),
                    class: 'action-primary action-accept',

                    /**
                     * Click handler.
                     */
                    click: function () {
                        this.closeModal(true);
                    }
                }]
            });
        },

        /**
         * @param {String} command
         * @return {jQuery}
         */
        render: function (command) {
            return $('<div class="marketplace-command"></div>')
                .append(
                    $('<p></p>').text(
                        $t('Run the following command from the Magento root directory:')
                    )
                )
                .append(
                    $('<pre></pre>').append($('<code></code>').text(command))
                );
        },

        /**
         * @param {jQuery} button
         */
        flash: function (button) {
            var label = button.find('span'),
                text = label.data('label');

            if (!text) {
                text = label.text();
                label.data('label', text);
            }

            label.text($t('Copied'));

            setTimeout(function () {
                label.text(text);
            }, 2000);
        },

        /**
         * @param {String} text
         * @return {jQuery.Deferred}
         */
        copy: function (text) {
            var self = this,
                deferred = $.Deferred();

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(
                    function () {
                        deferred.resolve();
                    },
                    function () {
                        self.resolveWithFallback(deferred, text);
                    }
                );

                return deferred;
            }

            return this.resolveWithFallback(deferred, text);
        },

        /**
         * @param {jQuery.Deferred} deferred
         * @param {String} text
         * @return {jQuery.Deferred}
         */
        resolveWithFallback: function (deferred, text) {
            if (this.copyFallback(text)) {
                deferred.resolve();
            } else {
                deferred.reject();
            }

            return deferred;
        },

        /**
         * @param {String} text
         * @return {Boolean}
         */
        copyFallback: function (text) {
            var textarea = $('<textarea></textarea>')
                    .val(text)
                    .css({
                        position: 'fixed',
                        top: 0,
                        left: 0,
                        opacity: 0
                    })
                    .appendTo('body'),
                copied;

            textarea[0].select();

            try {
                copied = document.execCommand('copy');
            } catch (e) {
                copied = false;
            }

            textarea.remove();

            return copied;
        }
    };
});
