<?php

namespace Swissup\Marketplace\Ui\DataProvider\Form\SettingsDataProvider\Modifier;

class DomainAsUsername extends HttpBasicAuth
{
    /**
     * Replace username field with read-only domain name.
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
        ]);
    }
}
