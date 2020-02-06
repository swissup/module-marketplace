<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Swissup\Marketplace\Model\Handler\ChannelsSave;
use Swissup\Marketplace\Model\Job;
use Swissup\Marketplace\Service\JobDispatcher;

class Save extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Marketplace::settings_save';

    /**
     * @var JobDispatcher
     */
    protected $dispatcher;

    /**
     * @param Context $context
     * @param JobDispatcher $dispatcher
     */
    public function __construct(
        Context $context,
        JobDispatcher $dispatcher
    ) {
        $this->dispatcher = $dispatcher;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response = new \Magento\Framework\DataObject();
        $channels = $this->getRequest()->getPostValue('channels');

        if ($channels) {
            try {
                $job = $this->dispatcher->dispatch(ChannelsSave::class, [
                    'channels' => $channels
                ]);

                $response->addData([
                    'message' => __('Please wait a minute until the changes will take place.'),
                    'id' => $job->getId(),
                    'created_at' => $job->getCreatedAt(),
                ]);
            } catch (\Exception $e) {
                $response->setMessage($e->getMessage());
                $response->setError(1);
            }
        }

        return $resultJson->setData($response);
    }
}
