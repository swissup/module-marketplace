<?php

namespace Swissup\Marketplace\Ui\DataProvider\Form\SettingsDataProvider\Modifier;

class AbstractModifier implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    /**
     * @var \Swissup\Marketplace\Model\Channel\AbstractChannel
     */
    protected $channel;

    /**
     * @param \Swissup\Marketplace\Model\Channel\AbstractChannel $channel
     */
    public function __construct(
        \Swissup\Marketplace\Model\Channel\AbstractChannel $channel
    ) {
        $this->channel = $channel;
    }

    /**
     * @return \Swissup\Marketplace\Model\Channel\AbstractChannel $channel
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
                        'opened' => true,
                    ],
                ],
            ],
            'children' => [
                'enabled' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __('Enabled'),
                                'notice' => $this->channel->isEnabled() ? '' : __(
                                    'Run this shell command to enable channel: composer config repositories.%1 %2 %3',
                                    $this->channel->getIdentifier(),
                                    $this->channel->getType(),
                                    $this->channel->getUrl()
                                ),
                                'dataType' => 'boolean',
                                'componentType' => 'field',
                                'formElement' => 'checkbox',
                                'prefer' => 'toggle',
                                'valueMap' => [
                                    'true' => 1,
                                    'false' => 0,
                                ],
                                'default' => 0,
                                'disabled' => true,
                                'visible' => !$this->channel->isEnabled(),
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
                                'visible' => $this->channel->isEnabled(),
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
