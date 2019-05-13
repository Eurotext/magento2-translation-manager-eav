<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Service;

use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManagerEav\Api\Data\ProjectAttributeInterface;
use Eurotext\TranslationManagerEav\Api\ProjectAttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class CleanProjectAttributesService
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

    public function clean(ProjectInterface $project): bool
    {
        $this->searchCriteriaBuilder->addFilter('project_id', $project->getId());

        $searchCriteria = $this->searchCriteriaBuilder->create();

        $projectAttributes = $this->projectAttributeRepository->getList($searchCriteria);

        if ($projectAttributes->getTotalCount() === 0) {
            return false;
        }

        foreach ($projectAttributes->getItems() as $projectAttribute) {
            /** @var $projectAttribute ProjectAttributeInterface */
            $this->projectAttributeRepository->delete($projectAttribute);
        }

        return true;
    }
}