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
use Psr\Log\LoggerInterface;

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

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        EntityTypeCollection $entityTypeCollection,
        AttributeRepositoryInterface $attributeRepository,
        ProjectAttributeFactory $projectAttributeFactory,
        ProjectAttributeRepositoryInterface $projectAttributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        $this->attributeRepository        = $attributeRepository;
        $this->projectAttributeFactory    = $projectAttributeFactory;
        $this->projectAttributeRepository = $projectAttributeRepository;
        $this->searchCriteriaBuilder      = $searchCriteriaBuilder;
        $this->entityTypeCollection       = $entityTypeCollection;
        $this->logger                     = $logger;
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
            // no products found, matching the criteria
            $this->logger->notice(sprintf('no matching attributes found for entity %s', $entityTypeCode));

            return $result;
        }

        $entitiesNotFound = array_flip($entities);

        // create project entities
        $attributes = $searchResult->getItems();

        $projectId = $project->getId();
        foreach ($attributes as $attribute) {
            /** @var $attribute AttributeInterface */
            $entityId      = (int)$attribute->getAttributeId();
            $attributeCode = $attribute->getAttributeCode();

            // Found entity, so remove it from not found list
            unset($entitiesNotFound[$attributeCode]);

            $this->searchCriteriaBuilder
                ->addFilter(ProjectAttributeSchema::ENTITY_ID, $entityId)
                ->addFilter(ProjectAttributeSchema::PROJECT_ID, $projectId);
            $searchCriteria = $this->searchCriteriaBuilder->create();

            $searchResults = $this->projectAttributeRepository->getList($searchCriteria);

            if ($searchResults->getTotalCount() >= 1) {
                // attribute has already been added to project
                $this->logger->info(sprintf('skipping attribute "%s"(%d) already added', $attributeCode, $entityId));
                continue;
            }

            /** @var ProjectAttributeInterface $projectAttribute */
            $projectAttribute = $this->projectAttributeFactory->create();
            $projectAttribute->setProjectId($projectId);
            $projectAttribute->setEntityId($entityId);
            $projectAttribute->setAttributeCode($attributeCode);
            $projectAttribute->setEavEntityType($entityTypeCode);
            $projectAttribute->setStatus(ProjectAttributeInterface::STATUS_NEW);

            try {
                $this->projectAttributeRepository->save($projectAttribute);
            } catch (\Exception $e) {
                $result = false;
            }
        }

        $this->logger->notice(sprintf('added matching attributes for entity %s', $entityTypeCode));

        // Log entites that where not found
        if (count($entitiesNotFound) > 0) {
            foreach ($entitiesNotFound as $code => $value) {
                $this->logger->error(sprintf('attribute-code "%s" not found', $code));
            }

        }

        return $result;
    }
}
