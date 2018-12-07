<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Test\Unit\Seeder;

use Eurotext\TranslationManager\Test\Builder\ProjectMockBuilder;
use Eurotext\TranslationManagerEav\Api\Data\ProjectAttributeInterface;
use Eurotext\TranslationManagerEav\Api\ProjectAttributeRepositoryInterface;
use Eurotext\TranslationManagerEav\Model\ProjectAttributeFactory;
use Eurotext\TranslationManagerEav\Seeder\AttributeSeeder;
use Eurotext\TranslationManagerEav\Setup\ProjectAttributeSchema;
use Eurotext\TranslationManagerEav\Test\Unit\UnitTestAbstract;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeSearchResultsInterface;
use Magento\Eav\Model\Entity\Type as EntityType;
use Magento\Eav\Model\ResourceModel\Entity\Type\Collection as EntityTypeCollection;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;

class AttributeSeederUnitTest extends UnitTestAbstract
{
    /** @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $searchCriteriaBuilder;

    /** @var ProjectAttributeRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $projectAttributeRepository;

    /** @var ProjectAttributeFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $projectAttributeFactory;

    /** @var AttributeRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $attributeRepository;

    /** @var EntityTypeCollection|\PHPUnit_Framework_MockObject_MockObject */
    private $entityTypeCollection;

    /** @var ProjectMockBuilder */
    private $projectBuilder;

    /** @var AttributeSeeder */
    private $sut;

    protected function setUp()
    {
        parent::setUp();

        $this->projectBuilder = new ProjectMockBuilder($this);

        $this->entityTypeCollection =
            $this->getMockBuilder(EntityTypeCollection::class)
                 ->disableOriginalConstructor()
                 ->setMethods(['load', 'getItems'])
                 ->getMock();

        $this->attributeRepository =
            $this->getMockBuilder(AttributeRepositoryInterface::class)
                 ->setMethods(['getList'])
                 ->getMockForAbstractClass();

        $this->projectAttributeFactory =
            $this->getMockBuilder(ProjectAttributeFactory::class)
                 ->disableOriginalConstructor()
                 ->setMethods(['create'])
                 ->getMock();

        $this->projectAttributeRepository =
            $this->getMockBuilder(ProjectAttributeRepositoryInterface::class)
                 ->setMethods(['getList', 'save'])
                 ->getMockForAbstractClass();

        $this->searchCriteriaBuilder =
            $this->getMockBuilder(SearchCriteriaBuilder::class)
                 ->disableOriginalConstructor()
                 ->setMethods(['create', 'addFilter'])
                 ->getMock();

        $this->sut = $this->objectManager->getObject(
            AttributeSeeder::class, [

                'entityTypeCollection'       => $this->entityTypeCollection,
                'attributeRepository'        => $this->attributeRepository,
                'projectAttributeFactory'    => $this->projectAttributeFactory,
                'projectAttributeRepository' => $this->projectAttributeRepository,
                'searchCriteriaBuilder'      => $this->searchCriteriaBuilder,
            ]
        );
    }

    public function testItShouldSeedAttributes()
    {
        $projectId            = 33;
        $attributesTotalCount = 1;
        $attributeId          = 11;
        $pAttributesCount     = 0;

        $entityType = $this->getMockBuilder(EntityType::class)
                           ->disableOriginalConstructor()
                           ->setMethods(['getEntityTypeCode'])
                           ->getMock();
        $entityType->expects($this->once())->method('getEntityTypeCode')->willReturn('hans_dampf');

        $this->entityTypeCollection->expects($this->once())->method('getItems')->willReturn([$entityType]);

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            ['is_user_defined', 1],
            [ProjectAttributeSchema::ENTITY_ID, $attributeId],
            [ProjectAttributeSchema::PROJECT_ID, $projectId]
        );
        $this->searchCriteriaBuilder->expects($this->exactly(2))
                                    ->method('create')
                                    ->willReturnOnConsecutiveCalls(new SearchCriteria(), new SearchCriteria());

        // Attributes
        $attribute = $this->getMockBuilder(AttributeInterface::class)->getMock();
        $attribute->expects($this->once())->method('getAttributeId')->willReturn($attributeId);

        $attributes = [$attribute];

        $attributeResult = $this->getMockBuilder(AttributeSearchResultsInterface::class)->getMockForAbstractClass();
        $attributeResult->expects($this->once())->method('getTotalCount')->willReturn($attributesTotalCount);
        $attributeResult->expects($this->once())->method('getItems')->willReturn($attributes);

        $this->attributeRepository->expects($this->once())->method('getList')->willReturn($attributeResult);

        // Search existing project entity
        $pAttributesResult = $this->getMockBuilder(SearchResultsInterface::class)->getMockForAbstractClass();
        $pAttributesResult->expects($this->once())->method('getTotalCount')->willReturn($pAttributesCount);

        $this->projectAttributeRepository->expects($this->once())->method('getList')->willReturn($pAttributesResult);
        $this->projectAttributeRepository->expects($this->once())->method('save');

        // New project entity
        $pAttribute = $this->getMockBuilder(ProjectAttributeInterface::class)->getMockForAbstractClass();
        $pAttribute->expects($this->once())->method('setProjectId')->with($projectId);
        $pAttribute->expects($this->once())->method('setEntityId')->with($attributeId);
        $pAttribute->expects($this->once())->method('setStatus')->with(ProjectAttributeInterface::STATUS_NEW);

        $this->projectAttributeFactory->expects($this->once())->method('create')->willReturn($pAttribute);

        // project mock
        $project = $this->projectBuilder->buildProjectMock();
        $project->expects($this->once())->method('getId')->willReturn($projectId);

        // TEST
        $result = $this->sut->seed($project);

        $this->assertTrue($result);
    }

    public function testItShouldSkipSeedingIfAttributeIsSeededAlready()
    {
        $projectId            = 33;
        $attributesTotalCount = 1;
        $attributeId          = 11;
        $pAttributesCount     = 1;

        $entityType = $this->getMockBuilder(EntityType::class)
                           ->disableOriginalConstructor()
                           ->setMethods(['getEntityTypeCode'])
                           ->getMock();
        $entityType->expects($this->once())->method('getEntityTypeCode')->willReturn('hans_dampf');

        $this->entityTypeCollection->expects($this->once())->method('getItems')->willReturn([$entityType]);

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            ['is_user_defined', 1],
            [ProjectAttributeSchema::ENTITY_ID, $attributeId],
            [ProjectAttributeSchema::PROJECT_ID, $projectId]
        );
        $this->searchCriteriaBuilder->expects($this->exactly(2))
                                    ->method('create')
                                    ->willReturnOnConsecutiveCalls(new SearchCriteria(), new SearchCriteria());

        // Attributes
        $attribute = $this->getMockBuilder(AttributeInterface::class)->getMock();
        $attribute->expects($this->once())->method('getAttributeId')->willReturn($attributeId);

        $attributes = [$attribute];

        $attributeResult = $this->getMockBuilder(AttributeSearchResultsInterface::class)->getMockForAbstractClass();
        $attributeResult->expects($this->once())->method('getTotalCount')->willReturn($attributesTotalCount);
        $attributeResult->expects($this->once())->method('getItems')->willReturn($attributes);

        $this->attributeRepository->expects($this->once())->method('getList')->willReturn($attributeResult);

        // Search existing project entity
        $pAttributesResult = $this->getMockBuilder(SearchResultsInterface::class)->getMockForAbstractClass();
        $pAttributesResult->expects($this->once())->method('getTotalCount')->willReturn($pAttributesCount);

        $this->projectAttributeRepository->expects($this->once())->method('getList')->willReturn($pAttributesResult);
        $this->projectAttributeRepository->expects($this->never())->method('save');

        $this->projectAttributeFactory->expects($this->never())->method('create');

        // project mock
        $project = $this->projectBuilder->buildProjectMock();
        $project->expects($this->once())->method('getId')->willReturn($projectId);

        // TEST
        $result = $this->sut->seed($project);

        $this->assertTrue($result);
    }

    public function testItShouldCatchExceptionsWhileSaving()
    {
        $projectId            = 33;
        $attributesTotalCount = 1;
        $attributeId          = 11;
        $pAttributesCount     = 0;

        $entityType = $this->getMockBuilder(EntityType::class)
                           ->disableOriginalConstructor()
                           ->setMethods(['getEntityTypeCode'])
                           ->getMock();
        $entityType->expects($this->once())->method('getEntityTypeCode')->willReturn('hans_dampf');

        $this->entityTypeCollection->expects($this->once())->method('getItems')->willReturn([$entityType]);

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            ['is_user_defined', 1],
            [ProjectAttributeSchema::ENTITY_ID, $attributeId],
            [ProjectAttributeSchema::PROJECT_ID, $projectId]
        );
        $this->searchCriteriaBuilder->expects($this->exactly(2))
                                    ->method('create')
                                    ->willReturnOnConsecutiveCalls(new SearchCriteria(), new SearchCriteria());

        // Attributes
        $attribute = $this->getMockBuilder(AttributeInterface::class)->getMock();
        $attribute->expects($this->once())->method('getAttributeId')->willReturn($attributeId);

        $attributes = [$attribute];

        $attributeResult = $this->getMockBuilder(AttributeSearchResultsInterface::class)->getMockForAbstractClass();
        $attributeResult->expects($this->once())->method('getTotalCount')->willReturn($attributesTotalCount);
        $attributeResult->expects($this->once())->method('getItems')->willReturn($attributes);

        $this->attributeRepository->expects($this->once())->method('getList')->willReturn($attributeResult);

        // Search existing project entity
        $pAttributesResult = $this->getMockBuilder(SearchResultsInterface::class)->getMockForAbstractClass();
        $pAttributesResult->expects($this->once())->method('getTotalCount')->willReturn($pAttributesCount);

        $this->projectAttributeRepository->expects($this->once())->method('getList')->willReturn($pAttributesResult);
        $this->projectAttributeRepository->expects($this->once())->method('save')->willThrowException(new \Exception);

        // New project entity
        $pAttribute = $this->getMockBuilder(ProjectAttributeInterface::class)->getMockForAbstractClass();
        $pAttribute->expects($this->once())->method('setProjectId')->with($projectId);
        $pAttribute->expects($this->once())->method('setEntityId')->with($attributeId);
        $pAttribute->expects($this->once())->method('setStatus')->with(ProjectAttributeInterface::STATUS_NEW);

        $this->projectAttributeFactory->expects($this->once())->method('create')->willReturn($pAttribute);

        // project mock
        $project = $this->projectBuilder->buildProjectMock();
        $project->expects($this->once())->method('getId')->willReturn($projectId);

        // TEST
        $result = $this->sut->seed($project);

        $this->assertFalse($result);
    }

    public function testItShouldSkipSeedingIfNoAttributesAreFound()
    {
        $attributesTotalCount = 0;

        $entityType = $this->getMockBuilder(EntityType::class)
                           ->disableOriginalConstructor()
                           ->setMethods(['getEntityTypeCode'])
                           ->getMock();
        $entityType->expects($this->once())->method('getEntityTypeCode')->willReturn('hans_dampf');

        $this->entityTypeCollection->expects($this->once())->method('getItems')->willReturn([$entityType]);

        $attributeSearch = new SearchCriteria();

        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')->with('is_user_defined', 1);
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($attributeSearch);

        $attributeResult = $this->getMockBuilder(AttributeSearchResultsInterface::class)->getMockForAbstractClass();
        $attributeResult->expects($this->once())->method('getTotalCount')->willReturn($attributesTotalCount);
        $attributeResult->expects($this->never())->method('getItems');

        $this->attributeRepository->expects($this->once())->method('getList')->willReturn($attributeResult);

        $project = $this->projectBuilder->buildProjectMock();

        $result = $this->sut->seed($project);

        $this->assertTrue($result);
    }

}