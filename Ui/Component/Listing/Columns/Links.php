<?php

namespace Swissup\Marketplace\Ui\Component\Listing\Columns;

class Links extends \Magento\Ui\Component\Listing\Columns\Column
{
    const URL_PATH_DETAILS = 'swissup_marketplace/package/details';
    const URL_PATH_INSTALL = 'swissup_marketplace/package/install';
    const URL_PATH_UPDATE = 'swissup_marketplace/package/update';
    const URL_PATH_DELETE = 'swissup_marketplace/package/delete';
    const URL_PATH_ENABLE = 'swissup_marketplace/package/enable';
    const URL_PATH_DISABLE = 'swissup_marketplace/package/disable';

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
            $key = $this->getData('name');
            $item[$key] = [
                'details' => $this->getDetailsLinkParams($item),
            ];

            foreach ($this->getData('links') as $link) {
                if (empty($item['remote']['marketplace']['links'][$link['key']])) {
                    continue;
                }

                $item[$key][$link['key']] = [
                    'href'  => $item['remote']['marketplace']['links'][$link['key']],
                    'label' => __($link['label']),
                    'target' => '_blank',
                ];
            }

            if ($item['installed']) {
                if ($item['state'] === 'outdated') {
                    $item[$key]['update'] = $this->getUpdateLinkParams($item);
                }

                if ($item['enabled']) {
                    $item[$key]['disable'] = $this->getDisableLinkParams($item);
                } else {
                    $item[$key]['enable'] = $this->getEnableLinkParams($item);
                }

                $item[$key]['delete'] = $this->getUninstallLinkParams($item);
            } else {
                $item[$key]['install'] = $this->getInstallLinkParams($item);
            }
        }

        return $dataSource;
    }

    protected function getDetailsLinkParams($item)
    {
        return [
            'href' => $this->getContext()->getUrl(
                static::URL_PATH_DETAILS,
                [
                    'name' => $item['name']
                ]
            ),
            'label' => __('View Details')
        ];
    }

    protected function getUpdateLinkParams($item)
    {
        return [
            'href' => $this->getContext()->getUrl(
                static::URL_PATH_UPDATE,
                [
                    'name' => $item['name']
                ]
            ),
            'label' => __('Update Module'),
        ];
    }

    protected function getDisableLinkParams($item)
    {
        return [
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
    }

    protected function getEnableLinkParams($item)
    {
        return [
            'href' => $this->getContext()->getUrl(
                static::URL_PATH_ENABLE,
                [
                    'name' => $item['name']
                ]
            ),
            'label' => __('Enable Module'),
        ];
    }

    protected function getUninstallLinkParams($item)
    {
        return [
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
    }

    protected function getInstallLinkParams($item)
    {
        return [
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
