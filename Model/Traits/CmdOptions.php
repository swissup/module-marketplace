<?php

namespace Swissup\Marketplace\Model\Traits;

trait CmdOptions
{
    protected static $cmdOptions = [];

    protected $activeCmdOptions = [];

    public static function getAvailableCmdOptions()
    {
        return static::$cmdOptions;
    }

    public function getCmdOptions()
    {
        return $this->activeCmdOptions;
    }

    public function hasCmdOption($option)
    {
        return in_array($option, $this->activeCmdOptions);
    }

    public function setCmdOptions($options)
    {
        $this->activeCmdOptions = $options;
        return $this;
    }
}
