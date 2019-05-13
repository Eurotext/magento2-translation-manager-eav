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
use Magento\Eav\Api\AttributeOptionManagementInterface;
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

    /** @var AttributeOptionManagementInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $attributeOptionManagement;

    /** @var ItemV1ApiInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $itemApi;

    protected function setUp()
    {
        parent::setUp();

        $this->itemApi = $this->createMock(ItemV1ApiInterface::class);

        $this->projectAttributeRepository = $this->createMock(ProjectAttributeRepositoryInterface::class);
        $this->searchCriteriaBuilder      = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaBuilder->method('create')->willReturn(new SearchCriteria());

        $this->searchResults       = $this->createMock(SearchResultsInterface::class);
        $this->attributeRepository = $this->createMock(AttributeRepositoryInterface::class);

        $this->attributeOptionManagement = $this->createMock(AttributeOptionManagementInterface::class);

        $this->projectMockBuilder = new ProjectMockBuilder($this);

        $this->sut = $this->objectManager->getObject(
            AttributeRetriever::class,
            [
                'itemApi'                   => $this->itemApi,
                'projectEntityRepository'   => $this->projectAttributeRepository,
                'attributeRepository'       => $this->attributeRepository,
                'attributeOptionManagement' => $this->attributeOptionManagement,
                'searchCriteriaBuilder'     => $this->searchCriteriaBuilder,
            ]
        );
    }

    public function testItShouldRetrieveProjectAttributes()
    {
        $storeId        = 1;
        $extId          = 2423;
        $attributeCode  = 'some_attribute_code';
        $entityTypeCode = 'catalog_product';
        $status         = ProjectAttributeInterface::STATUS_IMPORTED;
        $lastError      = '';

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

        $this->attributeOptionManagement->expects($this->never())->method('add');

        // Retrieve Project from Eurotext
        $result = $this->sut->retrieve($project);

        $this->assertTrue($result);
    }

    public function testItShouldRetrieveProjectAttributesWithOptions()
    {
        $storeId        = 1;
        $extId          = 2423;
        $attributeCode  = 'some_attribute_code';
        $entityTypeCode = 'catalog_product';
        $status         = ProjectAttributeInterface::STATUS_IMPORTED;
        $lastError      = '';

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

        $option = $this->createMock(AttributeOptionInterface::class);

        $attribute = $this->createMock(AttributeInterface::class);
        $attribute->expects($this->once())->method('getOptions')->willReturn([$option]);

        $this->attributeRepository->expects($this->once())->method('get')
                                  ->with($entityTypeCode, $attributeCode)
                                  ->willReturn($attribute);

        $this->attributeOptionManagement->expects($this->once())->method('add')
                                        ->with($entityTypeCode, $attributeCode, $option);

        // Retrieve Project from Eurotext
        $result = $this->sut->retrieve($project);

        $this->assertTrue($result);
    }

    public function testItShouldSetLastErrorForGuzzleException()
    {
        $lastError    = 'The Message from the exception that occured';
        $apiException = new TransferException($lastError);

        $this->runTestExceptionsAreHandledCorrectly($apiException);
    }

    public function testItShouldSetLastErrorForException()
    {
        $lastError    = 'The Message from the exception that occured';
        $apiException = new \Exception($lastError);

        $this->runTestExceptionsAreHandledCorrectly($apiException);
    }

    private function runTestExceptionsAreHandledCorrectly(\Exception $apiException)
    {
        $storeId        = 1;
        $extId          = 2423;
        $attributeCode  = 'some_attribute_code';
        $entityTypeCode = 'catalog_product';
        $status         = ProjectAttributeInterface::STATUS_ERROR;

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
}
