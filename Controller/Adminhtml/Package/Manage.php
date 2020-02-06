<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Package;

use Magento\Framework\Controller\ResultFactory;
use Swissup\Marketplace\Model\Handler\PackageInstall;
use Swissup\Marketplace\Model\Handler\PackageUninstall;
use Swissup\Marketplace\Model\Handler\PackageUpdate;
use Swissup\Marketplace\Model\Handler\PackageEnable;
use Swissup\Marketplace\Model\Handler\PackageDisable;
use Swissup\Marketplace\Model\Job;
use Swissup\Marketplace\Model\HandlerValidationException;
use Swissup\Marketplace\Service\JobDispatcher;

class Manage extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Marketplace::package_manage';

    /**
     * @var array
     */
    protected $handlers = [
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
        $packages = $this->getRequest()->getPost('packages');
        $job = $this->getRequest()->getParam('job');

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response = new \Magento\Framework\DataObject();

        try {
            $job = $this->dispatcher->dispatch($this->getHandlerClass($job), [
                'packages' => $packages
            ]);

            $response->addData([
                'id' => $job->getId(),
                'created_at' => $job->getCreatedAt(),
            ]);
        } catch (HandlerValidationException $e) {
            $response->setError(1);
            $response->addData($e->getData());
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
    protected function getHandlerClass($key)
    {
        if (isset($this->handlers[$key])) {
            return $this->handlers[$key];
        }

        throw new \Exception(__('Operation "%1" is not permitted.', $key));
    }
}
