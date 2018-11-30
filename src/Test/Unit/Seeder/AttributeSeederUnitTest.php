<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Test\Unit\Seeder;

use Eurotext\TranslationManager\Test\Builder\ProjectMockBuilder;
use Eurotext\TranslationManagerEav\Api\ProjectAttributeRepositoryInterface;
use Eurotext\TranslationManagerEav\Model\ProjectAttributeFactory;
use Eurotext\TranslationManagerEav\Seeder\AttributeSeeder;
use Eurotext\TranslationManagerEav\Test\Unit\UnitTestAbstract;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeSearchResultsInterface;
use Magento\Eav\Model\Entity\Type as EntityType;
use Magento\Eav\Model\ResourceModel\Entity\Type\Collection as EntityTypeCollection;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;

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

        $this->attributeRepository->expects($this->once())->method('getList')->willReturn($attributeResult);

        $project = $this->projectBuilder->buildProjectMock();

        $result = $this->sut->seed($project);

        $this->assertTrue($result);
    }

}