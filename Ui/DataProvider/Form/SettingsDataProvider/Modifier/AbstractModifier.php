<?php

namespace Swissup\Marketplace\Ui\DataProvider\Form\SettingsDataProvider\Modifier;

use Magento\Framework\UrlInterface;
use Swissup\Marketplace\Api\ChannelInterface;

/**
 * The settings form was removed - channels are configured with the
 * marketplace:channel:* and marketplace:auth:* commands. The modifiers are kept
 * as stubs, so that the channel modules registering them in the pool still
 * compile.
 */
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
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }
}
