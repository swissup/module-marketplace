<?php

namespace Swissup\Marketplace\Model\Channel;

class DomainAndKeyAuth extends HttpBasicAuth
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
