<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerEav\Repository;

use Eurotext\TranslationManager\Api\Data\ProjectEntityInterface;
use Eurotext\TranslationManagerEav\Api\ProjectAttributeRepositoryInterface;
use Eurotext\TranslationManagerEav\Model\ProjectAttribute;
use Eurotext\TranslationManagerEav\Model\ProjectAttributeFactory;
use Eurotext\TranslationManagerEav\Model\ResourceModel\ProjectAttributeCollectionFactory;
use Eurotext\TranslationManagerEav\Model\ResourceModel\ProjectAttributeResource;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class ProjectAttributeRepository implements ProjectAttributeRepositoryInterface
{
    /**
     * @var ProjectAttributeFactory
     */
    protected $projectAttributeFactory;

    /**
     * @var ProjectAttributeResource
     */
    private $attributeResource;

    /**
     * @var ProjectAttributeCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    public function __construct(
        ProjectAttributeResource $productResource,
        ProjectAttributeFactory $projectFactory,
        ProjectAttributeCollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->projectAttributeFactory = $projectFactory;
        $this->attributeResource       = $productResource;
        $this->collectionFactory       = $collectionFactory;
        $this->searchResultsFactory    = $searchResultsFactory;
    }

    /**
     * @param ProjectEntityInterface $object
     *
     * @return ProjectEntityInterface
     * @throws CouldNotSaveException
     */
    public function save(ProjectEntityInterface $object): ProjectEntityInterface
    {
        try {
            /** @var ProjectAttribute $object */
            $this->attributeResource->save($object);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $object;
    }

    /**
     * @param int $id
     *
     * @return ProjectEntityInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): ProjectEntityInterface
    {
        /** @var ProjectAttribute $object */
        $object = $this->projectAttributeFactory->create();
        $this->attributeResource->load($object, $id);
        if (!$object->getId()) {
            throw new NoSuchEntityException(__('Project with id "%1" does not exist.', $id));
        }

        return $object;
    }

    /**
     * @param ProjectEntityInterface $object
     *
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ProjectEntityInterface $object): bool
    {
        try {
            /** @var ProjectAttribute $object */
            $this->attributeResource->delete($object);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @param int $id
     *
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $id): bool
    {
        $object = $this->getById($id);

        return $this->delete($object);
    }

    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface
    {
        /** @var \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection */
        $collection = $this->collectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $fields     = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $condition    = $filter->getConditionType() ?: 'eq';
                $fields[]     = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $direction = ($sortOrder->getDirection() === SortOrder::SORT_ASC) ? 'ASC' : 'DESC';
                $collection->addOrder($sortOrder->getField(), $direction);
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());

        $objects = [];
        foreach ($collection as $objectModel) {
            $objects[] = $objectModel;
        }

        /** @var \Magento\Framework\Api\SearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($objects);

        return $searchResults;
    }
}
