<?php

namespace Swissup\Marketplace\Installer\Helper;

class Request
{
    public function getData(array $request, $key, $default = null)
    {
        return $request[$key] ?? $default;
    }
}
