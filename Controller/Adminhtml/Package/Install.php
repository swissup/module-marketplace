<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Package;

use Magento\Framework\Controller\ResultFactory;

class Install extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Marketplace::package_manage';

    /**
     * @var \Swissup\Marketplace\Installer\Installer
     */
    private $installer;

    /**
     * @var \Swissup\Marketplace\Model\Logger\BufferLogger
     */
    private $logger;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Swissup\Marketplace\Installer\Installer $installer
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Swissup\Marketplace\Installer\Installer $installer,
        \Swissup\Marketplace\Model\Logger\BufferLogger $logger
    ) {
        $this->installer = $installer;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $packages = $this->getRequest()->getPost('packages');

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response = new \Magento\Framework\DataObject();

        try {
            $this->installer
                ->setLogger($this->logger)
                ->run($packages, $this->getRequest()->getParams());

            $response->setMessage(__('Package(s) successfully installed.'));
            $response->setLog($this->logger->getLog());
        } catch (\Exception $e) {
            $response->setMessage($e->getMessage());
            $response->setError(1);
        }

        return $resultJson->setData($response);
    }
}
