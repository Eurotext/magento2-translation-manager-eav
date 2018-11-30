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
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteriaInterfaceFactory;

class AttributeSeeder implements EntitySeederInterface
{
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var SearchCriteriaInterfaceFactory
     */
    private $searchCriteriaFactory;

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

    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaInterfaceFactory $searchCriteriaFactory,
        ProjectAttributeFactory $projectAttributeFactory,
        ProjectAttributeRepositoryInterface $projectAttributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->attributeRepository        = $attributeRepository;
        $this->searchCriteriaFactory      = $searchCriteriaFactory;
        $this->projectAttributeFactory    = $projectAttributeFactory;
        $this->projectAttributeRepository = $projectAttributeRepository;
        $this->searchCriteriaBuilder      = $searchCriteriaBuilder;
    }

    public function seed(ProjectInterface $project): bool
    {
        $result = true;

        // get attribute collection
        /** @var $searchCriteria SearchCriteriaInterface */
        $searchCriteria = $this->searchCriteriaFactory->create();
        $searchResult   = $this->attributeRepository->getList('catalog_product', $searchCriteria);

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
