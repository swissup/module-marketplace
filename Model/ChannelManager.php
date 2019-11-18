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
        return $this->composer->run([
            'command' => 'config',
            '-a' => true,
            '-g' => true,
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
            $string = $this->composer->run([
                'command' => 'config',
                '-a' => true,
                '-g' => true,
                'setting-key' => $channel->getAuthType() . '.' . $channel->getHostname(),
            ]);
            return $this->jsonSerializer->unserialize($string);
        } catch (\Exception $e) {
            return [];
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
        $channels = $this->composer->run([
            'command' => 'config',
            'setting-key' => 'repositories',
        ]);
        return $this->jsonSerializer->unserialize($channels);
    }
}
