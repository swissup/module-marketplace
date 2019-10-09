<?php

namespace Swissup\Marketplace\Model\ResourceModel\Package;

use Magento\Framework\Data\Collection\EntityFactoryInterface;

class Collection extends \Magento\Framework\Data\Collection
{
    /**
     * Item object class name
     *
     * @var string
     */
    protected $_itemObjectClass = \Swissup\Marketplace\Model\Package::class;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
    ) {
        $this->request = $request;
        $this->jsonHelper = $jsonHelper;
        $this->httpClientFactory = $httpClientFactory;

        return parent::__construct($entityFactory);
    }

    /**
     * Load data
     *
     * @return $this
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }

        foreach ($this->getRemoteUrls() as $remoteUrl) {
            try {
                $response = $this->fetch($remoteUrl);
                $response = $this->jsonHelper->jsonDecode($response);
            } catch (\Exception $e) {
                continue;
            }

            if (!is_array($response)) {
                continue;
            }

            if (isset($response['includes'])) {
                $remoteUrl = substr($remoteUrl, 0, strrpos($remoteUrl, '/') + 1);
                $response = $this->fetch($remoteUrl . key($response['includes']));
                $response = $this->jsonHelper->jsonDecode($response);

                if (!is_array($response)) {
                    continue;
                }
            }

            if (!isset($response['packages'])) {
                continue;
            }

            foreach ($response['packages'] as $packageName => $packageVersions) {
                $versions = array_keys($packageVersions);
                $latestVersion = array_reduce($versions, function ($carry, $item) {
                    if (version_compare($carry, $item) === -1) {
                        $carry = $item;
                    }
                    return $carry;
                });

                $item = $this->getNewEmptyItem();
                $item->setData($packageVersions[$latestVersion]);
                $item->setId($packageName);
                $item->setVersions($versions);
                $item->setUpdatedAt($packageVersions[$latestVersion]['time']);

                $this->addItem($item);
            }
        }

        $this->_setIsLoaded(true);

        return $this;
    }

    /**
     * @param  string $url
     * @return string
     */
    protected function fetch($url)
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($url);
        $client->setConfig([
            'maxredirects' => 5,
            'timeout' => 30
        ]);
        $client->setParameterGet('domain', $this->request->getHttpHost());
        return $client->request()->getBody();
    }

    /**
     * Compatibility with Ui/DataProvider
     *
     * @param string $field
     * @param string $direction
     */
    public function addOrder($field, $direction)
    {
        return $this->setOrder($field, $direction);
    }

    private function getRemoteUrls()
    {
        return [
            'https://swissup.github.io/packages/packages.json',
            // 'https://ci.swissuplabs.com/api/packages.json',
        ];
    }
}
