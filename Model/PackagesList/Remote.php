<?php

namespace Swissup\Marketplace\Model\PackagesList;

class Remote extends AbstractList
{
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Swissup\Marketplace\Model\ChannelRepository $channelRepository
    ) {
        $this->request = $request;
        $this->channelRepository = $channelRepository;
    }

    /**
     * @return array
     */
    public function getList()
    {
        if ($this->isLoaded()) {
            return $this->data;
        }

        foreach ($this->channelRepository->getList() as $channel) {
            if (!$channel->isEnabled()) {
                continue;
            }

            foreach ($channel->getPackages() as $id => $packageData) {
                $versions = array_keys($packageData);
                $latestVersion = array_reduce($versions, function ($carry, $item) {
                    if (version_compare($carry, $item) === -1) {
                        $carry = $item;
                    }
                    return $carry;
                });

                $this->data[$id] = $this->extractPackageData($packageData[$latestVersion]);
                foreach ($packageData as $version => $data) {
                    $this->data[$id]['versions'][$version] = $this->extractPackageData($data);
                }
            }
        }

        $this->isLoaded(true);

        return $this->data;
    }
}
