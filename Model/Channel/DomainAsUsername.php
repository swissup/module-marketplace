<?php

namespace Swissup\Marketplace\Model\Channel;

class DomainAsUsername extends Composer
{
    /**
     * @return string
     */
    public function getUsername()
    {
        $url = $this->scopeConfig->getValue('web/unsecure/base_url');

        return parse_url($url, PHP_URL_HOST);
    }
}
