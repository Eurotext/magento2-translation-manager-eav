<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Test\Integration\Sender;

use Eurotext\RestApiClient\Api\Project\ItemV1Api;
use Eurotext\RestApiClient\Request\Project\ItemPostRequest;
use Eurotext\RestApiClient\Response\Project\ItemPostResponse;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManagerEav\Api\Data\ProjectAttributeInterface;
use Eurotext\TranslationManagerEav\Api\ProjectAttributeRepositoryInterface;
use Eurotext\TranslationManagerEav\Mapper\AttributeItemPostMapper;
use Eurotext\TranslationManagerEav\Sender\AttributeSender;
use Eurotext\TranslationManagerEav\Test\Unit\UnitTestAbstract;
use GuzzleHttp\Exception\RequestException;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class AttributeSenderUnitTest extends UnitTestAbstract
{
    /** @var AttributeSender */
    private $sut;

    /** @var ProjectAttributeRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $projectAttributeRepository;

    /** @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $searchCriteriaBuilder;

    /** @var AttributeRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $attributeRepository;

    /** @var AttributeItemPostMapper|\PHPUnit_Framework_MockObject_MockObject */
    private $attributeItemPostMapper;

    /** @var ItemV1Api|\PHPUnit_Framework_MockObject_MockObject */
    private $itemApi;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    protected function setUp()
    {
        parent::setUp();

        $this->itemApi = $this->createMock(ItemV1Api::class);

        $this->projectAttributeRepository = $this->createMock(ProjectAttributeRepositoryInterface::class);

        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaBuilder->method('create')->willReturn(new SearchCriteria());

        $this->attributeRepository = $this->createMock(AttributeRepositoryInterface::class);

        $this->attributeItemPostMapper = $this->createMock(AttributeItemPostMapper::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->sut = $this->objectManager->getObject(
            AttributeSender::class,
            [
                'itemApi'                    => $this->itemApi,
                'projectAttributeRepository' => $this->projectAttributeRepository,
                'searchCriteriaBuilder'      => $this->searchCriteriaBuilder,
                'attributeRepository'        => $this->attributeRepository,
                'itemPostMapper'             => $this->attributeItemPostMapper,
                'logger'                     => $this->logger,
            ]
        );

    }

    public function testItShouldSendProjectEntities()
    {
        $extIdSaved    = 0;
        $extIdNew      = 12345;
        $attributeCode = 'etm_integration_tests_1';
        $eavEntityType = 'catalog_product';

        $projectEntity = $this->createMock(ProjectAttributeInterface::class);
        $projectEntity->expects($this->once())->method('getExtId')->willReturn($extIdSaved);
        $projectEntity->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $projectEntity->expects($this->once())->method('getEavEntityType')->willReturn($eavEntityType);
        $projectEntity->expects($this->once())->method('setExtId')->with($extIdNew);
        $projectEntity->expects($this->once())->method('setStatus')->with(ProjectAttributeInterface::STATUS_EXPORTED);

        $searchResultItems = [$projectEntity];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getItems')->willReturn($searchResultItems);
        $this->projectAttributeRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $attribute = $this->createMock(AttributeInterface::class);
        $this->attributeRepository->expects($this->once())->method('get')
                                  ->with($eavEntityType, $attributeCode)
                                  ->willReturn($attribute);

        $itemRequest = $this->createMock(ItemPostRequest::class);
        $this->attributeItemPostMapper->expects($this->once())->method('map')->willReturn($itemRequest);

        $itemPostResponse = $this->createMock(ItemPostResponse::class);
        $itemPostResponse->expects($this->once())->method('getId')->willReturn($extIdNew);
        $this->itemApi->expects($this->once())->method('post')->with($itemRequest)->willReturn($itemPostResponse);

        $this->projectAttributeRepository->expects($this->once())->method('save')->with($projectEntity);

        $project = $this->createMock(ProjectInterface::class);
        $project->method('getId')->willReturn(123);
        /** @var ProjectInterface $project */

        $result = $this->sut->send($project);

        $this->assertTrue($result);
    }

    public function testItShouldNoSendIfEntityHasExtId()
    {
        $extIdSaved = 12345;

        $projectEntity = $this->createMock(ProjectAttributeInterface::class);
        $projectEntity->expects($this->once())->method('getExtId')->willReturn($extIdSaved);
        $projectEntity->expects($this->never())->method('getAttributeCode');
        $projectEntity->expects($this->never())->method('getEavEntityType');
        $projectEntity->expects($this->never())->method('setExtId');
        $projectEntity->expects($this->never())->method('setStatus');

        $searchResultItems = [$projectEntity];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getItems')->willReturn($searchResultItems);
        $this->projectAttributeRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $this->attributeRepository->expects($this->never())->method('get');
        $this->attributeItemPostMapper->expects($this->never())->method('map');
        $this->itemApi->expects($this->never())->method('post');
        $this->projectAttributeRepository->expects($this->never())->method('save');

        $project = $this->createMock(ProjectInterface::class);
        $project->method('getId')->willReturn(123);
        /** @var ProjectInterface $project */

        $result = $this->sut->send($project);

        $this->assertTrue($result);
    }

    public function testItShouldCatchExceptionIfAttributeIsNotFound()
    {
        $extIdSaved    = 0;
        $attributeCode = 'etm_integration_tests_1';
        $eavEntityType = 'catalog_product';

        $projectEntity = $this->createMock(ProjectAttributeInterface::class);
        $projectEntity->expects($this->once())->method('getExtId')->willReturn($extIdSaved);
        $projectEntity->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $projectEntity->expects($this->once())->method('getEavEntityType')->willReturn($eavEntityType);
        $projectEntity->expects($this->never())->method('setExtId');
        $projectEntity->expects($this->never())->method('setStatus');

        $searchResultItems = [$projectEntity];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getItems')->willReturn($searchResultItems);
        $this->projectAttributeRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $this->attributeRepository->expects($this->once())->method('get')
                                  ->with($eavEntityType, $attributeCode)
                                  ->willThrowException(new NoSuchEntityException());

        $this->attributeItemPostMapper->expects($this->never())->method('map');
        $this->itemApi->expects($this->never())->method('post');
        $this->projectAttributeRepository->expects($this->never())->method('save');

        $this->logger->expects($this->once())->method('error');

        $project = $this->createMock(ProjectInterface::class);
        $project->method('getId')->willReturn(123);
        /** @var ProjectInterface $project */

        $result = $this->sut->send($project);

        $this->assertFalse($result);
    }

    public function testItShouldCatchExceptionFromTheApi()
    {
        $extIdSaved    = 0;
        $extIdNew      = 12345;
        $attributeCode = 'etm_integration_tests_1';
        $eavEntityType = 'catalog_product';

        $projectEntity = $this->createMock(ProjectAttributeInterface::class);
        $projectEntity->expects($this->once())->method('getExtId')->willReturn($extIdSaved);
        $projectEntity->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $projectEntity->expects($this->once())->method('getEavEntityType')->willReturn($eavEntityType);
        $projectEntity->expects($this->never())->method('setExtId');
        $projectEntity->expects($this->never())->method('setStatus');

        $searchResultItems = [$projectEntity];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getItems')->willReturn($searchResultItems);
        $this->projectAttributeRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $attribute = $this->createMock(AttributeInterface::class);
        $this->attributeRepository->expects($this->once())->method('get')
                                  ->with($eavEntityType, $attributeCode)
                                  ->willReturn($attribute);

        $itemRequest = $this->createMock(ItemPostRequest::class);
        $this->attributeItemPostMapper->expects($this->once())->method('map')->willReturn($itemRequest);

        $itemPostResponse = $this->createMock(ItemPostResponse::class);
        $itemPostResponse->expects($this->never())->method('getId');
        $exception = $this->createMock(RequestException::class);
        $this->itemApi->expects($this->once())->method('post')->with($itemRequest)->willThrowException($exception);

        $this->projectAttributeRepository->expects($this->never())->method('save');

        $this->logger->expects($this->once())->method('error');

        $project = $this->createMock(ProjectInterface::class);
        $project->method('getId')->willReturn(123);
        /** @var ProjectInterface $project */

        $result = $this->sut->send($project);

        $this->assertFalse($result);
    }

    public function testItShouldCatchExceptionWhileSavingTheAttribute()
    {
        $extIdSaved    = 0;
        $extIdNew      = 12345;
        $attributeCode = 'etm_integration_tests_1';
        $eavEntityType = 'catalog_product';

        $projectEntity = $this->createMock(ProjectAttributeInterface::class);
        $projectEntity->expects($this->once())->method('getExtId')->willReturn($extIdSaved);
        $projectEntity->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $projectEntity->expects($this->once())->method('getEavEntityType')->willReturn($eavEntityType);
        $projectEntity->expects($this->once())->method('setExtId')->with($extIdNew);
        $projectEntity->expects($this->once())->method('setStatus')->with(ProjectAttributeInterface::STATUS_EXPORTED);

        $searchResultItems = [$projectEntity];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getItems')->willReturn($searchResultItems);
        $this->projectAttributeRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $attribute = $this->createMock(AttributeInterface::class);
        $this->attributeRepository->expects($this->once())->method('get')
                                  ->with($eavEntityType, $attributeCode)
                                  ->willReturn($attribute);

        $itemRequest = $this->createMock(ItemPostRequest::class);
        $this->attributeItemPostMapper->expects($this->once())->method('map')->willReturn($itemRequest);

        $itemPostResponse = $this->createMock(ItemPostResponse::class);
        $itemPostResponse->expects($this->once())->method('getId')->willReturn($extIdNew);
        $this->itemApi->expects($this->once())->method('post')->with($itemRequest)->willReturn($itemPostResponse);

        $this->projectAttributeRepository->expects($this->once())->method('save')
                                         ->with($projectEntity)->willThrowException(new \Exception());

        $this->logger->expects($this->once())->method('error');

        $project = $this->createMock(ProjectInterface::class);
        $project->method('getId')->willReturn(123);
        /** @var ProjectInterface $project */

        $result = $this->sut->send($project);

        $this->assertFalse($result);
    }
}
