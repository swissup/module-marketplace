<?php

namespace Swissup\Marketplace\Model\PackagesList;

class AbstractList
{
    protected $data = [];

    protected $isLoaded = false;

    /**
     * @param array $data
     * @return array
     */
    protected function extractPackageData(array $data)
    {
        $result = array_intersect_key($data, array_flip([
            'name',
            'description',
            'keywords',
            'version',
            'require',
            'time',
            'type',
            'accessible',
        ]));

        $result['marketplace'] = $data['extra']['swissup'] ?? [];
        if (isset($data['extra']['marketplace'])) {
            $result['marketplace'] = $data['extra']['marketplace'];
        }

        return $result;
    }

    public function isLoaded($flag = null)
    {
        if (null !== $flag) {
            $this->isLoaded = $flag;
        }
        return $this->isLoaded;
    }
}
