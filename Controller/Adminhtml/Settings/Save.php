<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Swissup\Marketplace\Job\ChannelsSave;
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
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $channels = $this->getRequest()->getPostValue('channels');

        if ($channels) {
            try {
                $this->dispatcher->dispatch(ChannelsSave::class, [
                    'data' => $channels
                ]);
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the settings.'));
            }
        }

        return $resultRedirect->setPath('*/package/index');
    }
}
