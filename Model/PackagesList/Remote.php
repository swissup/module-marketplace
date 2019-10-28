<?php

namespace Swissup\Marketplace\Model\PackagesList;

class Remote extends AbstractList
{
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
    ) {
        $this->request = $request;
        $this->jsonHelper = $jsonHelper;
        $this->httpClientFactory = $httpClientFactory;
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
     * @return array
     */
    public function getList()
    {
        if ($this->isLoaded()) {
            return $this->data;
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

                $this->data[$id] = $this->extractPackageData($packageData[$latestVersion]);
                foreach ($packageData as $version => $data) {
                    $this->data[$id]['versions'][$version] = $this->extractPackageData($data);
                }
            }
        }

        $this->isLoaded(true);

        return $this->data;
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
}
