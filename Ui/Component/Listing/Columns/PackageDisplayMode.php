<?php

namespace Swissup\Marketplace\Ui\Component\Listing\Columns;

use Magento\Framework\Escaper;
use Magento\Framework\Data\OptionSourceInterface;

class PackageDisplayMode implements OptionSourceInterface
{
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param Escaper $escaper
     */
    public function __construct(
        Escaper $escaper
    ) {
        $this->escaper = $escaper;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => $this->escaper->escapeHtml(__('Bundles')),
                'value' => '',
            ],
            [
                'label' => $this->escaper->escapeHtml(__('Components')),
                'value' => '!metapackage',
            ],
        ];
    }
}
