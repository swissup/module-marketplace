<?php

namespace Swissup\Marketplace\Block\Adminhtml\Channel;

use Magento\Framework\View\Element\Template;
use Swissup\Marketplace\Api\ChannelInterface;

class Switcher extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Swissup_Marketplace::channel/switcher.phtml';

    /**
     * @var \Swissup\Marketplace\Model\ChannelRepository
     */
    private $channelRepository;

    /**
     * @var \Swissup\Marketplace\Model\Session
     */
    private $session;

    /**
     * @var ChannelInterface[]
     */
    private $channels;

    /**
     * @var ChannelInterface
     */
    private $channel;

    /**
     * @param Template\Context $context
     * @param \Swissup\Marketplace\Model\ChannelRepository $channelRepository
     * @param \Swissup\Marketplace\Model\Session $session
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Swissup\Marketplace\Model\ChannelRepository $channelRepository,
        \Swissup\Marketplace\Model\Session $session,
        array $data = []
    ) {
        $this->channelRepository = $channelRepository;
        $this->session = $session;
        parent::__construct($context, $data);
    }

    /**
     * @param ChannelInterface $channel
     * @return boolean
     */
    public function isChannelSelected($channel)
    {
        return $this->getCurrentChannelId() === $channel->getIdentifier();
    }

    /**
     * @return ChannelInterface[]
     */
    public function getChannels()
    {
        if ($this->channels === null) {
            $this->channels = $this->channelRepository->getList(true);
        }
        return $this->channels;
    }

    /**
     * @return string
     */
    public function getCurrentChannelId()
    {
        if ($channel = $this->getCurrentChannel()) {
            return $channel->getIdentifier();
        }
        return null;
    }

    /**
     * @return string
     */
    public function getCurrentChannelTitle()
    {
        if ($channel = $this->getCurrentChannel()) {
            return $channel->getTitle();
        }
        return null;
    }

    /**
     * @param ChannelInterface $channel
     * @return string
     */
    public function getSwitchChannelUrl($channel)
    {
        return $this->getUrl('*/channel/change', [
            'channel' => $channel->getIdentifier(),
        ]);
    }

    /**
     * @return ChannelInterface|null
     */
    public function getCurrentChannel()
    {
        if ($this->channel) {
            return $this->channel;
        }

        try {
            $this->channel = $this->channelRepository->getById(
                $this->session->getChannelId(),
                true
            );
        } catch (\Exception $e) {
            // no channels found
        }

        return $this->channel;
    }

    /**
     * @inheritdoc
     */
    public function toHtml()
    {
        if (count($this->getChannels()) < 2) {
            return '';
        }
        return parent::toHtml();
    }
}
