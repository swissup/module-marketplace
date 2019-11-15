<?php

namespace Swissup\Marketplace\Model;

class Session extends \Magento\Backend\Model\Session
{
    const CHANNEL_ID_KEY = 'swissup_marketplace_channel_id';

    /**
     * @param string $id
     */
    public function setChannelId($id)
    {
        return $this->setData(self::CHANNEL_ID_KEY, $id);
    }

    /**
     * @return string
     */
    public function getChannelId()
    {
        return (string) $this->getData(self::CHANNEL_ID_KEY);
    }
}
