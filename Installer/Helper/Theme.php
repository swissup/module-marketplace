<?php

namespace Swissup\Marketplace\Installer\Helper;

use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory;

class Theme
{
    /**
     * @var array
     */
    private $memo = [];

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param array $request
     * @param string $path
     * @return int
     */
    public function getId(array $request, $path)
    {
        if (!isset($this->memo[$path])) {
            $this->memo[$path] = $this->collectionFactory->create()
                ->getThemeByFullPath($path)
                ->getThemeId();
        }
        return $this->memo[$path];
    }
}
