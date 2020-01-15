<?php

namespace Swissup\Marketplace\Ui\Component\Listing\Columns;

use Magento\Framework\Escaper;
use Magento\Framework\Data\OptionSourceInterface;

class PackageState implements OptionSourceInterface
{
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
                'label' => $this->escaper->escapeHtml(__('All')),
                'value' => '',
            ],
            [
                'label' => $this->escaper->escapeHtml(__('Outdated')),
                'value' => 'outdated',
            ],
            [
                'label' => $this->escaper->escapeHtml(__('Not Installed')),
                'value' => 'na',
            ],
        ];
    }
}
