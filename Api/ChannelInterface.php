<?php

namespace Swissup\Marketplace\Api;

interface ChannelInterface
{
    /**
     * @return $this
     */
    public function save();

    /**
     * @param array $data
     */
    public function addData(array $data);

    /**
     * @return boolean
     */
    public function isEnabled();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return string
     */
    public function getUrl();

    /**
     * @return string
     */
    public function getHostname();

    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @return string
     */
    public function getAuthType();

    /**
     * @return boolean
     */
    public function useKeysAsPassword();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return array
     */
    public function getPackages();

    /**
     * @return $this
     */
    public function removeCache();

    /**
     * @return array
     */
    public function getComposerRepositoryData();
}
