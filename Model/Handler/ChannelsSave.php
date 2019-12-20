<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;
use Swissup\Marketplace\Model\ChannelRepository;

class ChannelsSave extends AbstractHandler implements HandlerInterface
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

    public function getTitle()
    {
        return __('Save Channel Data');
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
