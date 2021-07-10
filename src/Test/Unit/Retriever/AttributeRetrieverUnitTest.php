<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Test\Integration\Retriever;

use Eurotext\RestApiClient\Api\Project\ItemV1ApiInterface;
use Eurotext\TranslationManager\Test\Builder\ProjectMockBuilder;
use Eurotext\TranslationManagerEav\Api\Data\ProjectAttributeInterface;
use Eurotext\TranslationManagerEav\Api\ProjectAttributeRepositoryInterface;
use Eurotext\TranslationManagerEav\Repository\ProjectAttributeRepository;
use Eurotext\TranslationManagerEav\Retriever\AttributeRetriever;
use Eurotext\TranslationManagerEav\Test\Unit\UnitTestAbstract;
use GuzzleHttp\Exception\TransferException;
use Magento\Eav\Api\AttributeOptionUpdateInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;

class AttributeRetrieverUnitTest extends UnitTestAbstract
{
    /** @var AttributeRetriever */
    private $sut;

    /** @var ProjectMockBuilder */
    private $projectMockBuilder;

    /** @var ProjectAttributeRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $projectAttributeRepository;

    /** @var SearchResultsInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $searchResults;

    /** @var AttributeRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $attributeRepository;

    /** @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $searchCriteriaBuilder;

    /** @var AttributeOptionUpdateInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $attributeOptionUpdate;

    /** @var ItemV1ApiInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $itemApi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->itemApi = $this->createMock(ItemV1ApiInterface::class);

        $this->projectAttributeRepository = $this->createMock(ProjectAttributeRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaBuilder->method('create')->willReturn(new SearchCriteria());

        $this->searchResults = $this->createMock(SearchResultsInterface::class);
        $this->attributeRepository = $this->createMock(AttributeRepositoryInterface::class);

        $this->attributeOptionUpdate = $this->createMock(AttributeOptionUpdateInterface::class);

        $this->projectMockBuilder = new ProjectMockBuilder($this);

        $this->sut = $this->objectManager->getObject(
            AttributeRetriever::class,
            [
                'itemApi' => $this->itemApi,
                'projectEntityRepository' => $this->projectAttributeRepository,
                'attributeRepository' => $this->attributeRepository,
                'attributeOptionUpdate' => $this->attributeOptionUpdate,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
            ]
        );
    }

    public function testItShouldRetrieveProjectAttributes()
    {
        $storeId = 1;
        $extId = 2423;
        $attributeCode = 'some_attribute_code';
        $entityTypeCode = 'catalog_product';
        $status = ProjectAttributeInterface::STATUS_IMPORTED;
        $lastError = '';

        $project = $this->projectMockBuilder->buildProjectMock();
        $project->method('getStoreviewDst')->willReturn($storeId);

        $projectAttribute = $this->createMock(ProjectAttributeInterface::class);
        $projectAttribute->expects($this->once())->method('setStatus')->with($status);
        $projectAttribute->expects($this->once())->method('setLastError')->with($lastError);
        $projectAttribute->expects($this->once())->method('getExtId')->willReturn($extId);
        $projectAttribute->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $projectAttribute->expects($this->once())->method('getEavEntityType')->willReturn($entityTypeCode);

        $this->projectAttributeRepository->expects($this->once())->method('getList')->willReturn($this->searchResults);
        $this->projectAttributeRepository->expects($this->once())->method('save')->with($projectAttribute);

        $this->searchResults->expects($this->once())->method('getItems')->willReturn([$projectAttribute]);

        $attribute = $this->createMock(AttributeInterface::class);
        $this->attributeRepository->expects($this->once())->method('get')
                                  ->with($entityTypeCode, $attributeCode)
                                  ->willReturn($attribute);

        $this->attributeOptionUpdate->expects($this->never())->method('update');

        // Retrieve Project from Eurotext
        $result = $this->sut->retrieve($project);

        $this->assertTrue($result);
    }

    public function testItShouldRetrieveProjectAttributesWithOptions()
    {
        $storeId = 1;
        $extId = 2423;
        $attributeCode = 'some_attribute_code';
        $entityTypeCode = 'catalog_product';
        $status = ProjectAttributeInterface::STATUS_IMPORTED;
        $lastError = '';
        $optionId = 3985;
        $optionLabel = 'Foobar';

        $project = $this->projectMockBuilder->buildProjectMock();
        $project->method('getStoreviewDst')->willReturn($storeId);

        $projectAttribute = $this->createMock(ProjectAttributeInterface::class);
        $projectAttribute->expects($this->once())->method('setStatus')->with($status);
        $projectAttribute->expects($this->once())->method('setLastError')->with($lastError);
        $projectAttribute->expects($this->once())->method('getExtId')->willReturn($extId);
        $projectAttribute->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $projectAttribute->expects($this->once())->method('getEavEntityType')->willReturn($entityTypeCode);

        $this->projectAttributeRepository->expects($this->once())->method('getList')->willReturn($this->searchResults);
        $this->projectAttributeRepository->expects($this->once())->method('save')->with($projectAttribute);

        $this->searchResults->expects($this->once())->method('getItems')->willReturn([$projectAttribute]);

        $optionEmpty = $this->createOptionMock(111, '');
        $option = $this->createOptionMock($optionId, $optionLabel);

        $attribute = $this->createMock(AttributeInterface::class);
        $attribute->expects($this->once())->method('getOptions')->willReturn([$optionEmpty, $option]);

        $this->attributeRepository->expects($this->once())->method('get')
                                  ->with($entityTypeCode, $attributeCode)
                                  ->willReturn($attribute);

        $this->attributeOptionUpdate->expects($this->once())->method('update')
                                    ->with($entityTypeCode, $attributeCode, $optionId, $option);

        // Retrieve Project from Eurotext
        $result = $this->sut->retrieve($project);

        $this->assertTrue($result);
    }

    public function testItShouldSetLastErrorForGuzzleException()
    {
        $lastError = 'The Message from the exception that occured';
        $apiException = new TransferException($lastError);

        $this->runTestExceptionsAreHandledCorrectly($apiException);
    }

    public function testItShouldSetLastErrorForException()
    {
        $lastError = 'The Message from the exception that occured';
        $apiException = new \Exception($lastError);

        $this->runTestExceptionsAreHandledCorrectly($apiException);
    }

    private function runTestExceptionsAreHandledCorrectly(\Exception $apiException)
    {
        $storeId = 1;
        $extId = 2423;
        $attributeCode = 'some_attribute_code';
        $entityTypeCode = 'catalog_product';
        $status = ProjectAttributeInterface::STATUS_ERROR;

        $project = $this->projectMockBuilder->buildProjectMock();
        $project->method('getStoreviewDst')->willReturn($storeId);

        $projectAttribute = $this->createMock(ProjectAttributeInterface::class);
        $projectAttribute->expects($this->once())->method('setStatus')->with($status);
        $projectAttribute->expects($this->once())->method('getExtId')->willReturn($extId);
        $projectAttribute->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $projectAttribute->expects($this->once())->method('getEavEntityType')->willReturn($entityTypeCode);
        $projectAttribute->expects($this->once())->method('setLastError')->with($apiException->getMessage());

        $this->projectAttributeRepository->expects($this->once())->method('getList')->willReturn($this->searchResults);
        $this->projectAttributeRepository->expects($this->once())->method('save')->with($projectAttribute);

        $this->searchResults->expects($this->once())->method('getItems')->willReturn([$projectAttribute]);

        $attribute = $this->createMock(AttributeInterface::class);
        $this->attributeRepository->expects($this->once())->method('get')
                                  ->with($entityTypeCode, $attributeCode)
                                  ->willReturn($attribute);

        $this->itemApi->method('get')->willThrowException($apiException);

        // Retrieve Project from Eurotext
        $result = $this->sut->retrieve($project);

        $this->assertFalse($result);
    }

    private function createOptionMock(int $optionId, string $optionLabel)
    {
        $count = $optionLabel === '' ? $this->any() : $this->once();

        $option = $this->createMock(AttributeOptionInterface::class);
        $option->expects($count)->method('getValue')->willReturn($optionId);
        $option->expects($this->once())->method('getLabel')->willReturn($optionLabel);

        return $option;
    }
}
