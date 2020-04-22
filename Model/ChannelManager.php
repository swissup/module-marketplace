<?php

namespace Swissup\Marketplace\Model;

use Swissup\Marketplace\Api\ChannelInterface;

class ChannelManager
{
    /**
     * @var \Swissup\Marketplace\Model\ComposerApplication
     */
    protected $composer;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializer;

    /**
     * @param \Swissup\Marketplace\Model\ComposerApplication $appFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     */
    public function __construct(
        \Swissup\Marketplace\Model\ComposerApplication $composer,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
    ) {
        $this->composer = $composer;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param ChannelInterface $channel
     * @return string
     */
    public function enable($channel)
    {
        return $this->composer->run([
            'command' => 'config',
            'setting-key' => 'repositories.' . $channel->getIdentifier(),
            'setting-value' => [
                $channel->getType(),
                $channel->getUrl(),
            ],
        ]);
    }

    /**
     * @param ChannelInterface $channel
     * @return string
     */
    public function disable($channel)
    {
        $id = $channel->getIdentifier();

        // fixed channel disabling when the key in composer.json is not equal with id.
        foreach ($this->getEnabledChannels() as $key => $data) {
            if ($data['url'] === $channel->getUrl()) {
                $id = $key;
                break;
            }
        }

        return $this->composer->run([
            'command' => 'config',
            '--unset' => true,
            'setting-key' => 'repositories.' . $id,
        ]);
    }

    /**
     * @param ChannelInterface $channel
     * @return string
     */
    public function saveCredentials($channel)
    {
        return $this->composer->runAuthCommand([
            'setting-key' => $channel->getAuthType() . '.' . $channel->getHostname(),
            'setting-value' => $channel->getAuthSettingValue(),
        ]);
    }

    /**
     * @param ChannelInterface $channel
     * @return array
     */
    public function getCredentials($channel)
    {
        try {
            $string = $this->composer->runAuthCommand([
                'setting-key' => $channel->getAuthType(), // don't use concat with hostname to fix error when domain level > 3
            ]);
            $data = $this->jsonSerializer->unserialize($string);

            return $data[$channel->getHostname()] ?? [];
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'http-basic is not defined') !== false) {
                return [];
            }
            throw new \RuntimeException(
                "'composer config -a -g' command failed: " . $e->getMessage()
            );
        }
    }

    /**
     * @param ChannelInterface $channel
     * @return boolean
     */
    public function isEnabled($channel)
    {
        foreach ($this->getEnabledChannels() as $data) {
            if ($data['url'] === $channel->getUrl()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getEnabledChannels()
    {
        try {
            $channels = $this->composer->run([
                'command' => 'config',
                'setting-key' => 'repositories',
                '-q' => true,
            ]);
            $channels = $this->jsonSerializer->unserialize($channels);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "'composer config' command failed: " . $e->getMessage()
            );
        }

        return $channels;
    }
}
