<?php

namespace Swissup\Marketplace\Ui\Component\Listing\Columns;

class Links extends \Magento\Ui\Component\Listing\Columns\Column
{
    const URL_PATH_DETAILS = 'swissup_marketplace/package/details';
    const URL_PATH_INSTALL = 'swissup_marketplace/package/install';
    const URL_PATH_UNINSTALL = 'swissup_marketplace/package/uninstall';
    const URL_PATH_UPDATE = 'swissup_marketplace/package/update';
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

            $item[$key]['separator'] = $this->getSeparatorParams();
            $item[$key]['update'] = $this->getUpdateLinkParams($item);
            $item[$key]['disable'] = $this->getDisableLinkParams($item);
            $item[$key]['enable'] = $this->getEnableLinkParams($item);
            $item[$key]['uninstall'] = $this->getUninstallLinkParams($item);
            $item[$key]['install'] = $this->getInstallLinkParams($item);
        }

        return $dataSource;
    }

    protected function getSeparatorParams()
    {
        return ['href' => '#', 'label' => ''];
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
            'isAjax' => true,
            'href' => $this->getContext()->getUrl(static::URL_PATH_UPDATE),
            'label' => __('Update Module'),
        ];
    }

    protected function getDisableLinkParams($item)
    {
        return [
            'isAjax' => true,
            'href' => $this->getContext()->getUrl(static::URL_PATH_DISABLE),
            'label' => __('Disable Module'),
            'confirm' => [
                'title' => __('Disable Module'),
                'message' => __('Are you sure you want to disable this module?')
            ]
        ];
    }

    protected function getEnableLinkParams($item)
    {
        return [
            'isAjax' => true,
            'href' => $this->getContext()->getUrl(static::URL_PATH_ENABLE),
            'label' => __('Enable Module'),
        ];
    }

    protected function getUninstallLinkParams($item)
    {
        return [
            'isAjax' => true,
            'href' => $this->getContext()->getUrl(static::URL_PATH_UNINSTALL),
            'label' => __('Uninstall Module'),
            'confirm' => [
                'title' => __('Uninstall Module'),
                'message' => __('Are you sure you want to uninstall this module?')
            ]
        ];
    }

    protected function getInstallLinkParams($item)
    {
        return [
            'isAjax' => true,
            'href' => $this->getContext()->getUrl(static::URL_PATH_INSTALL),
            'label' => __('Install Module'),
        ];
    }
}
