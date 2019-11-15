<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Channel;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Swissup\Marketplace\Model\ChannelRepository;
use Swissup\Marketplace\Model\Session;

class Change extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Marketplace::package_index';

    /**
     * @var ChannelRepository
     */
    protected $channelRepository;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Context $context
     * @param ChannelRepository $channelRepository
     * @param Session $session
     */
    public function __construct(
        Context $context,
        ChannelRepository $channelRepository,
        Session $session
    ) {
        $this->channelRepository = $channelRepository;
        $this->session = $session;
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
        $id = $this->getRequest()->getParam('channel');

        try {
            if ($this->channelRepository->getById($id)) {
                $this->session->setChannelId($id);
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Something went wrong while saving the settings.'));
        }

        return $resultRedirect->setPath('*/package/index');
    }
}
