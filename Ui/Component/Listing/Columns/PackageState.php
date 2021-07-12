<?php

namespace Swissup\Marketplace\Ui\Component\Listing\Columns;

use Magento\Framework\Escaper;
use Magento\Framework\Data\OptionSourceInterface;

class PackageState implements OptionSourceInterface
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
                'label' => $this->escaper->escapeHtml(__('Any')),
                'value' => '',
            ],
            [
                'label' => $this->escaper->escapeHtml(__('Enabled')),
                'value' => 'enabled',
            ],
            [
                'label' => $this->escaper->escapeHtml(__('Disabled')),
                'value' => 'disabled',
            ],
            [
                'label' => $this->escaper->escapeHtml(__('Installed')),
                'value' => '!na',
            ],
            [
                'label' => $this->escaper->escapeHtml(__('Not Installed')),
                'value' => 'na',
            ],
            [
                'label' => $this->escaper->escapeHtml(__('Outdated')),
                'value' => 'outdated',
            ],
        ];
    }
}
