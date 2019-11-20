<?php

namespace Swissup\Marketplace\Ui\DataProvider\Form\SettingsDataProvider\Modifier;

use Magento\Framework\UrlInterface;
use Swissup\Marketplace\Api\ChannelInterface;

class AbstractModifier implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    /**
     * @var ChannelInterface
     */
    protected $channel;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param ChannelInterface $channel
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        ChannelInterface $channel,
        UrlInterface $urlBuilder
    ) {
        $this->channel = $channel;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @return ChannelInterface $channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        $data['main']['channels'][$this->channel->getIdentifier()] = $this->getData();

        return $data;
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'url' => $this->channel->getUrl(),
            'enabled' => (int) $this->channel->isEnabled(),
        ];
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $meta['channels']['children']
            [$this->channel->getIdentifier()] = $this->getFieldset();

        return $meta;
    }

    /**
     * @param string $id
     * @param string $title
     * @param mixed $fields
     * @return array
     */
    protected function getFieldset()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => $this->channel->getTitle()
                            . ($this->channel->isEnabled() ? '' : ' [' . __('Disabled') . ']'),
                        'dataScope' => 'channels.' . $this->channel->getIdentifier(),
                        'componentType' => 'fieldset',
                        'collapsible' => true,
                        'opened' => false,
                    ],
                ],
            ],
            'children' => [
                'enabled' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __('Enabled'),
                                'dataType' => 'boolean',
                                'componentType' => 'field',
                                'formElement' => 'checkbox',
                                'prefer' => 'toggle',
                                'valueMap' => [
                                    'true' => 1,
                                    'false' => 0,
                                ],
                                'default' => 0,
                            ],
                        ],
                    ],
                ],
                'url' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __('URL'),
                                'formElement' => 'input',
                                'componentType' => 'field',
                                'elementTmpl' => 'ui/form/element/text',
                            ],
                        ],
                    ],
                ],
            ] + $this->getFields(),
        ];
    }

    /**
     * @param mixed $fields
     * @return array
     */
    protected function getFields()
    {
        return [];
    }
}
