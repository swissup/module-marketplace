<?php

namespace Swissup\Marketplace\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Swissup\Marketplace\Model\Job;

class Data extends AbstractHelper
{
    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    private $deployment;

    /**
     * @param Context $context
     * @param \Magento\Framework\App\DeploymentConfig $deployment
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\DeploymentConfig $deployment
    ) {
        $this->deployment = $deployment;

        parent::__construct($context);
    }

    /**
     * @param array $params
     * @return string
     */
    public function generateJobSignature(Job $job)
    {
        return $this->generateSignature([
            $job->getClass(),
            $job->getArgumentsSerialized(),
            $job->getCreatedAt(),
        ]);
    }

    /**
     * @param array $params
     * @return string
     */
    public function generateSignature(array $params)
    {
        return hash_hmac(
            'sha256',
            implode(',', $params),
            (string) $this->deployment->get(ConfigOptionsListConstants::CONFIG_PATH_CRYPT_KEY)
        );
    }
}
