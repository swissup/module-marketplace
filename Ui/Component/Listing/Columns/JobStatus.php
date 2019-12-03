<?php

namespace Swissup\Marketplace\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Swissup\Marketplace\Model\JobFactory;

class JobStatus extends Column
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param JobFactory $jobFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        JobFactory $jobFactory,
        array $components = [],
        array $data = []
    ) {
        $this->jobFactory = $jobFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $statuses = $this->jobFactory->create()->getAvailableStatuses();

        foreach ($dataSource['data']['items'] as &$item) {
            $statusCode = $item[$this->getData('name')];

            $classes = [
                'grid-severity-notice',
                'marketplace-job-status-' . $statusCode,
                empty($item['output']) ? '' : 'job-with-output',
            ];

            $item[$this->getData('name')] =
                '<span class="' . implode(' ', $classes) . '">'
                . $statuses[$statusCode] ?? 'Unknown'
                . '</span>';
        }

        return $dataSource;
    }
}
