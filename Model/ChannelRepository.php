<?php

namespace Swissup\Marketplace\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Swissup\Marketplace\Model\Channel\AbstractChannel;

class ChannelRepository
{
    /**
     * @var array
     */
    private $channels;

    /**
     * @var \Swissup\Marketplace\Model\Composer
     */
    private $composer;

    /**
     * @param array $channels
     * @param \Swissup\Marketplace\Model\Composer $composer
     */
    public function __construct(
        array $channels,
        \Swissup\Marketplace\Model\Composer $composer
    ) {
        $this->channels = $channels;
        $this->composer = $composer;
    }

    /**
     * @return AbstractChannel
     */
    public function getById($identifier)
    {
        foreach ($this->channels as $channel) {
            if ($channel->getIdentifier() === $identifier) {
                return $channel;
            }
        }
        throw new NoSuchEntityException(__('Channel "%1" does not exist.', $identifier));
    }

    /**
     * @return array
     */
    public function getList()
    {
        return $this->channels;
    }
}
