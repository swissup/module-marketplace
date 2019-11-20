<?php

namespace Swissup\Marketplace\Model\Channel;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\RuntimeException;

/**
 * Composer repository type implementation.
 *
 * @see https://getcomposer.org/doc/05-repositories.md#composer
 */
class Composer implements \Swissup\Marketplace\Api\ChannelInterface
{
    /**
     * @var array
     */
    protected $data = [];

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
     * @param \Swissup\Marketplace\Model\ChannelManager $channelManager
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
        \Swissup\Marketplace\Model\ChannelManager $channelManager,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        array $data = []
    ) {
        $this->channelManager = $channelManager;
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
     * @return $this
     */
    public function save()
    {
        $enableFlag = $this->getData('enabled');

        if ($enableFlag !== null) {
            if (!$this->isEnabled() && $enableFlag) {
                $this->channelManager->enable($this);
            } elseif ($this->isEnabled() && !$enableFlag) {
                $this->channelManager->disable($this);
            }
        }

        if ($this->getAuthType() && $this->getAuthSettingValue()) {
            $this->channelManager->saveCredentials($this);
        }

        return $this;
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
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getData($key, $default = null)
    {
        return $this->data[$key] ?? $default;
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
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->channelManager->isEnabled($this);
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        if (isset($this->data['username'])) {
            return $this->data['username'];
        }

        if (!$this->getAuthType()) {
            return '';
        }

        $data = $this->channelManager->getCredentials($this);

        return $data['username'] ?? '';
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        if (isset($this->data['password'])) {
            return $this->data['password'];
        }

        if (!$this->getAuthType()) {
            return '';
        }

        $data = $this->channelManager->getCredentials($this);

        return $data['password'] ?? '';
    }

    /**
     * @return array
     */
    public function getAuthSettingValue()
    {
        return [
            $this->getUsername(),
            $this->getPassword(),
        ];
    }

    /**
     * Get packages from remote server.
     *
     * @return array
     * @throws AuthenticationException
     * @throws RuntimeException
     * @throws \Zend_Http_Client_Exception
     */
    public function getPackages()
    {
        $response = $this->loadCache();
        if ($response) {
            return $response;
        }

        $response = $this->fetch($this->getUrl('packages.json'));

        if (isset($response['includes'])) {
            $response = $this->fetch($this->getUrl(key($response['includes'])));
        }

        if (isset($response['packages'])) {
            $this->saveCache($response['packages']);
        }

        return $response['packages'] ?? [];
    }

    /**
     * @param string $url
     * @return string
     * @throws \Zend_Http_Client_Exception
     */
    protected function fetch($url, $parseResponse = true)
    {
        $response = $this->getHttpClient()->setUri($url)->request();

        $this->validateResponse($response);

        $body = $response->getBody();

        return $parseResponse ? $this->parseResponseText($body) : $body;
    }

    /**
     * @param Zend_Http_Response $response
     * @return boolean
     * @throws AuthenticationException
     * @throws NotFoundException
     * @throws RuntimeException
     */
    protected function validateResponse($response)
    {
        switch ($response->getStatus()) {
            case 200:
                return true;
            case 401:
                throw new AuthenticationException(
                    __('An authentication error occurred. Verify your credentials and try again.')
                );
            case 404:
                throw new NotFoundException(
                    __('Remote channel returned "404 - Not Found" response.')
                );
        }

        throw new RuntimeException(__(
            'An error occured. Response code - %1. Response message - %2',
            $response->getStatus(),
            $response->getMessage()
        ));
    }

    /**
     * @param string $string
     * @return array
     * @throws RuntimeException
     */
    protected function parseResponseText($string)
    {
        try {
            return $this->jsonSerializer->unserialize($string);
        } catch (\Exception $e) {
            throw new RuntimeException(
                __('Remote channel returned malformed response.')
            );
        }
    }

    /**
     * @return \Magento\Framework\HTTP\ZendClient
     */
    protected function getHttpClient()
    {
        $client = $this->httpClientFactory->create()
            ->setConfig([
                'maxredirects' => 5,
                'timeout' => 30,
            ]);

        if ($this->getAuthType()) {
            $client->setAuth($this->getUsername(), $this->getPassword());
        }

        return $client;
    }

    /**
     * @return boolean
     */
    protected function isCacheable()
    {
        return !empty($this->data['cacheable']);
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
     * @return $this
     */
    public function removeCache()
    {
        $this->cache->remove($this->getCacheKey());

        return $this;
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
