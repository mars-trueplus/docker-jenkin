<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\Location;
use Magestore\Webpos\Api\Data\Location\LocationInterface;

/**
 * Class LocationRepository
 * @package Magestore\Webpos\Model\Location
 */
class LocationRepository implements \Magestore\Webpos\Api\Location\LocationRepositoryInterface {
    /**
     * @var \Magestore\Webpos\Model\ResourceModel\Location\Location
     */
    protected $resourceLocation;

    /**
     * @var \Magestore\Webpos\Model\ResourceModel\Location\Location\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var LocationFactory
     */
    protected $locationFactory;

    /**
     * @var \Magestore\Webpos\Api\Data\Location\LocationSearchResultsInterfaceFactory
     */
    protected $locationSearchResultsFactory;

    /**
     * @var \Magestore\Webpos\Api\Pos\PosRepositoryInterface
     */
    protected $posRepositoryInterface;
    /**
     * @var \Magestore\Appadmin\Api\Staff\StaffRepositoryInterface
     */
    protected $staffRepository;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    /**
     * @var \Magestore\Webpos\Api\Staff\SessionRepositoryInterface
     */
    protected $sessionRepository;
    /**
     * @var \Magestore\Webpos\Api\Data\Staff\Login\AvailableLocationInterface
     */
    protected $availableLocation;
    /**
     * @var \Magestore\Webpos\Api\Shift\ShiftRepositoryInterface
     */
    protected $shiftRepository;

    /**
     * LocationRepository constructor.
     * @param \Magestore\Webpos\Model\ResourceModel\Location\Location $resourceLocation
     * @param \Magestore\Webpos\Model\ResourceModel\Location\Location\CollectionFactory $collectionFactory
     * @param LocationFactory $locationFactory
     * @param \Magestore\Webpos\Api\Data\Location\LocationSearchResultsInterfaceFactory $locationSearchResultsFactory
     * @param \Magestore\Webpos\Api\Pos\PosRepositoryInterface $posRepository
     * @param \Magestore\Appadmin\Api\Staff\StaffRepositoryInterface $staffRepository
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magestore\Webpos\Api\Staff\SessionRepositoryInterface $sessionRepository
     * @param \Magestore\Webpos\Api\Data\Staff\Login\AvailableLocationInterface $availableLocation
     * @param \Magestore\Webpos\Api\Shift\ShiftRepositoryInterface $shiftRepository
     */
    public function __construct(
        \Magestore\Webpos\Model\ResourceModel\Location\Location $resourceLocation,
        \Magestore\Webpos\Model\ResourceModel\Location\Location\CollectionFactory $collectionFactory,
        LocationFactory $locationFactory,
        \Magestore\Webpos\Api\Data\Location\LocationSearchResultsInterfaceFactory $locationSearchResultsFactory,
        \Magestore\Webpos\Api\Pos\PosRepositoryInterface $posRepository,
        \Magestore\Appadmin\Api\Staff\StaffRepositoryInterface $staffRepository,
        \Magento\Framework\App\RequestInterface $request,
        \Magestore\Webpos\Api\Staff\SessionRepositoryInterface $sessionRepository,
        \Magestore\Webpos\Api\Data\Staff\Login\AvailableLocationInterface $availableLocation,
        \Magestore\Webpos\Api\Shift\ShiftRepositoryInterface $shiftRepository
    ) {
        $this->resourceLocation = $resourceLocation;
        $this->collectionFactory = $collectionFactory;
        $this->locationFactory = $locationFactory;
        $this->locationSearchResultsFactory = $locationSearchResultsFactory;
        $this->posRepositoryInterface = $posRepository;
        $this->staffRepository = $staffRepository;
        $this->request = $request;
        $this->sessionRepository = $sessionRepository;
        $this->availableLocation = $availableLocation;
        $this->shiftRepository = $shiftRepository;
    }

    /**
     * @inheritdoc
     */
    public function getById($id) {
        $location = $this->locationFactory->create();
        $this->resourceLocation->load($location, $id);
        if($location->getId()) {
            return $location;
        } else {
            throw new \Magento\Framework\Exception\NoSuchEntityException(__('Location with id "%1" does not exist.', $id));
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria) {
        $collection = $this->collectionFactory->create();
        //Add filters from root filter group to the collection
        /** @var FilterGroup $group */
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders === null) {
            $sortOrders = [];
        }
        /** @var \Magento\Framework\Api\SortOrder $sortOrder */
        foreach ($sortOrders as $sortOrder) {
            $field = $sortOrder->getField();
            $collection->addOrder(
                $field,
                ($sortOrder->getDirection() == \Magento\Framework\Api\SortOrder::SORT_ASC)
                    ? \Magento\Framework\Api\SortOrder::SORT_ASC : \Magento\Framework\Api\SortOrder::SORT_DESC
            );
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $collection->load();
        $searchResults = $this->locationSearchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function getAllLocation() {
        /** @var \Magestore\Webpos\Model\ResourceModel\Location\Location\Collection $collection */
        $collection = $this->collectionFactory->create();
        return $collection;
    }

    /**
     * @inheritdoc
     */
    public function save(\Magestore\Webpos\Api\Data\Location\LocationInterface $location) {
        return $this->resourceLocation->save($location);
    }

    /**
     * @inheritdoc
     */
    public function delete(\Magestore\Webpos\Api\Data\Location\LocationInterface $location) {
        return $this->deleteById($location->getLocationId());
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($locationId)
    {
        /* @var \Magestore\Webpos\Model\Location\Location $location */
        $location = $this->getById($locationId);

            if ($location->getId()) {
                // check location
                $checkDelete = true;
                if (count($this->posRepositoryInterface->getPosByLocationId($location->getLocationId())) > 0) {
                    $checkDelete = false;
                }

                if($checkDelete) {
                    $this->resourceLocation->delete($location);
                    return true;
                } else {
                    throw new \Magento\Framework\Exception\ValidatorException(__('The operations failed. Some locations are still working. You can\'t delete them!'));
                }
            } else {
                throw new \Magento\Framework\Exception\NoSuchEntityException(__('Location with id "%1" does not exist.', $locationId));
            }

    }


    /**
     * @param $staffId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getLocationIdsFromStaffId($staffId) {
        $staff = $this->staffRepository->getById($staffId);
        if(!$staff->getStaffId()) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(__('Staff does not exist.'));
        }

        $locationIds = $staff->getLocationIds();
        return explode(',', $locationIds);
    }

    /**
     * {@inheritdoc}
     */
    public function getListAvailable($staffId){
        $locationIdsArray = $this->getLocationIdsFromStaffId($staffId);
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter($collection->getResource()->getIdFieldName(), array('in' => $locationIdsArray));

        $locations = [];
        $numberPosAssign = 0;

        /** @var LocationInterface $item */
        foreach ($collection as $item) {
            $posCollection = $this->posRepositoryInterface->getPosByLocationId($item->getLocationId());
            $posArray = [];
            /** @var \Magestore\Webpos\Api\Data\Pos\PosInterface $pos */
            foreach ($posCollection as $pos) {
                if ($pos->getStatus() == \Magestore\Webpos\Model\Source\Adminhtml\Status::STATUS_ENABLED) {
                    $currentShiftByPos = $this->shiftRepository->getCurrentShiftByPosId($pos->getPosId());
                    if (
                        $currentShiftByPos->getStaffId() && $currentShiftByPos->getStaffId() != $staffId
                    ) {
                        $pos->setStaffId($currentShiftByPos->getStaffId());
                        $pos->setStaffName($currentShiftByPos->getStaffName());
                    }

                    $posArray[] = $pos;
                    $numberPosAssign++;
                }
            }
            $item->setPos($posArray);
            $locations[] = $item;
        }


        if (!count($locations) || !$numberPosAssign) {
            return false;
        }

        return $locations;
    }

    /**
     * @param $staffId
     * @return array|bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getListLocationWithStaff($staffId){
        $locationIdsArray = $this->getLocationIdsFromStaffId($staffId);
        $collection = $this->collectionFactory->create();

        $locations = [];
        $numberPosAssign = 0;

        /** @var LocationInterface $item */
        foreach ($collection as $item) {
            $locationId = $item->getLocationId() ? : $item->getWarehouseId();
            if (!in_array($locationId, $locationIdsArray)) {
                $locations[] = $item;
                continue;
            }

            $posCollection = $this->posRepositoryInterface->getPosByLocationId($locationId);
            $posArray = [];
            /** @var \Magestore\Webpos\Api\Data\Pos\PosInterface $pos */
            foreach ($posCollection as $pos) {
                if ($pos->getStatus() == \Magestore\Webpos\Model\Source\Adminhtml\Status::STATUS_ENABLED) {
                    $currentShiftByPos = $this->shiftRepository->getCurrentShiftByPosId($pos->getPosId());
                    if (
                        $currentShiftByPos->getStaffId() && $currentShiftByPos->getStaffId() != $staffId
                    ) {
                        $pos->setStaffId($currentShiftByPos->getStaffId());
                        $pos->setStaffName($currentShiftByPos->getStaffName());
                    }

                    $posArray[] = $pos;
                    $numberPosAssign++;
                }
            }

            $item->setPos($posArray);
            $locations[] = $item;
        }


        if (!count($locations) || !$numberPosAssign) {
            return false;
        }

        return $locations;
    }
}