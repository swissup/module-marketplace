<?php

namespace Swissup\Marketplace\Ui\Component\Listing\Columns;

class Links extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $item[$this->getData('name')] = [
                'details' => [
                    'href' => $this->getContext()->getUrl(
                        Actions::URL_PATH_DETAILS,
                        [
                            'name' => $item['name']
                        ]
                    ),
                    'label' => __('View Details')
                ],
            ];

            foreach ($this->getData('links') as $link) {
                if (empty($item['remote']['marketplace']['links'][$link['key']])) {
                    continue;
                }

                $item[$this->getData('name')][$link['key']] = [
                    'href'  => $item['remote']['marketplace']['links'][$link['key']],
                    'label' => __($link['label']),
                    'target' => '_blank',
                ];
            }
        }

        return $dataSource;
    }
}
