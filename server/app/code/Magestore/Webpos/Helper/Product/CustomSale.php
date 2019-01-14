<?php
/**
 * Created by PhpStorm.
 * User: rooney
 * Date: 08/08/2018
 * Time: 09:52
 */

namespace Magestore\Webpos\Helper\Product;


use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class CustomSale extends \Magestore\Webpos\Helper\Data
{
    const SKU = 'pos_custom_sale';
    const TYPE = 'customsale';
    /**
     * @var \Magento\Framework\App\State
     */
    protected $_appState;
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;
    /**
     * @var AttributeSetFactory
     */
    protected $attributeSetFactory;
    /**
     * @var CategorySetupFactory
     */
    protected $categorySetupFactory;
    /**
     * @var \Magento\Store\Model\ResourceModel\Website\CollectionFactory
     */
    protected $_websiteCollectionFactory;
    /**
     * @var \Magestore\Webpos\Api\Catalog\Attribute\AttributesRepositoryInterface
     */
    protected $attributesRepository;
    /**
     * @var AttributeValue
     */
    protected $attributeValueHelper;

    /**
     * CustomSale constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Catalog\Model\Product $product
     * @param AttributeSetFactory $attributeSetFactory
     * @param \Magento\Store\Model\ResourceModel\Website\CollectionFactory $websiteCollectionFactory
     * @param CategorySetupFactory $categorySetupFactory
     * @param \Magestore\Webpos\Api\Catalog\Attribute\AttributesRepositoryInterface $attributesRepository
     * @param AttributeValue $attributeValueHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Catalog\Model\Product $product,
        AttributeSetFactory $attributeSetFactory,
        \Magento\Store\Model\ResourceModel\Website\CollectionFactory $websiteCollectionFactory,
        CategorySetupFactory $categorySetupFactory,
        \Magestore\Webpos\Api\Catalog\Attribute\AttributesRepositoryInterface $attributesRepository,
        AttributeValue $attributeValueHelper
    )
    {
        parent::__construct($context, $objectManager, $storeManager);
        $this->attributeSetFactory = $attributeSetFactory;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->_websiteCollectionFactory = $websiteCollectionFactory;
        $this->_product = $product;
        $this->_appState = $appState;
        $this->productMetadata = $productMetadata;
        $this->attributesRepository = $attributesRepository;
        $this->attributeValueHelper = $attributeValueHelper;
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */
    protected function getProductModel()
    {
        return $this->_product;
    }

    /**
     * @return int
     */
    public function getProductId()
    {
        return $this->getProductModel()->getIdBySku(self::SKU);
    }

    public function deleteProduct()
    {
        try {
            $id = $this->getProductModel()->getIdBySku(self::SKU);
            $this->getProductModel()->load($id)->delete();
        } catch (\Exception $e) {
            var_dump($e->getMessage(), $e->getTraceAsString());
        }
    }

    public function updateProduct()
    {
        $id = $this->getProductModel()->getIdBySku(self::SKU);
        $product = $this->getProductModel()->load($id);

        if ($this->isEnabledInventory()) {
            $this->registerWarehouse($product);
        }
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @return $this
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createProduct($setup)
    {
        try {
            $version = $this->productMetadata->getVersion();

            if (version_compare($version, '2.2.0', '>=') || $version === 'No version set (parsed as 1.0.0)') {
                $this->_appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
            } else {
                $this->_appState->setAreaCode('admin');
            }
        } catch (\Exception $e) {
            $this->_appState->getAreaCode();
        }

        $product = $this->getProductModel();
        if ($productId = $product->getIdBySku(self::SKU)) {
            return $this;
        } else {
            $product = $product->getCollection()->addAttributeToFilter('type_id', 'simple')->getFirstItem();
            $product->setId(null);
        }

        $websiteIds = $this->_websiteCollectionFactory->create()
            ->addFieldToFilter('website_id', array('neq' => 0))
            ->getAllIds();

        $attributeSet = $this->attributeSetFactory->create();
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
        $entityTypeId = $categorySetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
        $attributeSetId = $this->attributeSetFactory->create()
            ->getCollection()
            ->setEntityTypeFilter($entityTypeId)
            ->addFieldToFilter('attribute_set_name', 'Custom_Sale_Attribute_Set')
            ->getFirstItem()
            ->getAttributeSetId();
        if (!$attributeSetId) {
            $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);
            $data = [
                'attribute_set_name' => 'Custom_Sale_Attribute_Set', // define custom attribute set name here
                'entity_type_id' => $entityTypeId,
                'sort_order' => 200,
            ];
            $attributeSet->setData($data);
            $attributeSet->validate();
            $attributeSet->save();
            $attributeSet->initFromSkeleton($attributeSetId);
            $attributeSet->save();
            $attributeSetId = $attributeSet->getId();
        }


        $product->setAttributeSetId($attributeSetId)
            ->setTypeId(self::TYPE)
            ->setStoreId(0)
            ->setSku(self::SKU)
            ->setWebsiteIds($websiteIds)
            ->setStockData(array(
                'manage_stock' => 0,
                'use_config_manage_stock' => 0,
            ));

        if ($this->_moduleManager->isEnabled('Magestore_Giftvoucher')) {
            $product->setGiftCardType(1)
                ->setGiftType(1)
                ->setGiftPriceType(1);
        }
        
        $product->addData(array(
            'name' => 'Custom Sale',
            'weight' => 1,
            'status' => 1,
            'visibility' => 1,
            'price' => 0,
            'description' => 'Custom Sale for POS system',
            'short_description' => 'Custom Sale for POS system',
            'quantity_and_stock_status' => array(
                'is_in_stock' => 1,
                'qty' => 0
            )
        ));

        $product = $this->addRequireAttributes($product, $attributeSetId);

        if (!is_array($errors = $product->validate())) {
            try {
                $product->save();
                if (!$product->getId()) {
                    $lastProduct = $this->getProductModel()->getCollection()->setOrder('entity_id', 'DESC')->getFirstItem();
                    $lastProductId = $lastProduct->getId();
                    $product->setName('Custom Sale')->setId($lastProductId + 1)->save();
                    $this->getProductModel()->load(0)->delete();
                }

                if ($this->isEnabledInventory()) {
                    $this->registerWarehouse($product);
                }

            } catch (\Exception $e) {
                return $this;
            }
        }
    }

    /**
     * add data for requiring attributes of custom sale product
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int $attributeSetId
     * @throws \Magento\Framework\Exception\NoSuchEntityException If $attributeSetId is not found
     * @return \Magento\Catalog\Model\Product
     */
    protected function addRequireAttributes($product, $attributeSetId) {
        $listAttributes = $this->attributesRepository->getCustomSaleRequireAttributes($attributeSetId);
        /** @var \Magento\Catalog\Api\Data\ProductAttributeInterface $attribute */
        foreach($listAttributes as $attribute) {
            if($product->getData($attribute->getAttributeCode())) {
                continue;
            } elseif ($attribute['default_value'] != null) {
                $value = $attribute['default_value'];
            } else {
                $value = $this->attributeValueHelper->getDefaultValueAttribute($attribute);
            }

            if($value) {
                $product->setData($attribute->getAttributeCode(), $value);
            }
        }

        return $product;
    }

    /**
     * @param $product
     */
    private function registerWarehouse($product)
    {
        /** @var \Magestore\InventorySuccess\Model\Warehouse\Options $warehouseOptions */
        $warehouseOptions = $this->objectManager->get('\Magestore\InventorySuccess\Model\Warehouse\Options');
        $adjustStockFactory = $this->objectManager
            ->get('\Magestore\InventorySuccess\Model\AdjustStockFactory');

        $adjustStockManagement = $this->objectManager
            ->get('\Magestore\InventorySuccess\Api\AdjustStock\AdjustStockManagementInterface');
        $warehouseProductCollectionFactory = $this->objectManager
            ->get('\Magestore\InventorySuccess\Model\ResourceModel\Warehouse\Product\CollectionFactory');

        $warehouses = $warehouseOptions->collectionFactory->create();

        $warehouseProductCollection = $warehouseProductCollectionFactory->create()->selectAllStocks();
        $warehouseProducts = $warehouseProductCollection
            ->addFieldToFilter(
                \Magestore\InventorySuccess\Api\Data\Warehouse\ProductInterface::WAREHOUSE_ID,
                array(
                    'in' => $warehouses->getColumnValues('warehouse_id')
                )
            )
            ->addFieldToFilter(
                \Magestore\InventorySuccess\Api\Data\Warehouse\ProductInterface::PRODUCT_ID,
                $product->getId()
            );

        $registeredWarehouses = $warehouseProducts->getColumnValues('website_id');


        foreach ($warehouses as $warehouse) {

            if (in_array($warehouse->getWarehouseId(), $registeredWarehouses)) {
                continue;
            }

            $adjustData = array(
                \Magestore\InventorySuccess\Api\Data\AdjustStock\AdjustStockInterface::WAREHOUSE_ID => $warehouse->getData('warehouse_id'),
                \Magestore\InventorySuccess\Api\Data\AdjustStock\AdjustStockInterface::WAREHOUSE_NAME => $warehouse->getData('warehouse_name'),
                \Magestore\InventorySuccess\Api\Data\AdjustStock\AdjustStockInterface::WAREHOUSE_CODE => $warehouse->getData('warehouse_code'),
                \Magestore\InventorySuccess\Api\Data\AdjustStock\AdjustStockInterface::REASON => 'Create custom sale',
                'products' => [
                    $product->getId() => [
                        'adjust_qty' => 1,
                        'product_name' => $product->getName(),
                        'product_sku' => $product->getSku()
                    ]
                ]
            );


            $adjustStock = $adjustStockFactory->create();

            /* create stock adjustment, require products, require qty changed */
            $adjustStockManagement->createAdjustment($adjustStock, $adjustData, true, true);

            /* created adjuststock or not */
            if ($adjustStock->getId()) {
                /* complete stock adjustment */
                $adjustStockManagement->complete($adjustStock, true);
            }
        }
    }
}