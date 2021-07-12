<?php

namespace Swissup\Marketplace\Ui\DataProvider\Form;

class PackageInstallDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Swissup\Marketplace\Installer\Installer
     */
    protected $installer;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Magento\Framework\App\RequestInterface $request,
        \Swissup\Marketplace\Installer\Installer $installer,
        array $meta = [],
        array $data = []
    ) {
        $this->request = $request;
        $this->installer = $installer;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
    }

    public function getPackages()
    {
        return $this->request->getParam('packages');
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return ['main' => [
            'packages' => $this->getPackages(),
        ]];
    }

    public function getMeta()
    {
        $meta = parent::getMeta();

        $packages = $this->getPackages();
        foreach ($packages as $package) {
            $meta['general']['children']['packages']['arguments']['data']['options'][] = [
                'value' => $package,
                'label' => $package,
            ];
        }

        try {
            $fields = $this->installer->getFormConfig($packages);
        } catch (\Exception $e) {
            $meta['general']['children']['exception'] = $this->createNotice($e->getMessage());
            $fields = [];
        }

        foreach ($fields as $field => $config) {
            if (!isset($meta['general']['children'][$field])) {
                $meta['general']['children'][$field] = $this->createField($config);
            } else {
                $meta['general']['children'][$field]['arguments']
                    ['data']['options'] += $config['options'];
                $meta['general']['children'][$field]['arguments']
                    ['data']['config']['visible'] = true;
            }
        }

        foreach ($fields as $field => $config) {
            if (!isset($meta['general']['children'][$field]['arguments']['data']['options']) ||
                count($meta['general']['children'][$field]['arguments']['data']['options']) <= 1
            ) {
                continue;
            }

            array_unshift(
                $meta['general']['children'][$field]['arguments']['data']['options'],
                [
                    'value' => '',
                    'label' => __('Select...'),
                ]
            );
        }

        return $meta;
    }

    /**
     * @param string $notice
     * @return array
     */
    private function createNotice($notice)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'value' => $notice,
                        'formElement' => 'input',
                        'componentType' => 'field',
                        'dataType' => 'text',
                        'template' => 'ui/form/element/text',
                    ],
                ],
            ],
        ];
    }

    private function createField(array $config)
    {
        $result = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => $config['title'],
                        'visible' => !isset($config['options']) || count($config['options']) > 1,
                        'formElement' => isset($config['options']) ? 'select' : 'input',
                        'componentType' => 'field',
                        'dataType' => 'text',
                        'required' => true,
                        'validation' => [
                            'required-entry' => true,
                        ],
                    ],
                ],
            ],
        ];

        if (isset($config['options']) && is_array($config['options'])) {
            $result['arguments']['data']['options'] = $config['options'];
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        // prevent parent method call 'cos it wants a collection
        return null;
    }
}
