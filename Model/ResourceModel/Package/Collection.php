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

    /**
     * Order configuration
     *
     * @var array
     */
    protected $_orders = [
        'time' => self::SORT_ORDER_DESC
    ];

    /**
     * Array of packages received from remote server.
     *
     * @var array
     */
    private $data = [];

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
     * @return array
     */
    private function getRemoteUrls()
    {
        return [
            'https://swissup.github.io/packages/packages.json',
            // 'https://ci.swissuplabs.com/api/packages.json',
        ];
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
            $packages = $this->fetchPackages($remoteUrl);

            if (!$packages) {
                continue;
            }

            foreach ($packages as $id => $packageData) {
                $versions = array_keys($packageData);
                $latestVersion = array_reduce($versions, function ($carry, $item) {
                    if (version_compare($carry, $item) === -1) {
                        $carry = $item;
                    }
                    return $carry;
                });

                $this->data[$id] = $packageData[$latestVersion];
                $this->data[$id]['versions'] = $versions;
                $this->data[$id]['image_src'] = 'https://swissuplabs.com/media/catalog/product/cache/1/image/512x512/9df78eab33525d08d6e5fb8d27136e95/b/o/box.v3.navigation_2_1.png';

                if (!empty($packageData['dev-master']['extra']['marketplace']['gallery'][0])) {
                    $this->data[$id]['image_src'] = $packageData['dev-master']['extra']['marketplace']['gallery'][0];
                }
            }
        }

        if (!empty($this->_orders)) {
            usort($this->data, [$this, '_usort']);
        }

        foreach ($this->data as $values) {
            $item = $this->getNewEmptyItem();
            $item->setData($values);
            $item->setId($values['name']);

            $this->addItem($item);
        }

        $this->_setIsLoaded(true);

        return $this;
    }

    /**
     * Callback for sorting items. Supports sorting by one column only.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function _usort($a, $b)
    {
        foreach ($this->_orders as $key => $direction) {
            $result = $a[$key] > $b[$key] ? 1 : ($a[$key] < $b[$key] ? -1 : 0);
            return self::SORT_ORDER_ASC === strtoupper($direction) ? $result : -$result;
        }
    }

    /**
     * Fetch packages from remote server.
     *
     * @param string $url
     * @return array|false
     */
    protected function fetchPackages($url)
    {
        $response = [];

        try {
            $response = $this->fetch($url);
            $response = $this->jsonHelper->jsonDecode($response);
        } catch (\Exception $e) {
            return false;
        }

        if (!is_array($response)) {
            return false;
        }

        if (isset($response['includes'])) {
            $url = substr($url, 0, strrpos($url, '/') + 1);

            try {
                $response = $this->fetch($url . key($response['includes']));
                $response = $this->jsonHelper->jsonDecode($response);
            } catch (\Exception $e) {
                return false;
            }

            if (!is_array($response)) {
                return false;
            }
        }

        return $response['packages'] ?? false;
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
}
