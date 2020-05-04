<?php

namespace Swissup\Marketplace\Plugin;

use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;

class CspPolicyCollector
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->request = $request;
    }

    /**
     * @param DynamicCollector $subject
     * @param array $result
     * @return array
     */
    public function afterCollect(
        DynamicCollector $subject,
        array $result = []
    ) {
        $page = $this->request->getFullActionName();

        if ($page === 'swissup_marketplace_package_index') {
            $result[] = new FetchPolicy('img-src', false, ['docs.swissuplabs.com']);
        }

        return $result;
    }
}
