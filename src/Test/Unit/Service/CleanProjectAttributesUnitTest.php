<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Unit\Service;

use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManagerEav\Api\Data\ProjectAttributeInterface;
use Eurotext\TranslationManagerEav\Api\ProjectAttributeRepositoryInterface;
use Eurotext\TranslationManagerEav\Service\CleanProjectAttributesService;
use Eurotext\TranslationManagerProduct\Test\Unit\UnitTestAbstract;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use PHPUnit\Framework\MockObject\MockObject;

class CleanProjectAttributesUnitTest extends UnitTestAbstract
{
    /** @var CleanProjectAttributesService */
    private $sut;

    /** @var ProjectAttributeRepositoryInterface|MockObject */
    private $projectAttributeRepository;

    /** @var SearchCriteriaBuilder|MockObject */
    private $searchCriteriaBuilder;

    protected function setUp()
    {
        parent::setUp();

        $this->projectAttributeRepository = $this->createMock(ProjectAttributeRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);

        $this->sut = $this->objectManager->getObject(
            CleanProjectAttributesService::class, [
                'projectAttributeRepository' => $this->projectAttributeRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
            ]
        );
    }

    public function testItShouldLoadAttributeIsActiveStatus()
    {
        $project = $this->createMock(ProjectInterface::class);

        $searchCriteria = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $projectAttribute = $this->createMock(ProjectAttributeInterface::class);

        $projectAttributes = [
            $projectAttribute,
        ];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn(1);
        $searchResult->expects($this->once())->method('getItems')->willReturn($projectAttributes);

        $this->projectAttributeRepository->expects($this->once())->method('getList')
                                         ->with($searchCriteria)->willReturn($searchResult);
        $this->projectAttributeRepository->expects($this->once())->method('delete')->with($projectAttribute);

        $result = $this->sut->clean($project);

        $this->assertTrue($result);
    }

}