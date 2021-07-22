<?php

namespace Swissup\Marketplace\Model;

class ChannelFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array $data
     * @return \Swissup\Marketplace\Api\ChannelInterface
     */
    public function create($data)
    {
        switch ($data['type']) {
            case 'composer':
                $class = \Swissup\Marketplace\Model\Channel\Composer::class;
                $arguments = [
                    'url' => $data['url'],
                    'title' => $data['title'] ?? $this->generateIdentifier($data['url']),
                    'identifier' => $data['identifier'] ?? $this->generateIdentifier($data['url']),
                    'hostname' => $data['hostname'] ?? $this->parseHostname($data['url']),
                    'data' => $data,
                ];
                break;
            default:
                throw new \Exception('Channel type is not supported');
        }

        return $this->objectManager->create($class, $arguments);
    }

    private function generateIdentifier($url)
    {
        return str_replace(['www.', '.'], ['', '_'], $this->parseHostname($url));
    }

    private function parseHostname($url)
    {
        $hostname = parse_url($url, PHP_URL_HOST);

        if (!$hostname) {
            throw new \Exception('Unable to parse channel URL');
        }

        return $hostname;
    }
}
