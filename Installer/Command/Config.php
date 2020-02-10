<?php

namespace Swissup\Marketplace\Installer\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Swissup\Marketplace\Installer\Request;
use Swissup\Marketplace\Model\Traits\LoggerAware;

class Config
{
    use LoggerAware;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    private $configWriter;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function execute(Request $request)
    {
        $this->logger->info('Config: Update store parameters');

        foreach ($request->getStoreIds() as $storeId) {
            foreach ($request->getParams() as $path => $value) {
                if (is_array($value)) {
                    $this->processArray($path, $value, $storeId);
                } else {
                    $this->processScalar($path, $value, $storeId);
                }
            }
        }
    }

    /**
     * @param string $path
     * @param string $value
     * @param int $storeId
     * @return void
     */
    private function processScalar($path, $value, $storeId)
    {
        if (!$storeId) {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        } else {
            $scope = ScopeInterface::SCOPE_STORES;
        }

        $this->configWriter->save($path, $value, $scope, $storeId);
    }

    /**
     * @param array $path
     * @param array $data
     * @param int $storeId
     * @return void
     */
    private function processArray($path, $data, $storeId)
    {
        if (!$storeId) {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
        }

        $remote  = $data['path'] ?? $path;
        $search  = $data['search'] ?? $data['remove'];
        $replace = $data['replace'] ?? '';
        $value   = $this->scopeConfig->getValue($remote, $scope, $storeId);

        if (!is_array($search)) {
            $search = [$search];
        }

        foreach ($search as $i => $string) {
            $value = str_replace(
                $string,
                is_array($replace) ? ($replace[$i] ?? '') : $replace,
                $value
            );
        }

        $this->processScalar($path, $value, $storeId);
    }
}
