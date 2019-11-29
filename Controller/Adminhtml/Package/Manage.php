<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Package;

use Swissup\Marketplace\Job\PackageInstall;
use Swissup\Marketplace\Job\PackageUninstall;
use Swissup\Marketplace\Job\PackageUpdate;
use Swissup\Marketplace\Job\PackageEnable;
use Swissup\Marketplace\Job\PackageDisable;
use Swissup\Marketplace\Service\JobDispatcher;

class Manage extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Marketplace::package_manage';

    /**
     * @var array
     */
    protected $jobs = [
        'install' => PackageInstall::class,
        'uninstall' => PackageUninstall::class,
        'update' => PackageUpdate::class,
        'enable' => PackageEnable::class,
        'disable' => PackageDisable::class,
    ];

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Swissup\Marketplace\Service\JobDispatcher
     */
    protected $dispatcher;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Swissup\Marketplace\Service\JobDispatcher $dispatcher
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Swissup\Marketplace\Service\JobDispatcher $dispatcher
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->dispatcher = $dispatcher;
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
            $this->dispatcher->dispatch(
                $this->getJobClassName($job),
                [
                    'packageName' => $package
                ]
            );
        } catch (\Exception $e) {
            $response->setMessage($e->getMessage());
            $response->setError(1);
        }

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($response);

        return $resultJson;
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getJobClassName($jobCode)
    {
        if (isset($this->jobs[$jobCode])) {
            return $this->jobs[$jobCode];
        }

        throw new \Exception(__('Operation "%1" is not permitted.', $jobCode));
    }
}
