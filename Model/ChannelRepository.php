<?php

namespace Swissup\Marketplace\Model;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Swissup\Marketplace\Api\ChannelInterface;

class ChannelRepository
{
    /**
     * @var \Swissup\Marketplace\Model\ChannelManager
     */
    private $channelManager;

    /**
     * @var \Swissup\Marketplace\Model\ChannelFactory
     */
    private $channelFactory;

    /**
     * @var ChannelInterface[]
     */
    private $channels = [];

    /**
     * @param array $channels
     * @param \Swissup\Marketplace\Model\ChannelManager $channelManager
     * @param \Swissup\Marketplace\Model\ChannelFactory $channelFactory
     */
    public function __construct(
        array $channels,
        \Swissup\Marketplace\Model\ChannelManager $channelManager,
        \Swissup\Marketplace\Model\ChannelFactory $channelFactory
    ) {
        $this->channelManager = $channelManager;
        $this->channelFactory = $channelFactory;

        $this->setChannels($channels);
    }

    /**
     * @param ChannelInterface[] $channels
     * @throws AlreadyExistsException
     */
    private function setChannels($channels)
    {
        $urls = [];

        foreach ($channels as $channel) {
            $identifier = $channel->getIdentifier();

            if (array_key_exists($identifier, $this->channels)) {
                throw new AlreadyExistsException(__('Channel "%1" already exists.', $identifier));
            }

            $this->channels[$identifier] = $channel;
            $urls[$channel->getUrl()] = true;
        }

        // create runtime channels (for composer http-basic type only)
        foreach ($this->channelManager->getAllChannels() as $identifier => $data) {
            if (!isset($data['url']) || strpos($data['url'], 'packagist.org') !== false) {
                continue;
            }

            if (isset($urls[$data['url']])) {
                continue;
            }

            $data['runtime'] = true;
            $data['authType'] = 'http-basic';

            try {
                $channel = $this->channelFactory->create($data);
            } catch (\Exception $e) {
                continue;
            }

            $this->channels[$channel->getIdentifier()] = $channel;
        }

        return $this;
    }

    /**
     * @param string $identifier
     * @param boolean $orFirst
     * @return ChannelInterface
     * @throws NoSuchEntityException
     */
    public function getById($identifier)
    {
        foreach ($this->getList() as $channel) {
            if ($channel->getIdentifier() === $identifier) {
                return $channel;
            }
        }

        throw new NoSuchEntityException(__('Channel "%1" does not exist.', $identifier));
    }

    /**
     * @param string $identifier
     * @return ChannelInterface
     * @throws NoSuchEntityException
     */
    public function getFirstEnabled($identifier = null)
    {
        if ($identifier) {
            try {
                $channel = $this->getById($identifier);
            } catch (NoSuchEntityException $e) {
                // not found
            }

            if ($channel->isEnabled()) {
                return $channel;
            }
        }

        foreach ($this->getList(true) as $channel) {
            return $channel;
        }

        throw new NoSuchEntityException(__('No active channels found.'));
    }

    /**
     * @return ChannelInterface[]
     */
    public function getList($enabledOnly = false)
    {
        if (!$enabledOnly) {
            return $this->channels;
        }

        $result = [];
        foreach ($this->channels as $channel) {
            if (!$channel->isEnabled()) {
                continue;
            }
            $result[] = $channel;
        }

        return $result;
    }
}
