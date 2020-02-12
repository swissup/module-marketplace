<?php

namespace Swissup\Marketplace\Installer\Helper;

class Serializer
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
    ) {
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param array $request
     * @param array $value
     * @return string
     */
    public function serialize(array $request, $value)
    {
        return $this->jsonSerializer->serialize($value);
    }
}
