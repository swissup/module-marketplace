<?php

namespace Swissup\Marketplace\Model;

class HandlerValidationException extends \Magento\Framework\Exception\ValidatorException
{
    private $data = [];

    public function getData()
    {
        return array_filter($this->data);
    }

    public function setData($data)
    {
        unset($data['message']);

        $this->data = $data;
    }
}
