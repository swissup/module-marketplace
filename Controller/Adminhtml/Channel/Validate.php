<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Channel;

class Validate extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Marketplace::settings_save';

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Swissup\Marketplace\Model\ChannelRepository
     */
    protected $channelRepository;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Swissup\Marketplace\Model\ChannelRepository $channelRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Swissup\Marketplace\Model\ChannelRepository $channelRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->channelRepository = $channelRepository;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();

        try {
            $channel = $this->getRequest()->getParam('channel');
            $channel = $this->channelRepository->getById($channel);
            $channel->removeCache()->addData([
                'username' => $this->getRequest()->getParam('username'),
                'password' => $this->getRequest()->getParam('password'),
            ]);
            $packages = $channel->getPackages();
            $response->setTotal(count($packages));
        } catch (\Exception $e) {
            $response->setMessage($e->getMessage());
            $response->setError(1);
        }

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($response);

        return $resultJson;
    }
}
