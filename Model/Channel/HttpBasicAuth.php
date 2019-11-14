<?php

namespace Swissup\Marketplace\Model\Channel;

class HttpBasicAuth extends AbstractChannel
{
    /**
     * @var string
     */
    protected $authType = 'http-basic';

    /**
     * @return string
     */
    public function getUsername()
    {
        if (isset($this->data['username'])) {
            return $this->data['username'];
        }

        $data = $this->composer->getAuthData($this);

        return $data['username'] ?? '';
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        if (isset($this->data['password'])) {
            return $this->data['password'];
        }

        $data = $this->composer->getAuthData($this);

        return $data['password'] ?? '';
    }

    /**
     * @return array
     */
    public function getAuthJsonCredentials()
    {
        return [
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
        ];
    }

    protected function getHttpClient()
    {
        $client = parent::getHttpClient();

        return $client->setAuth($this->getUsername(), $this->getPassword());
    }
}
