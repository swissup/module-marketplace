<?php

namespace Swissup\Marketplace\Installer\Command;

use Swissup\Marketplace\Installer\Request;
use Swissup\Marketplace\Model\Traits\LoggerAware;

class ProductAttribute
{
    use LoggerAware;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory
     */
    private $attributeFactory;

    /**
     * @var \Magento\Catalog\Helper\Product
     */
    private $productHelper;

    /**
     * @var \Magento\Eav\Model\EntityFactory
     */
    private $eavEntityFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory
     */
    private $attributeSetCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Attribute
     */
    private $attributeResource;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Eav\Model\EntityFactory $eavEntityFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Attribute $attributeResource
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Attribute $attributeResource
    ) {
        $this->attributeResource = $attributeResource;
        $this->attributeFactory = $attributeFactory;
        $this->productHelper = $productHelper;
        $this->eavEntityFactory = $eavEntityFactory;
        $this->attributeSetCollectionFactory = $attributeSetCollectionFactory;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function execute(Request $request)
    {
        $this->logger->info('Product Attributes: Update attributes');

        $entityTypeId = $this->eavEntityFactory->create()
            ->setType(\Magento\Catalog\Model\Product::ENTITY)
            ->getTypeId();
        $attributeSets = $this->attributeSetCollectionFactory->create()
            ->setEntityTypeFilter($entityTypeId);

        foreach ($request->getParams() as $data) {
            /* @var $model \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
            $model = $this->attributeFactory->create();
            $this->attributeResource->load($model, $data['attribute_code'], 'attribute_code');
            if ($model->getId()) {
                continue;
            }

            $data = array_merge([
                'is_global'=> 0,
                'frontend_input'=> 'boolean',
                'is_configurable'=> 0,
                'is_filterable'=> 0,
                'is_filterable_in_search' => 0,
                'sort_order' => 1,
            ], $data);

            $data['source_model'] = $this->productHelper->getAttributeSourceModelByInputType(
                $data['frontend_input']
            );
            $data['backend_model'] = $this->productHelper->getAttributeBackendModelByInputType(
                $data['frontend_input']
            );
            $data['backend_type'] = $model->getBackendTypeByInput($data['frontend_input']);

            $model->addData($data);
            $model->setEntityTypeId($entityTypeId);
            $model->setIsUserDefined(1);

            foreach ($attributeSets as $set) {
                $model->setAttributeSetId($set->getId());
                $model->setAttributeGroupId($set->getDefaultGroupId());
                try {
                    $this->attributeResource->save($model);
                } catch (\Exception $e) {
                    $this->logger->warning($e->getMessage());
                }
            }

            if (!$attributeSets->count()) {
                try {
                    $this->attributeResource->save($model);
                } catch (\Exception $e) {
                    $this->logger->warning($e->getMessage());
                }
            }
        }
    }
}
