<?php

namespace Swissup\Marketplace\Model\Handler\Additional;

class MaintenanceDisable extends MaintenanceEnable
{
    protected $status = false;

    public function getTitle()
    {
        return __('Disable Maintenance Mode');
    }
}
