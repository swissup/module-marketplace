<?php

namespace Swissup\Marketplace\Model\Channel;

class AbstractChannel
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var \Swissup\Marketplace\Model\Composer
     */
    protected $composer;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var string
     */
    protected $authType = '';

    /**
     * @var string
     */
    protected $type = 'composer';

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializer;

    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @param string $url
     * @param string $title
     * @param string $identifier
     * @param string $hostname
     * @param \Swissup\Marketplace\Model\Composer $composer
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
     * @param array $data[optional]
     */
    public function __construct(
        $url,
        $title,
        $identifier,
        $hostname,
        \Swissup\Marketplace\Model\Composer $composer,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        array $data = []
    ) {
        $this->composer = $composer;
        $this->cache = $cache;
        $this->scopeConfig = $scopeConfig;
        $this->jsonSerializer = $jsonSerializer;
        $this->httpClientFactory = $httpClientFactory;
        $this->data = array_merge(
            $this->getDefaultData(),
            $data,
            [
                'url' => $url,
                'title' => $title,
                'identifier' => $identifier,
                'hostname' => $hostname,
                'cacheable' => true,
            ]
        );
    }

    /**
     * @return array
     */
    protected function getDefaultData()
    {
        return [
            'authType' => $this->authType,
            'type' => $this->type,
        ];
    }

    /**
     * @param array $data
     */
    public function addData(array $data)
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * Returns true, when channel is found in composer.json repos.
     *
     * @return boolean
     */
    public function isEnabled()
    {
        foreach ($this->composer->getChannels() as $channel) {
            if ($channel['url'] === $this->getUrl()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->data['title'];
    }

    /**
     * @param string $suffix
     * @return string
     */
    public function getUrl($suffix = null)
    {
        $url = $this->data['url'];

        if ($suffix && strpos($url, $suffix) === false) {
            $url = rtrim($url, '/');
            $suffix = ltrim($suffix, '/');
            $url = $url . '/' . $suffix;
        }

        return $url;
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->data['hostname'];
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->data['identifier'];
    }

    /**
     * @return string
     */
    public function getAuthType()
    {
        return $this->data['authType'];
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->data['type'];
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return '';
    }

    /**
     * @return array
     */
    public function getAuthJsonCredentials()
    {
        return [];
    }

    /**
     * @return $this
     */
    public function save()
    {
        $this->composer->updateChannel($this);

        return $this;
    }

    /**
     * Get packages from remote server.
     *
     * @return array
     */
    public function getPackages()
    {
        $response = $this->loadCache();
        if ($response) {
            return $response;
        }

        try {
            $response = $this->fetch($this->getUrl('packages.json'));
            $response = $this->jsonSerializer->unserialize($response);
        } catch (\Exception $e) {
            return [];
        }

        if (!is_array($response)) {
            return [];
        }

        if (isset($response['includes'])) {
            try {
                $response = $this->fetch($this->getUrl(key($response['includes'])));
                $response = $this->jsonSerializer->unserialize($response);
            } catch (\Exception $e) {
                return [];
            }

            if (!is_array($response)) {
                return [];
            }
        }

        if (isset($response['packages'])) {
            $this->saveCache($response['packages']);
        }

        return $response['packages'] ?? [];
    }

    /**
     * @param string $url
     * @return string
     */
    protected function fetch($url)
    {
        return $this->getHttpClient()->setUri($url)->request()->getBody();
    }

    /**
     * @return \Magento\Framework\HTTP\ZendClient
     */
    protected function getHttpClient()
    {
        return $this->httpClientFactory->create()
            ->setConfig([
                'maxredirects' => 5,
                'timeout' => 30,
            ]);
    }

    /**
     * @return boolean
     */
    protected function isCacheable()
    {
        return (bool) $this->data['cacheable'];
    }

    /**
     * @return string
     */
    protected function getCacheKey()
    {
        return sha1($this->getUrl() . $this->getUsername() . $this->getPassword());
    }

    /**
     * @return array
     */
    protected function loadCache()
    {
        if (!$this->isCacheable()) {
            return false;
        }

        if (!$cached = $this->cache->load($this->getCacheKey())) {
            return false;
        }

        return $this->unserialize($cached);
    }

    /**
     * @param array $data
     * @return void
     */
    protected function saveCache($data)
    {
        if (!$this->isCacheable() || !$data) {
            return false;
        }

        if ($cached = $this->serialize($data)) {
            $this->cache->save(
                $cached,
                $this->getCacheKey(),
                [],
                60 * 10
            );
        }
    }

    /**
     * @param  array $data
     * @return string
     */
    protected function serialize($data)
    {
        return $this->jsonSerializer->serialize($data);
    }

    /**
     * @param  string $data
     * @return array
     */
    protected function unserialize($data)
    {
        return $this->jsonSerializer->unserialize($data);
    }
}
