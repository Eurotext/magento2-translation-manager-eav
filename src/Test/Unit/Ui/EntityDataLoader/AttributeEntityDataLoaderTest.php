<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Unit\Ui\EntityDataLoader;

use Eurotext\TranslationManagerEav\Api\ProjectAttributeRepositoryInterface;
use Eurotext\TranslationManagerEav\Ui\EntityDataLoader\AttributeEntityDataLoader;
use Eurotext\TranslationManagerProduct\Test\Unit\UnitTestAbstract;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use PHPUnit\Framework\MockObject\MockObject;

class AttributeEntityDataLoaderTest extends UnitTestAbstract
{
    /** @var AttributeEntityDataLoader */
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
            AttributeEntityDataLoader::class, [
                'projectAttributeRepository' => $this->projectAttributeRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
            ]
        );
    }

    /**
     * @param int $attributesCount
     * @param string $isActive
     *
     * @dataProvider provideItShouldLoadAttributeIsActiveStatus
     */
    public function testItShouldLoadAttributeIsActiveStatus(int $attributesCount, string $isActive)
    {
        $projectId = 1;
        $data = [];

        $searchCriteria = $this->createMock(SearchCriteria::class);

        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn($attributesCount);

        $this->projectAttributeRepository->expects($this->once())->method('getList')
                                         ->with($searchCriteria)->willReturn($searchResult);

        $result = $this->sut->load($projectId, $data);

        $this->assertTrue($result);

        $this->assertArrayHasKey('attributes', $data);

        $attributes = $data['attributes'];

        $this->assertArrayHasKey('is_active', $attributes);

        $this->assertEquals($isActive, $attributes['is_active']);
    }

    public function provideItShouldLoadAttributeIsActiveStatus(): array
    {
        return [
            'attributes-10' => [
                'attributesCount' => 10,
                'isActive' => '1',
            ],
            'attributes-0' => [
                'attributesCount' => 0,
                'isActive' => '0',
            ],
        ];
    }

}