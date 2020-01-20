<?php

namespace Swissup\Marketplace\Ui\DataProvider\Form\SettingsDataProvider\Modifier;

class DomainAsUsername extends HttpBasicAuth
{
    /**
     * Replace username field with read-only domain name.
     *
     * @param array $config
     * @return array
     */
    protected function getUsernameField(array $config = [])
    {
        $field = parent::getUsernameField($config);

        return array_replace_recursive($field, [
            'username' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Domain'),
                            'elementTmpl' => 'ui/form/element/text',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
