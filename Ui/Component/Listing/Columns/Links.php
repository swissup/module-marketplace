<?php

namespace Swissup\Marketplace\Ui\Component\Listing\Columns;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Composer\ComposerInformation;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class Links extends \Magento\Ui\Component\Listing\Columns\Column
{
    const URL_PATH_DETAILS = 'swissup_marketplace/package/details';

    const COMMAND_REQUIRE = 'marketplace:require';
    const COMMAND_UPDATE = 'marketplace:update';
    const COMMAND_REMOVE = 'marketplace:remove';
    const COMMAND_ENABLE = 'marketplace:enable';
    const COMMAND_DISABLE = 'marketplace:disable';

    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param AuthorizationInterface $authorization
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    ) {
        $this->authorization = $authorization;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $isAllowed = $this->authorization->isAllowed('Swissup_Marketplace::package_manage');

        foreach ($dataSource['data']['items'] as &$item) {
            $key = $this->getData('name');
            $item[$key] = [
                // 'details' => $this->getDetailsLinkParams($item),
            ];

            foreach ($this->getData('links') as $link) {
                if (empty($item['remote']['marketplace']['links'][$link['key']])) {
                    continue;
                }

                $item[$key][$link['key']] = [
                    'href'  => $item['remote']['marketplace']['links'][$link['key']],
                    'label' => __($link['label']),
                    'target' => '_blank',
                    'rel' => 'noopener',
                ];
            }

            if (!$isAllowed) {
                continue;
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
            'label' => __('View Details')
        ];
    }

    protected function getUpdateLinkParams($item)
    {
        $link = [
            'command' => static::COMMAND_UPDATE,
            'label' => $this->getLinkTitle('Update', $item),
        ];

        if (!$item['accessible']) {
            $link['label'] .= '*';
        }

        return $link;
    }

    protected function getDisableLinkParams($item)
    {
        return [
            'command' => static::COMMAND_DISABLE,
            'label' => $this->getLinkTitle('Disable', $item),
        ];
    }

    protected function getEnableLinkParams($item)
    {
        return [
            'command' => static::COMMAND_ENABLE,
            'label' => $this->getLinkTitle('Enable', $item),
        ];
    }

    protected function getUninstallLinkParams($item)
    {
        return [
            'command' => static::COMMAND_REMOVE,
            'label' => $this->getLinkTitle('Remove', $item),
        ];
    }

    protected function getInstallLinkParams($item)
    {
        if ($item['downloaded']) {
            return [
                'installer' => true,
                'label' => __('Run Installer'),
            ];
        }

        $link = [
            'command' => static::COMMAND_REQUIRE,
            'label' => $this->getLinkTitle('Install', $item),
        ];

        if (!$item['accessible']) {
            $link['label'] .= '*';
        }

        return $link;
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
