<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Sender;

use Eurotext\RestApiClient\Api\Project\ItemV1ApiInterface;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\EntitySenderInterface;
use Eurotext\TranslationManagerEav\Api\Data\ProjectAttributeInterface;
use Eurotext\TranslationManagerEav\Api\ProjectAttributeRepositoryInterface;
use Eurotext\TranslationManagerEav\Mapper\AttributeItemPostMapper;
use Eurotext\TranslationManagerEav\Setup\ProjectAttributeSchema;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class AttributeSender implements EntitySenderInterface
{
    /**
     * @var ProjectAttributeRepositoryInterface
     */
    private $projectAttributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ItemV1ApiInterface
     */
    private $itemApi;

    /** @var AttributeItemPostMapper AttributeItemPostMapper */
    private $itemPostMapper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    public function __construct(
        ItemV1ApiInterface $itemApi,
        ProjectAttributeRepositoryInterface $projectAttributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepositoryInterface $attributeRepository,
        AttributeItemPostMapper $itemPostMapper,
        LoggerInterface $logger
    ) {
        $this->itemApi                    = $itemApi;
        $this->projectAttributeRepository = $projectAttributeRepository;
        $this->searchCriteriaBuilder      = $searchCriteriaBuilder;
        $this->attributeRepository        = $attributeRepository;
        $this->itemPostMapper             = $itemPostMapper;
        $this->logger                     = $logger;
    }

    public function send(ProjectInterface $project): bool
    {
        $result = true;

        $projectId = $project->getId();

        $this->logger->info(sprintf('send project attributes project-id:%d', $projectId));

        $this->searchCriteriaBuilder->addFilter(ProjectAttributeSchema::PROJECT_ID, $projectId);
        $this->searchCriteriaBuilder->addFilter(ProjectAttributeSchema::EXT_ID, 0);
        $this->searchCriteriaBuilder->addFilter(ProjectAttributeSchema::STATUS, ProjectAttributeInterface::STATUS_NEW);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResult = $this->projectAttributeRepository->getList($searchCriteria);

        /** @var $projectAttributes ProjectAttributeInterface[] */
        $projectAttributes = $searchResult->getItems();

        foreach ($projectAttributes as $projectAttribute) {
            $isEntitySent = $this->sendEntity($project, $projectAttribute);

            $result = $isEntitySent ? $result : false;
        }

        return $result;
    }

    private function sendEntity(ProjectInterface $project, ProjectAttributeInterface $projectAttribute): bool
    {
        $result = true;

        // Skip already transferred attributes
        if ($projectAttribute->getExtId() > 0) {
            return true;
        }

        $attributeCode  = $projectAttribute->getAttributeCode();
        $entityTypeCode = $projectAttribute->getEavEntityType();

        try {
            $attribute = $this->attributeRepository->get($entityTypeCode, $attributeCode);
        } catch (NoSuchEntityException $e) {
            $message = $e->getMessage();
            $this->logger->error(sprintf('attribute %s => %s', $attributeCode, $message));

            return false;
        }

        $itemRequest = $this->itemPostMapper->map($attribute, $project);

        try {
            $response = $this->itemApi->post($itemRequest);

            // save project_attribute ext_id
            $extId = $response->getId();
            $projectAttribute->setExtId($extId);
            $projectAttribute->setStatus(ProjectAttributeInterface::STATUS_EXPORTED);

            $this->projectAttributeRepository->save($projectAttribute);

            $this->logger->info(sprintf('attribute %s, ext-id:%s => success', $attributeCode, $extId));
        } catch (GuzzleException $e) {
            $message = $e->getMessage();
            $this->logger->error(sprintf('attribute %s => %s', $attributeCode, $message));
            $result = false;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->logger->error(sprintf('attribute %s => %s', $attributeCode, $message));
            $result = false;
        }

        return $result;
    }
}
