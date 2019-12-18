<?php

namespace Swissup\Marketplace\Job;

use Swissup\Marketplace\Api\JobInterface;
use Swissup\Marketplace\Model\ChannelRepository;

class ChannelsSave extends AbstractJob implements JobInterface
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var ChannelRepository
     */
    private $channelRepository;

    /**
     * @param array $data
     * @param ChannelRepository $channelRepository
     */
    public function __construct(
        array $data,
        ChannelRepository $channelRepository
    ) {
        $this->data = $data;
        $this->channelRepository = $channelRepository;
    }

    public function execute()
    {
        foreach ($this->channelRepository->getList() as $channel) {
            $data = $this->data[$channel->getIdentifier()] ?? false;

            if (!$data) {
                continue;
            }

            $channel->addData($data)->save();
        }
    }
}
