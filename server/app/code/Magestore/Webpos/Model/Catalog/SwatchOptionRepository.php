<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Webpos\Model\Catalog;

use Magento\Eav\Model\Entity\Attribute\Option;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class SwatchOptionRepository implements \Magestore\Webpos\Api\Catalog\SwatchOptionRepositoryInterface
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;
    /**
     * @var \Magento\Swatches\Helper\Data
     */
    protected $swatchHelper;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    protected $attributeCollection;
    /**
     * @var \Magestore\Webpos\Api\Data\Catalog\Option\SwatchOptionSearchResultsInterfaceFactory
     */
    protected $swatchOptionSearchResultsFactory;
    /**
     * @var \Magestore\Webpos\Api\Data\Catalog\Option\Swatch\SwatchInterface
     */
    protected $swatchInterfaceFactory;
    /**
     * @var \Magestore\Webpos\Api\Data\Catalog\Option\SwatchOptionInterfaceFactory
     */
    protected $swatchOptionInterfaceFactory;

    /**
     * SwatchOptionRepository constructor.
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Swatches\Helper\Data $swatchHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollection
     * @param \Magestore\Webpos\Api\Data\Catalog\Option\SwatchOptionSearchResultsInterfaceFactory $swatchOptionSearchResultsFactory
     * @param \Magestore\Webpos\Api\Data\Catalog\Option\Swatch\SwatchInterfaceFactory $swatchInterfaceFactory
     * @param \Magestore\Webpos\Api\Data\Catalog\Option\SwatchOptionInterfaceFactory $swatchOptionInterfaceFactory
     */
    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Swatches\Helper\Data $swatchHelper,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollection,
        \Magestore\Webpos\Api\Data\Catalog\Option\SwatchOptionSearchResultsInterfaceFactory $swatchOptionSearchResultsFactory,
        \Magestore\Webpos\Api\Data\Catalog\Option\Swatch\SwatchInterfaceFactory $swatchInterfaceFactory,
        \Magestore\Webpos\Api\Data\Catalog\Option\SwatchOptionInterfaceFactory $swatchOptionInterfaceFactory
    ) {
        $this->_resultPageFactory = $resultPageFactory;
        $this->swatchHelper = $swatchHelper;
        $this->attributeCollection = $attributeCollection;
        $this->swatchOptionSearchResultsFactory = $swatchOptionSearchResultsFactory;
        $this->swatchInterfaceFactory = $swatchInterfaceFactory;
        $this->swatchOptionInterfaceFactory = $swatchOptionInterfaceFactory;
    }

    /**
     * @inheritdoc
     */
    public function getSwatchOptions() {
        $swatchAttributeArray= array();
        $swatchArray = array();
        $collection = $this->attributeCollection->create();
        foreach ($collection as $attributeModel) {
            $isSwatch = $this->swatchHelper->isSwatchAttribute($attributeModel);
            if ($isSwatch) {
                $swatchAttributeArray[] = $attributeModel->getId();
                $attributeOptions = [];
                foreach ($attributeModel->getOptions() as $option) {
                    $attributeOptions[$option->getValue()] = $this->getUnusedOption($option);
                }
                $attributeOptionIds = array_keys($attributeOptions);
                $swatches = $this->swatchHelper->getSwatchesByOptionsId($attributeOptionIds);
                $data = [
                    'attribute_id' => $attributeModel->getId(),
                    'attribute_code' => $attributeModel->getAttributeCode(),
                    'attribute_label' => $attributeModel->getStoreLabel(),
                    'swatches' => $this->getSwatchOptionData($swatches),
                ];
                /** @var \Magestore\Webpos\Api\Data\Catalog\Option\SwatchOptionInterface $swatch */
                $swatch = $this->swatchOptionInterfaceFactory->create();
                $swatch->setData($data);
                $swatchArray[] = $swatch;
            }
        };
        /** @var \Magestore\Webpos\Api\Data\Catalog\Option\SwatchOptionSearchResultsInterface $swatchInterface */
        $swatchInterface = $this->swatchOptionSearchResultsFactory->create();
        $swatchInterface->setItems($swatchArray);
        $swatchInterface->setTotalCount(count($swatchArray));
        return $swatchInterface;
    }

    /**
     * @param array $swatches
     * @return \Magestore\Webpos\Api\Data\Catalog\Option\Swatch\SwatchInterface[]
     */
    protected function getSwatchOptionData($swatches) {
        $data = [];
        foreach ($swatches as $swatch) {
            /** @var \Magestore\Webpos\Api\Data\Catalog\Option\Swatch\SwatchInterface $swt */
            $swt = $this->swatchInterfaceFactory->create();

            $swt->setSwatchId($swatch['swatch_id']);
            $swt->setOptionId($swatch['option_id']);
            $swt->setType($swatch['type']);
            $swt->setValue($swatch['value']);

            $data[] = $swt;
        }

        return $data;
    }

    protected function getUnusedOption(Option $swatchOption)
    {
        return [
            'label' => $swatchOption->getLabel(),
            'link' => 'javascript:void();',
            'custom_style' => 'disabled'
        ];
    }
}