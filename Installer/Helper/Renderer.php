<?php

namespace Swissup\Marketplace\Installer\Helper;

use Magento\Framework\Exception\FileSystemException;

class Renderer
{
    /**
     * @param array $request
     * @param string $path
     * @return string
     */
    public function render(array $request, $path)
    {
        if (!is_readable($path)) {
            throw new FileSystemException(__(
                'File %1 can\'t be read. Please check if it exists and has read permissions.',
                [
                    $path
                ]
            ));
        }

        return file_get_contents($path);
    }
}
