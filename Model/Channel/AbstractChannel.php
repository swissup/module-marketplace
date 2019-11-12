<?php

namespace Swissup\Marketplace\Model\Channel;

class AbstractChannel
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $hostname;

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var \Swissup\Marketplace\Model\Composer
     */
    protected $composer;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $authType = ''; // http-basic

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
        $this->url = $url;
        $this->title = $title;
        $this->identifier = $identifier;
        $this->hostname = $hostname;
        $this->composer = $composer;
        $this->scopeConfig = $scopeConfig;
        $this->data = $data;
    }

    public function save()
    {
        $this->composer->updateChannel($this);
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
        return (bool) $this->getDataFromComposerJson('url');
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getAuthType()
    {
        return $this->authType;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getDataFromComposerJson($key = null)
    {
        $result = [];

        foreach ($this->composer->getChannels() as $channel) {
            if ($channel['url'] === $this->getUrl()) {
                $result = $channel;
                break;
            }
        }

        if ($key) {
            return $result[$key] ?? null;
        }

        return $result;
    }
}
