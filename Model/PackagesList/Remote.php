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
                $packages = $channel->getPackages();
                foreach ($packages as $id => $packageData) {
                    if (!isset($this->data[$id]['channels'])) {
                        $this->data[$id]['channels'] = [];
                    }
                    $this->data[$id]['channels'][] = $channel->getIdentifier();

                    $latestVersion = $this->getLatestVersion($packageData);

                    if (isset($this->data[$id]['version']) &&
                        version_compare($this->data[$id]['version'], $latestVersion, '>=')
                    ) {
                        // this channel has older version in the list - skip it
                        continue;
                    }

                    $this->data[$id] = array_replace(
                        $this->data[$id],
                        $this->extractPackageData($packageData[$latestVersion])
                    );

                    // try to read marketplace data from 'module-' prefixed package
                    if (empty($this->data[$id]['marketplace']) &&
                        strpos($id, 'module-') === false
                    ) {
                        $moduleName = str_replace('/', '/module-', $id);
                        if (isset($packages[$moduleName]['dev-master']['extra'])) {
                            $data = $this->extractPackageData($packages[$moduleName]['dev-master']);
                            $this->data[$id]['marketplace'] = $data['marketplace'];
                        }
                    }

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

    /**
     * @param array $data
     * @return string
     */
    private function getLatestVersion($data)
    {
        return array_reduce(array_keys($data), function ($carry, $item) {
            if (version_compare($carry, $item) === -1) {
                $carry = $item;
            }
            return $carry;
        });
    }
}
