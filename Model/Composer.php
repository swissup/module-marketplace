<?php

namespace Swissup\Marketplace\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Swissup\Marketplace\Model\Channel\AbstractChannel;

class Composer
{
    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @var array
     */
    private $channels;

    /**
     * @var array
     */
    private $authData;

    /**
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
    ) {
        $this->filesystem = $filesystem;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @return array
     */
    public function getChannels()
    {
        if ($this->channels === null) {
            $directory = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);
            $data = $this->parseJsonFile($directory, 'composer.json');
            $this->channels = $data['repositories'] ?? [];
        }
        return $this->channels;
    }

    /**
     * @param AbstractChannel $channel
     */
    public function updateChannel(AbstractChannel $channel)
    {
        if ($channel->getAuthType()) {
            $this->addAuthCredentials($channel);
        }
    }

    /**
     * Add channel credentials to the auth.json file
     *
     * @param AbstractChannel $channel
     */
    private function addAuthCredentials(AbstractChannel $channel)
    {
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::COMPOSER_HOME);
        $filename = 'auth.json';

        if (!$directory->isExist($filename)) {
            try {
                $directory->writeFile($filename, '{}');
            } catch (\Exception $e) {
                throw new LocalizedException(__(
                    'Error in writing file %1. Please check permissions for writing.',
                    $directory->getAbsolutePath($filename)
                ));
            }
        }

        $authData = array_replace_recursive(
            $this->getAuthData(),
            [
                $channel->getAuthType() => [
                    $channel->getHostname() => $channel->getAuthJsonCredentials()
                ]
            ]
        );

        $directory->writeFile($filename, $this->jsonSerializer->serialize($authData));

        $this->authData = $authData;
    }

    /**
     * @param AbstractChannel|null $channel
     * @return array
     */
    public function getAuthData(AbstractChannel $channel = null)
    {
        if ($this->authData === null) {
            $directory = $this->filesystem->getDirectoryRead(DirectoryList::COMPOSER_HOME);
            $this->authData = $this->parseJsonFile($directory, 'auth.json');
        }

        if (!$channel) {
            return $this->authData;
        }

        $type = $channel->getAuthType();
        $host = $channel->getHostname();

        return $this->authData[$type][$host] ?? [];
    }

    /**
     * @param mixed $directory
     * @param string $filename
     * @return array
     */
    private function parseJsonFile($directory, $filename)
    {
        try {
            $data = $directory->readFile($filename);
            $data = $this->jsonSerializer->unserialize($data);
        } catch (\Exception $e) {
            $data = [];
        }
        return $data;
    }
}
