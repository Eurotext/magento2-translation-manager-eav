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

        $this->entityTypeCollection       = $this->createMock(EntityTypeCollection::class);
        $this->attributeRepository        = $this->createMock(AttributeRepositoryInterface::class);
        $this->projectAttributeFactory    = $this->createMock(ProjectAttributeFactory::class);
        $this->projectAttributeRepository = $this->createMock(ProjectAttributeRepositoryInterface::class);
        $this->searchCriteriaBuilder      = $this->createMock(SearchCriteriaBuilder::class);

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
        $attributeCode        = 'some-attribute-code';
        $pAttributesCount     = 0;

        $entityType = $this->createMock(EntityType::class);
        $entityType->expects($this->once())->method('getEntityTypeCode')->willReturn('hans_dampf');

        $this->entityTypeCollection->expects($this->once())->method('getItems')->willReturn([$entityType]);

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            ['is_user_defined', 1],
            [ProjectAttributeSchema::ENTITY_ID, $attributeId],
            [ProjectAttributeSchema::PROJECT_ID, $projectId]
        )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(2))
                                    ->method('create')
                                    ->willReturnOnConsecutiveCalls(new SearchCriteria(), new SearchCriteria());

        // Attributes
        $attribute = $this->createMock(AttributeInterface::class);
        $attribute->expects($this->once())->method('getAttributeId')->willReturn($attributeId);
        $attribute->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);

        $attributes = [$attribute];

        $attributeResult = $this->createMock(AttributeSearchResultsInterface::class);
        $attributeResult->expects($this->once())->method('getTotalCount')->willReturn($attributesTotalCount);
        $attributeResult->expects($this->once())->method('getItems')->willReturn($attributes);

        $this->attributeRepository->expects($this->once())->method('getList')->willReturn($attributeResult);

        // Search existing project entity
        $pAttributesResult = $this->createMock(SearchResultsInterface::class);
        $pAttributesResult->expects($this->once())->method('getTotalCount')->willReturn($pAttributesCount);

        $this->projectAttributeRepository->expects($this->once())->method('getList')->willReturn($pAttributesResult);
        $this->projectAttributeRepository->expects($this->once())->method('save');

        // New project entity
        $pAttribute = $this->createMock(ProjectAttributeInterface::class);
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

        $entityType = $this->createMock(EntityType::class);
        $entityType->expects($this->once())->method('getEntityTypeCode')->willReturn('hans_dampf');

        $this->entityTypeCollection->expects($this->once())->method('getItems')->willReturn([$entityType]);

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            ['is_user_defined', 1],
            [ProjectAttributeSchema::ENTITY_ID, $attributeId],
            [ProjectAttributeSchema::PROJECT_ID, $projectId]
        )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(2))
                                    ->method('create')
                                    ->willReturnOnConsecutiveCalls(new SearchCriteria(), new SearchCriteria());

        // Attributes
        $attribute = $this->createMock(AttributeInterface::class);
        $attribute->expects($this->once())->method('getAttributeId')->willReturn($attributeId);
        $attribute->expects($this->never())->method('getAttributeCode');

        $attributes = [$attribute];

        $attributeResult = $this->createMock(AttributeSearchResultsInterface::class);
        $attributeResult->expects($this->once())->method('getTotalCount')->willReturn($attributesTotalCount);
        $attributeResult->expects($this->once())->method('getItems')->willReturn($attributes);

        $this->attributeRepository->expects($this->once())->method('getList')->willReturn($attributeResult);

        // Search existing project entity
        $pAttributesResult = $this->createMock(SearchResultsInterface::class);
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
        $attributeCode        = 'some-attribute-code';
        $pAttributesCount     = 0;

        $entityType = $this->createMock(EntityType::class);
        $entityType->expects($this->once())->method('getEntityTypeCode')->willReturn('hans_dampf');

        $this->entityTypeCollection->expects($this->once())->method('getItems')->willReturn([$entityType]);

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            ['is_user_defined', 1],
            [ProjectAttributeSchema::ENTITY_ID, $attributeId],
            [ProjectAttributeSchema::PROJECT_ID, $projectId]
        )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(2))
                                    ->method('create')
                                    ->willReturnOnConsecutiveCalls(new SearchCriteria(), new SearchCriteria());

        // Attributes
        $attribute = $this->createMock(AttributeInterface::class);
        $attribute->expects($this->once())->method('getAttributeId')->willReturn($attributeId);
        $attribute->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);

        $attributes = [$attribute];

        $attributeResult = $this->createMock(AttributeSearchResultsInterface::class);
        $attributeResult->expects($this->once())->method('getTotalCount')->willReturn($attributesTotalCount);
        $attributeResult->expects($this->once())->method('getItems')->willReturn($attributes);

        $this->attributeRepository->expects($this->once())->method('getList')->willReturn($attributeResult);

        // Search existing project entity
        $pAttributesResult = $this->createMock(SearchResultsInterface::class);
        $pAttributesResult->expects($this->once())->method('getTotalCount')->willReturn($pAttributesCount);

        $this->projectAttributeRepository->expects($this->once())->method('getList')->willReturn($pAttributesResult);
        $this->projectAttributeRepository->expects($this->once())->method('save')->willThrowException(new \Exception);

        // New project entity
        $pAttribute = $this->createMock(ProjectAttributeInterface::class);
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

        $entityType = $this->createMock(EntityType::class);
        $entityType->expects($this->once())->method('getEntityTypeCode')->willReturn('hans_dampf');

        $this->entityTypeCollection->expects($this->once())->method('getItems')->willReturn([$entityType]);

        $attributeSearch = new SearchCriteria();

        $this->searchCriteriaBuilder
            ->expects($this->once())
            ->method('addFilter')
            ->with('is_user_defined', 1)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($attributeSearch);

        $attributeResult = $this->createMock(AttributeSearchResultsInterface::class);
        $attributeResult->expects($this->once())->method('getTotalCount')->willReturn($attributesTotalCount);
        $attributeResult->expects($this->never())->method('getItems');

        $this->attributeRepository->expects($this->once())->method('getList')->willReturn($attributeResult);

        $project = $this->projectBuilder->buildProjectMock();

        $result = $this->sut->seed($project);

        $this->assertTrue($result);
    }

    public function testItShouldAddEntitiesFilter()
    {
        $entity   = 'some-entity';
        $entities = [$entity];

        $attributesTotalCount = 0;

        $entityType = $this->createMock(EntityType::class);
        $entityType->expects($this->once())->method('getEntityTypeCode')->willReturn('hans_dampf');

        $this->entityTypeCollection->expects($this->once())->method('getItems')->willReturn([$entityType]);

        $attributeSearch = new SearchCriteria();

        $this->searchCriteriaBuilder
            ->method('addFilter')
            ->withConsecutive(
                ['is_user_defined', 1],
                ['attribute_code', $entities, 'in']
            )->willReturnSelf();

        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($attributeSearch);

        $attributeResult = $this->createMock(AttributeSearchResultsInterface::class);
        $attributeResult->expects($this->once())->method('getTotalCount')->willReturn($attributesTotalCount);
        $attributeResult->expects($this->never())->method('getItems');

        $this->attributeRepository->expects($this->once())->method('getList')->willReturn($attributeResult);

        $project = $this->projectBuilder->buildProjectMock();

        $result = $this->sut->seed($project, $entities);

        $this->assertTrue($result);
    }

}