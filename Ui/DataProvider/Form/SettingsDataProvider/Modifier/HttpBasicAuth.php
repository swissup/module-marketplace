<?php

namespace Swissup\Marketplace\Ui\DataProvider\Form\SettingsDataProvider\Modifier;

class HttpBasicAuth extends AbstractModifier
{
    protected function getData()
    {
        return array_merge(parent::getData(), [
            'username' => $this->channel->getUsername(),
            'password' => $this->channel->getPassword(),
        ]);
    }

    /**
     * Http-basic auth fields
     *
     * @return array
     */
    protected function getFields()
    {
        return array_merge(parent::getFields(), $this->getAuthFields());
    }

    /**
     * Standard username and password fields
     *
     * @return array
     */
    protected function getAuthFields()
    {
        return array_merge(
            $this->getUsernameField(),
            [
                'password_wrapper' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'showLabel' => true,
                                'breakLine' => false,
                                'sortOrder' => 100,
                                'componentType' => 'container',
                                'formElement' => 'container',
                                'component' => 'Magento_Ui/js/form/components/group',
                            ],
                        ],
                    ],
                    'children' => array_merge(
                        $this->getPasswordField(),
                        [
                            'button' => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'title' => __('Test'),
                                            'formElement' => 'container',
                                            'componentType' => 'container',
                                            'url' => $this->urlBuilder->getUrl('swissup_marketplace/channel/validate'),
                                            'component' => 'Swissup_Marketplace/js/channel-form/validate-button',
                                            'template' => 'ui/form/components/button/container',
                                            'displayArea' => 'insideGroup',
                                            'additionalForGroup' => true,
                                            'additionalClasses' => 'marketplace-validate-button-group',
                                            'actions' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * @param array $config
     * @return array
     */
    protected function getUsernameField(array $config = [])
    {
        if ($this->channel->useDomainAsUsername()) {
            $config['label'] = __('Domain');
            $config['elementTmpl'] = 'ui/form/element/text';
        }

        return [
            'username' => [
                'arguments' => [
                    'data' => [
                        'config' => array_merge([
                            'dataType' => 'text',
                            'label' => __('Username'),
                            'formElement' => 'input',
                            'componentType' => 'field',
                        ], $config),
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $config
     * @return array
     */
    protected function getPasswordField(array $config = [])
    {
        return [
            'password' => [
                'arguments' => [
                    'data' => [
                        'config' => array_merge([
                            'dataType' => 'text',
                            'label' => __('Password'),
                            'formElement' => 'input',
                            'componentType' => 'field',
                            'tooltip' => [
                                'description' => $this->channel->getAuthNotice(),
                            ],
                        ], $config),
                    ],
                ],
            ],
        ];
    }
}
