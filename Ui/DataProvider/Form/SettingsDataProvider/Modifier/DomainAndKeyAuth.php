<?php

namespace Swissup\Marketplace\Ui\DataProvider\Form\SettingsDataProvider\Modifier;

class DomainAndKeyAuth extends HttpBasicAuth
{
    /**
     * Modify fields to authenticate by domain name and access_key.
     * We use it for swissuplabs.com, argentotheme.com, and firecheckout.com
     *
     * @return array
     */
    protected function getAuthFields()
    {
        $fields = parent::getAuthFields();

        return array_replace_recursive($fields, [
            'username' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Domain'),
                            'formElement' => 'input',
                            'componentType' => 'field',
                            'elementTmpl' => 'ui/form/element/text',
                        ],
                    ],
                ],
            ],
            'password_wrapper' => [
                'children' => [
                    'password' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'label' => __('Access Key')
                                ],
                            ],
                        ],
                    ],
                    'button' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'title' => __('Add'),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
