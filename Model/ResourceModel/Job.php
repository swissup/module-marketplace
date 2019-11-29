<?php

namespace Swissup\Marketplace\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Job extends AbstractDb
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('swissup_marketplace_job', 'job_id');
    }
}
