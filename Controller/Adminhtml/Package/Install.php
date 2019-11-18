<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Package;

class Install extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Marketplace::package_manage';

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Swissup\Marketplace\Model\PackageManager
     */
    protected $packageManager;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Swissup\Marketplace\Model\PackageManager $packageManager
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Swissup\Marketplace\Model\PackageManager $packageManager
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->packageManager = $packageManager;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $package = $this->getRequest()->getPost('package');
        $response = new \Magento\Framework\DataObject();

        try {
            $this->packageManager->install($package);
            $response->setPackage([
                'enabled' => true,
                'installed' => true,
            ]);
        } catch (\Exception $e) {
            $response->setMessage($e->getMessage());
            $response->setError(1);
        }

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($response);

        return $resultJson;
    }
}
