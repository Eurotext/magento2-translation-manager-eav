<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Ui\EntityDataLoader;

use Eurotext\TranslationManager\Api\EntityDataLoaderInterface;
use Eurotext\TranslationManagerEav\Api\ProjectAttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class AttributeEntityDataLoader implements EntityDataLoaderInterface
{
    /**
     * @var ProjectAttributeRepositoryInterface
     */
    private $projectAttributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct(
        ProjectAttributeRepositoryInterface $projectAttributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->projectAttributeRepository = $projectAttributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function load(int $projectId, array &$data): bool
    {
        $this->searchCriteriaBuilder->addFilter('project_id', $projectId);

        $searchCriteria = $this->searchCriteriaBuilder->create();

        $projectAttributes = $this->projectAttributeRepository->getList($searchCriteria);

        // isActive has to be a string otherwise UI component does not accept this value
        $isActive = $projectAttributes->getTotalCount() > 0 ? '1' : '0';

        $data['attributes'] = ['is_active' => $isActive];

        return true;
    }
}