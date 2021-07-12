<?php

namespace Swissup\Marketplace\Ui\Component\Listing\Columns;

use Magento\Framework\Escaper;
use Magento\Framework\Data\OptionSourceInterface;
use Swissup\Marketplace\Model\ChannelRepository;

class Channel implements OptionSourceInterface
{
    /**
     * @var ChannelRepository
     */
    private $channelRepository;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param ChannelRepository $channelRepository
     * @param Escaper $escaper
     */
    public function __construct(
        ChannelRepository $channelRepository,
        Escaper $escaper
    ) {
        $this->channelRepository = $channelRepository;
        $this->escaper = $escaper;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = [
            [
                'label' => $this->escaper->escapeHtml(__('All Channels')),
                'value' => '',
            ]
        ];

        foreach ($this->channelRepository->getList(true) as $channel) {
            $result[] = [
                'label' => $this->escaper->escapeHtml($channel->getTitle()),
                'value' => $this->escaper->escapeHtml($channel->getIdentifier()),
            ];
        }

        return $result;
    }
}
