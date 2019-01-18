<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Seeder;

use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\EntitySeederInterface;
use Eurotext\TranslationManagerEav\Api\Data\ProjectAttributeInterface;
use Eurotext\TranslationManagerEav\Api\ProjectAttributeRepositoryInterface;
use Eurotext\TranslationManagerEav\Model\ProjectAttributeFactory;
use Eurotext\TranslationManagerEav\Setup\ProjectAttributeSchema;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Entity\Type as EntityType;
use Magento\Eav\Model\ResourceModel\Entity\Type\Collection as EntityTypeCollection;
use Magento\Framework\Api\SearchCriteriaBuilder;

class AttributeSeeder implements EntitySeederInterface
{
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var ProjectAttributeFactory
     */
    private $projectAttributeFactory;

    /**
     * @var ProjectAttributeRepositoryInterface
     */
    private $projectAttributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var EntityTypeCollection
     */
    private $entityTypeCollection;

    public function __construct(
        EntityTypeCollection $entityTypeCollection,
        AttributeRepositoryInterface $attributeRepository,
        ProjectAttributeFactory $projectAttributeFactory,
        ProjectAttributeRepositoryInterface $projectAttributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->attributeRepository        = $attributeRepository;
        $this->projectAttributeFactory    = $projectAttributeFactory;
        $this->projectAttributeRepository = $projectAttributeRepository;
        $this->searchCriteriaBuilder      = $searchCriteriaBuilder;
        $this->entityTypeCollection       = $entityTypeCollection;
    }

    public function seed(ProjectInterface $project, array $entities = []): bool
    {
        $result = true;

        $this->entityTypeCollection->load();

        foreach ($this->entityTypeCollection->getItems() as $entityType) {
            /** @var EntityType $entityType */
            $entityTypeCode = $entityType->getEntityTypeCode();

            $isSeeded = $this->seedEntityType($project, $entityTypeCode, $entities);

            $result = !$isSeeded ? false : $result;
        }

        return $result;
    }

    private function seedEntityType(ProjectInterface $project, string $entityTypeCode, array $entities = []): bool
    {
        $result = true;

        // get attribute collection
        $this->searchCriteriaBuilder->addFilter('is_user_defined', 1);
        if (count($entities) > 0) {
            $this->searchCriteriaBuilder->addFilter('attribute_code', $entities, 'in');
        }
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResult = $this->attributeRepository->getList($entityTypeCode, $searchCriteria);

        if ($searchResult->getTotalCount() === 0) {
            return $result;
        }

        // create project entities
        $attributes = $searchResult->getItems();

        $projectId = $project->getId();
        foreach ($attributes as $attribute) {
            /** @var $attribute AttributeInterface */
            $entityId = (int)$attribute->getAttributeId();

            $this->searchCriteriaBuilder
                ->addFilter(ProjectAttributeSchema::ENTITY_ID, $entityId)
                ->addFilter(ProjectAttributeSchema::PROJECT_ID, $projectId);
            $searchCriteria = $this->searchCriteriaBuilder->create();

            $searchResults = $this->projectAttributeRepository->getList($searchCriteria);

            if ($searchResults->getTotalCount() >= 1) {
                continue;
            }

            /** @var ProjectAttributeInterface $projectAttribute */
            $projectAttribute = $this->projectAttributeFactory->create();
            $projectAttribute->setProjectId($projectId);
            $projectAttribute->setEntityId($entityId);
            $projectAttribute->setAttributeCode($attribute->getAttributeCode());
            $projectAttribute->setEavEntityType($entityTypeCode);
            $projectAttribute->setStatus(ProjectAttributeInterface::STATUS_NEW);

            try {
                $this->projectAttributeRepository->save($projectAttribute);
            } catch (\Exception $e) {
                $result = false;
            }
        }

        return $result;
    }
}
