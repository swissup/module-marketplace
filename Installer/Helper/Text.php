<?php

namespace Swissup\Marketplace\Installer\Helper;

class Text
{
    public function sprintf(array $request)
    {
        $args = func_get_args();

        $args = array_slice($args, 1);

        return call_user_func_array('sprintf', $args);
    }
}
