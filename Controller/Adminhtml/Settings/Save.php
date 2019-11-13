<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Swissup\Marketplace\Model\ChannelRepository;

class Save extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Marketplace::settings_save';

    /**
     * @var ChannelRepository
     */
    protected $channelRepository;

    /**
     * @param Context $context
     * @param ChannelRepository $channelRepository
     */
    public function __construct(
        Context $context,
        ChannelRepository $channelRepository
    ) {
        $this->channelRepository = $channelRepository;
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
        $channelsData = $this->getRequest()->getPostValue('channels');

        if ($channelsData) {
            try {
                foreach ($this->channelRepository->getList() as $channel) {
                    if (!isset($channelsData[$channel->getIdentifier()])) {
                        continue;
                    }
                    $channel->addData($data)->save();
                }

                $this->messageManager->addSuccess(__('Marketplace settings successfully updated.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the settings.'));
            }
        }

        return $resultRedirect->setPath('*/package/index');
    }
}
