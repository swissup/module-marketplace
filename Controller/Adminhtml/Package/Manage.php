<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Package;

class Manage extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Marketplace::package_manage';

    /**
     * @var array
     */
    protected $allowedJobs = [
        'install',
        'uninstall',
        'update',
        'enable',
        'disable',
    ];

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
        $job = $this->getRequest()->getParam('job');
        $response = new \Magento\Framework\DataObject();

        try {
            $this->validate();
            $this->packageManager->{$job}($package);
        } catch (\Exception $e) {
            $response->setMessage($e->getMessage());
            $response->setError(1);
        }

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($response);

        return $resultJson;
    }

    /**
     * @throws \Exception
     */
    protected function validate()
    {
        $package = $this->getRequest()->getPost('package');
        $job = $this->getRequest()->getParam('job');

        if (!in_array($job, $this->allowedJobs) ||
            !method_exists($this->packageManager, $job)
        ) {
            throw new \Exception(__('Operation "%1" is not permitted.', $job));
        }
    }
}
