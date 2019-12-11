<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Package;

use Magento\Framework\Controller\ResultFactory;
use Swissup\Marketplace\Job\PackageInstall;
use Swissup\Marketplace\Job\PackageUninstall;
use Swissup\Marketplace\Job\PackageUpdate;
use Swissup\Marketplace\Job\PackageEnable;
use Swissup\Marketplace\Job\PackageDisable;
use Swissup\Marketplace\Model\Job as AsyncJob;
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
     * @var \Swissup\Marketplace\Service\JobDispatcher
     */
    protected $dispatcher;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Swissup\Marketplace\Service\JobDispatcher $dispatcher
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Swissup\Marketplace\Service\JobDispatcher $dispatcher
    ) {
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

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response = new \Magento\Framework\DataObject();

        try {
            $job = $this->dispatcher->dispatch($this->getJobClassName($job), [
                'packageName' => $package
            ]);

            if ($job instanceof AsyncJob) {
                $response->addData([
                    'id' => $job->getId(),
                    'created_at' => $job->getCreatedAt(),
                ]);
            } else {
                $this->messageManager->addSuccess(__('Settings successfully updated.'));
                $response->addData(['reload' => true]);
            }
        } catch (\Exception $e) {
            $response->setMessage($e->getMessage());
            $response->setError(1);
        }

        return $resultJson->setData($response);
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
