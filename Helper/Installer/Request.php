<?php

namespace Swissup\Marketplace\Helper\Installer;

class Request
{
    public function getData(array $request, $key, $default = null)
    {
        return $request[$key] ?? $default;
    }
}
