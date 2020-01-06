<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;
use Swissup\Marketplace\Model\ChannelRepository;

class ChannelsSave extends AbstractHandler implements HandlerInterface
{
    /**
     * @var array
     */
    private $channels;

    /**
     * @var ChannelRepository
     */
    private $channelRepository;

    /**
     * @param array $channels
     * @param ChannelRepository $channelRepository
     * @param array $data
     */
    public function __construct(
        array $channels,
        ChannelRepository $channelRepository,
        array $data = []
    ) {
        $this->channels = $channels;
        $this->channelRepository = $channelRepository;
        parent::__construct($data);
    }

    public function getTitle()
    {
        return __('Save Channel Data');
    }

    public function execute()
    {
        foreach ($this->channelRepository->getList() as $channel) {
            $data = $this->channels[$channel->getIdentifier()] ?? false;

            if (!$data) {
                continue;
            }

            $channel->addData($data)->save();
        }
    }
}
