<?php

namespace Swissup\Marketplace\Model\PackagesList;

class Remote extends AbstractList
{
    /**
     * @var \Swissup\Marketplace\Model\ChannelRepository
     */
    private $channelRepository;

    /**
     * @var string
     */
    private $channelId;

    /**
     * @param \Swissup\Marketplace\Model\ChannelRepository $channelRepository
     */
    public function __construct(
        \Swissup\Marketplace\Model\ChannelRepository $channelRepository
    ) {
        $this->channelRepository = $channelRepository;
    }

    /**
     * @param mixed $id
     * @return $this
     */
    public function setChannelId($id)
    {
        $this->channelId = $id;

        return $this;
    }

    /**
     * @param string|null $id
     * @return $this
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @return array
     */
    public function getList()
    {
        if ($this->isLoaded()) {
            return $this->data;
        }

        $channels = [];

        if ($this->getChannelId()) {
            try {
                $channels = [$this->channelRepository->getById($this->getChannelId())];
            } catch (\Exception $e) {
                //
            }
        }

        if (!$channels) {
            $channels = $this->channelRepository->getList(true);
        }

        foreach ($channels as $channel) {
            try {
                foreach ($channel->getPackages() as $id => $packageData) {
                    // @todo: add channel counter, show latest version from all channels

                    $versions = array_keys($packageData);
                    $latestVersion = array_reduce($versions, function ($carry, $item) {
                        if (version_compare($carry, $item) === -1) {
                            $carry = $item;
                        }
                        return $carry;
                    });

                    $this->data[$id] = $this->extractPackageData($packageData[$latestVersion]);
                    $this->data[$id]['channel'] = $channel->getIdentifier();
                    $this->data[$id]['uniqid'] = $channel->getIdentifier() . ':' . $id;
                    foreach ($packageData as $version => $data) {
                        $this->data[$id]['versions'][$version] = $this->extractPackageData($data);
                    }
                }
            } catch (\Exception $e) {
                //
            }
        }

        $this->isLoaded(true);

        return $this->data;
    }
}
