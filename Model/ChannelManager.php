<?php

namespace Swissup\Marketplace\Model;

use Swissup\Marketplace\Api\ChannelInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

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
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var array
     */
    protected $channels;

    /**
     * @param \Swissup\Marketplace\Model\ComposerApplication $composer
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \Swissup\Marketplace\Model\ComposerApplication $composer,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->composer = $composer;
        $this->jsonSerializer = $jsonSerializer;
        $this->filesystem = $filesystem;
    }

    /**
     * @param ChannelInterface $channel
     * @return string
     */
    public function enable($channel)
    {
        // remove runtime channel from backup file
        if ($channel->isRuntime()) {
            $data = $this->readDisabledRuntimeChannels();
            unset($data[$channel->getIdentifier()]);
            $this->writeDisabledRuntimeChannels($data);
        }

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

        // save runtime channel to backup file to allow to enable it later
        if ($channel->isRuntime()) {
            $data = $this->readDisabledRuntimeChannels();
            $data = array_merge($data, [
                $channel->getIdentifier() => $channel->getComposerRepositoryData(),
            ]);
            $this->writeDisabledRuntimeChannels($data);
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
        if ($this->channels !== null) {
            return $this->channels;
        }

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

        $this->channels = $channels;

        return $channels;
    }

    /**
     * @return array
     */
    public function getAllChannels()
    {
        return array_merge($this->getEnabledChannels(), $this->readDisabledRuntimeChannels());
    }

    /**
     * @return array
     */
    private function readDisabledRuntimeChannels()
    {
        $dir = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        $path = 'swissup/marketplace/repositories.json';

        if (!$dir->isReadable($path)) {
            return [];
        }

        try {
            $file = $dir->openFile($path);
            $data = $file->readAll();
            $data = $this->jsonSerializer->unserialize($data);
        } catch (\Exception $e) {
            $data = [];
        }

        return $data;
    }

    /**
     * @param array $data
     */
    private function writeDisabledRuntimeChannels($data)
    {
        $dir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $file = $dir->openFile('swissup/marketplace/repositories.json');

        $file->flush();
        $file->write($this->jsonSerializer->serialize($data));
    }
}
