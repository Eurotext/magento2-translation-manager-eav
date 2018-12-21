<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Retriever;

use Eurotext\RestApiClient\Api\Project\ItemV1ApiInterface;
use Eurotext\RestApiClient\Request\Project\ItemGetRequest;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\EntityRetrieverInterface;
use Eurotext\TranslationManagerEav\Api\Data\ProjectAttributeInterface;
use Eurotext\TranslationManagerEav\Api\ProjectAttributeRepositoryInterface;
use Eurotext\TranslationManagerEav\Mapper\AttributeItemGetMapper;
use Eurotext\TranslationManagerEav\Setup\ProjectAttributeSchema;
use Eurotext\TranslationManagerProduct\Api\ProjectProductRepositoryInterface;
use Eurotext\TranslationManagerProduct\Mapper\ProductItemGetMapper;
use Eurotext\TranslationManagerProduct\Setup\ProjectProductSchema;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;

class AttributeRetriever implements EntityRetrieverInterface
{
    /**
     * @var ProjectProductRepositoryInterface
     */
    private $projectEntityRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ItemV1ApiInterface
     */
    private $itemApi;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProductItemGetMapper
     */
    private $attributeItemGetMapper;

    /**
     * @var AttributeOptionManagementInterface
     */
    private $attributeOptionManagement;

    public function __construct(
        ItemV1ApiInterface $itemApi,
        ProjectAttributeRepositoryInterface $projectEntityRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepositoryInterface $attributeRepository,
        AttributeOptionManagementInterface $attributeOptionManagement,
        AttributeItemGetMapper $attributeItemGetMapper,
        LoggerInterface $logger
    ) {
        $this->projectEntityRepository   = $projectEntityRepository;
        $this->searchCriteriaBuilder     = $searchCriteriaBuilder;
        $this->itemApi                   = $itemApi;
        $this->attributeRepository       = $attributeRepository;
        $this->attributeItemGetMapper    = $attributeItemGetMapper;
        $this->logger                    = $logger;
        $this->attributeOptionManagement = $attributeOptionManagement;
    }

    public function retrieve(ProjectInterface $project): bool
    {
        $result = true;

        $projectId    = $project->getId();
        $projectExtId = $project->getExtId();
        $storeId      = $project->getStoreviewDst();

        $this->logger->info(sprintf('retrieve project attributes project-id:%d', $projectId));

        $this->searchCriteriaBuilder->addFilter(ProjectProductSchema::PROJECT_ID, $projectId);
        $this->searchCriteriaBuilder->addFilter(ProjectProductSchema::EXT_ID, 0, 'gt');
        $this->searchCriteriaBuilder->addFilter(
            ProjectAttributeSchema::STATUS, ProjectAttributeInterface::STATUS_EXPORTED
        );
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResult = $this->projectEntityRepository->getList($searchCriteria);

        $projectEntities = $searchResult->getItems();

        foreach ($projectEntities as $projectEntity) {
            /** @var $projectEntity ProjectAttributeInterface */
            $lastError = '';

            $itemExtId      = $projectEntity->getExtId();
            $entityTypeCode = $projectEntity->getEavEntityType();
            $attributeCode  = $projectEntity->getAttributeCode();

            try {
                $attribute = $this->attributeRepository->get($entityTypeCode, $attributeCode);

                $itemRequest = new ItemGetRequest($projectExtId, $itemExtId);

                $itemGetResponse = $this->itemApi->get($itemRequest);

                $this->attributeItemGetMapper->map($itemGetResponse, $attribute, $storeId);

                $this->attributeRepository->save($attribute);

                $options = $attribute->getOptions();
                if (is_array($options)) {
                    foreach ($options as $option) {
                        $this->attributeOptionManagement->add($entityTypeCode, $attributeCode, $option);
                    }
                }

                $status = ProjectAttributeInterface::STATUS_IMPORTED;

                $this->logger->info(sprintf('attribute %s, ext-id:%d => success', $attributeCode, $itemExtId));
            } catch (GuzzleException $e) {
                $status    = ProjectAttributeInterface::STATUS_ERROR;
                $lastError = $e->getMessage();
                $this->logger->error(sprintf('attribute %s => %s', $attributeCode, $lastError));
                $result = false;
            } catch (\Exception $e) {
                $status    = ProjectAttributeInterface::STATUS_ERROR;
                $lastError = $e->getMessage();
                $this->logger->error(sprintf('attribute %s => %s', $attributeCode, $lastError));
                $result = false;
            }

            $projectEntity->setStatus($status);
            $projectEntity->setLastError($lastError);
            $this->projectEntityRepository->save($projectEntity);
        }

        return $result;
    }

}
