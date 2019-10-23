<?php

namespace Swissup\Marketplace\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    const URL_PATH_INSTALL = 'marketplace/package/install';
    const URL_PATH_UPGRADE = 'marketplace/package/upgrade';
    const URL_PATH_DELETE = 'marketplace/package/delete';
    const URL_PATH_ENABLE = 'marketplace/package/enable';
    const URL_PATH_DISABLE = 'marketplace/package/disable';
    const URL_PATH_DETAILS = 'marketplace/package/details';

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $item[$this->getData('name')] = [
                'details' => [
                    'href' => $this->getContext()->getUrl(
                        static::URL_PATH_DETAILS,
                        [
                            'name' => $item['name']
                        ]
                    ),
                    'label' => __('View Details')
                ],
            ];

            if ($item['installed']) {
                if ($item['enabled']) {
                    $item[$this->getData('name')]['disable'] = [
                        'href' => $this->getContext()->getUrl(
                            static::URL_PATH_DISABLE,
                            [
                                'name' => $item['name']
                            ]
                        ),
                        'label' => __('Disable Module'),
                        'confirm' => [
                            'title' => __('Disable'),
                            'message' => __('Are you sure you want to disable this module?')
                        ]
                    ];
                } else {
                    $item[$this->getData('name')]['enable'] = [
                        'href' => $this->getContext()->getUrl(
                            static::URL_PATH_ENABLE,
                            [
                                'name' => $item['name']
                            ]
                        ),
                        'label' => __('Enable Module'),
                    ];
                }

                $item[$this->getData('name')]['delete'] = [
                    'href' => $this->getContext()->getUrl(
                        static::URL_PATH_DELETE,
                        [
                            'name' => $item['name']
                        ]
                    ),
                    'label' => __('Uninstall Module'),
                    'confirm' => [
                        'title' => __('Uninstall'),
                        'message' => __('Are you sure you want to uninstall this module?')
                    ]
                ];
            } else {
                $item[$this->getData('name')]['install'] = [
                    'href' => $this->getContext()->getUrl(
                        static::URL_PATH_INSTALL,
                        [
                            'name' => $item['name']
                        ]
                    ),
                    'label' => __('Install Module'),
                ];
            }

        }

        return $dataSource;
    }
}
