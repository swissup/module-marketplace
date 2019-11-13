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
     * @param string $url
     * @param string $title
     * @param string $identifier
     * @param string $hostname
     * @param \Swissup\Marketplace\Model\Composer $composer
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param array $data[optional]
     */
    public function __construct(
        $url,
        $title,
        $identifier,
        $hostname,
        \Swissup\Marketplace\Model\Composer $composer,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->composer = $composer;
        $this->scopeConfig = $scopeConfig;
        $this->data = array_merge(
            $this->getDefaultData(),
            $data,
            [
                'url' => $url,
                'title' => $title,
                'identifier' => $identifier,
                'hostname' => $hostname,
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
     * @return string
     */
    public function getUrl()
    {
        return $this->data['url'];
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
}
