<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Package;

use Magento\Framework\Controller\ResultFactory;

class Install extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Marketplace::package_manage';

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Swissup\Marketplace\Model\Installer\Installer $installer
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Swissup\Marketplace\Model\Installer\Installer $installer
    ) {
        $this->installer = $installer;
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
            $this->installer->run($packages, $this->getRequest()->getParams());
            $this->messageManager->addSuccess(__('Package(s) successfully installed.'));
        } catch (\Exception $e) {
            $response->setMessage($e->getMessage());
            $response->setError(1);
        }

        return $resultJson->setData($response);
    }
}
