<?php

namespace Swissup\Marketplace\Ui\DataProvider\Form\SettingsDataProvider\Modifier;

class DomainAndKey extends DomainAsUsername
{
    /**
     * Modify fields to hide password field and show access_key field.
     *
     * @return array
     */
    protected function getAuthFields()
    {
        $fields = parent::getAuthFields();

        return array_replace_recursive($fields, [
            'password' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            // @todo custom renderer
                            'dataType' => 'text',
                            'sortOrder' => 90,
                            'label' => '',
                            'formElement' => 'input',
                            'componentType' => 'field',
                        ],
                    ],
                ],
            ],
            'password_wrapper' => [
                'children' => [
                    'key' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dataType' => 'text',
                                    'label' => __('Access Key'),
                                    'formElement' => 'input',
                                    'componentType' => 'field',
                                ],
                            ],
                        ],
                    ],
                    'button' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'title' => __('Add'),
                                    'component' => 'Swissup_Marketplace/js/channel-form/add-key-button'
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Remove password field. Will move it above password row.
     * @see getAuthFields
     *
     * @param array $config
     * @return array
     */
    protected function getPasswordField(array $config = [])
    {
        return [];
    }
}
