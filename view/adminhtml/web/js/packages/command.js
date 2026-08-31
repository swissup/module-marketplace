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
                        self.copy(command);
                        self.flash($(event.currentTarget));
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
         */
        copy: function (text) {
            var textarea;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text);

                return;
            }

            textarea = $('<textarea></textarea>')
                .val(text)
                .css({
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    opacity: 0
                })
                .appendTo('body');

            textarea[0].select();
            document.execCommand('copy');
            textarea.remove();
        }
    };
});
