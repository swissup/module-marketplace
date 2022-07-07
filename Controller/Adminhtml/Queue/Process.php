<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Queue;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Stdlib\DateTime;
use Swissup\Marketplace\Model\Job;

class Process extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Marketplace::package_manage';

    protected $collectionFactory;

    protected $dispatcher;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory $collectionFactory,
        \Swissup\Marketplace\Service\QueueDispatcher $dispatcher
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->dispatcher = $dispatcher;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response = new \Magento\Framework\DataObject();

        try {
            $jobs = $this->collectionFactory->create()
                ->addFieldToFilter('status', Job::STATUS_PENDING)
                ->addFieldToFilter('scheduled_at', [
                    'or' => [
                        ['date' => true, 'to' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT)],
                        ['is' => new \Zend_Db_Expr('null')],
                    ]
                ])
                ->setOrder('scheduled_at', 'ASC')
                ->setOrder('created_at', 'ASC');

            $this->dispatcher->dispatch($jobs);

            $response->addData([
                'success' => true
            ]);
        } catch (\Exception $e) {
            $response->setMessage($e->getMessage());
            $response->setError(1);
        }

        return $resultJson->setData($response);
    }
}
