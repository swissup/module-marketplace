<?php

namespace Swissup\Marketplace\Ui\Component\Listing\Columns;

use Magento\Framework\Composer\ComposerInformation;

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

            if ($item['type'] === ComposerInformation::MODULE_PACKAGE_TYPE) {
                $item[$key]['disable'] = $this->getDisableLinkParams($item);
                $item[$key]['enable'] = $this->getEnableLinkParams($item);
            }

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
            'label' => __('(N/A) View Details')
        ];
    }

    protected function getUpdateLinkParams($item)
    {
        return [
            'isAjax' => true,
            'href' => $this->getContext()->getUrl(static::URL_PATH_UPDATE),
            'label' => $this->getLinkTitle('Update', $item),
        ];
    }

    protected function getDisableLinkParams($item)
    {
        return [
            'isAjax' => true,
            'href' => $this->getContext()->getUrl(static::URL_PATH_DISABLE),
            'label' => $this->getLinkTitle('Disable', $item),
            'confirm' => [
                'title' => $this->getLinkTitle('Disable', $item),
                'message' => __('Are you sure you want to do this?')
            ]
        ];
    }

    protected function getEnableLinkParams($item)
    {
        return [
            'isAjax' => true,
            'href' => $this->getContext()->getUrl(static::URL_PATH_ENABLE),
            'label' =>$this->getLinkTitle('Enable', $item),
        ];
    }

    protected function getUninstallLinkParams($item)
    {
        return [
            'isAjax' => true,
            'href' => $this->getContext()->getUrl(static::URL_PATH_UNINSTALL),
            'label' => $this->getLinkTitle('(N/A) Uninstall', $item),
            'confirm' => [
                'title' => $this->getLinkTitle('Uninstall', $item),
                'message' => __('Are you sure you want to do this?')
            ]
        ];
    }

    protected function getInstallLinkParams($item)
    {
        return [
            'isAjax' => true,
            'href' => $this->getContext()->getUrl(static::URL_PATH_INSTALL),
            'label' => $this->getLinkTitle('(N/A) Install', $item),
        ];
    }

    protected function getLinkTitle($actionTitle, $item)
    {
        switch ($item['type']) {
            case ComposerInformation::MODULE_PACKAGE_TYPE:
                $suffix = 'Module';
                break;
            case ComposerInformation::METAPACKAGE_PACKAGE_TYPE:
                $suffix = 'Bundle';
                break;
            case ComposerInformation::THEME_PACKAGE_TYPE:
                $suffix = 'Theme';
                break;
            case ComposerInformation::LANGUAGE_PACKAGE_TYPE:
                $suffix = 'Language';
                break;
            case ComposerInformation::LIBRARY_PACKAGE_TYPE:
                $suffix = 'Library';
                break;
            case ComposerInformation::COMPONENT_PACKAGE_TYPE:
                $suffix = 'Component';
                break;
            default:
                $suffix = 'Item';
        }
        return __($actionTitle . ' ' . $suffix);
    }
}
