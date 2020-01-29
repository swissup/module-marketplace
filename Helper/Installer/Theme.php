<?php

namespace Swissup\Marketplace\Helper\Installer;

use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory;

class Theme
{
    /**
     * @var array
     */
    private $memo = [];

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param string $path
     * @return int
     */
    public function getId($path)
    {
        if (!isset($this->memo[$path])) {
            $this->memo[$path] = $this->collectionFactory->create()
                ->getThemeByFullPath($path)
                ->getThemeId();
        }
        return $this->memo[$path];
    }
}
