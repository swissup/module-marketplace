<?php

namespace Swissup\Marketplace\Ui\DataProvider\Form\SettingsDataProvider\Modifier;

class KeysAsPassword extends HttpBasicAuth
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
                            'dataType' => 'text',
                            'sortOrder' => 110,
                            'label' => 'Saved Keys',
                            'formElement' => 'input',
                            'componentType' => 'field',
                            'component' => 'Swissup_Marketplace/js/channel-form/keys'
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
                                    'tooltip' => [
                                        'description' => $this->channel->getAuthNotice(),
                                    ],
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
