<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Test\Unit\Mapper;

use Eurotext\RestApiClient\Request\Project\ItemPostRequest;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\ScopeConfigReaderInterface;
use Eurotext\TranslationManagerEav\Mapper\AttributeItemPostMapper;
use Eurotext\TranslationManagerEav\Test\Unit\UnitTestAbstract;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;

class AttributeItemPostMapperUnitTest extends UnitTestAbstract
{
    /** @var ScopeConfigReaderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $scopeConfig;

    /** @var AttributeItemPostMapper */
    private $sut;

    protected function setUp()
    {
        parent::setUp();

        $this->scopeConfig = $this->createMock(ScopeConfigReaderInterface::class);

        $this->sut = $this->objectManager->getObject(
            AttributeItemPostMapper::class,
            [
                'scopeConfigReader' => $this->scopeConfig,
            ]
        );
    }

    public function testMap()
    {
        $projectId = 123;

        $storeViewSrc = 1;
        $storeViewDst = 2;
        $langSrc = 'de_DE';
        $langDst = 'en_US';

        $label = 'some-frontend-label';
        $attributeCode = 'some_attribute_code';
        $entityId = 47;
        $entityTypeId = 11;

        $optionValue = 'option-value-1';
        $optionLabel = 'Option Label 1';

        $optionOne = $this->createMock(AttributeOptionInterface::class);
        $optionOne->expects($this->once())->method('getValue')->willReturn($optionValue);
        $optionOne->expects($this->once())->method('getLabel')->willReturn($optionLabel);

        $options = [$optionOne,];

        /** @var \PHPUnit_Framework_MockObject_MockObject|AttributeInterface $attribute */
        $attribute = $this->createMock(AttributeInterface::class);
        $attribute->expects($this->once())->method('getDefaultFrontendLabel')->willReturn($label);
        $attribute->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $attribute->method('getAttributeId')->willReturn($entityId);
        $attribute->expects($this->once())->method('getEntityTypeId')->willReturn($entityTypeId);
        $attribute->expects($this->once())->method('getOptions')->willReturn($options);

        // Mock Project
        /** @var ProjectInterface|\PHPUnit_Framework_MockObject_MockObject $project */
        $project = $this->createMock(ProjectInterface::class);
        $project->expects($this->once())->method('getExtId')->willReturn($projectId);
        $project->expects($this->once())->method('getStoreviewSrc')->willReturn($storeViewSrc);
        $project->expects($this->once())->method('getStoreviewDst')->willReturn($storeViewDst);

        // Mock ScopeConfig
        $this->scopeConfig->expects($this->exactly(2))
                          ->method('getLocaleForStore')
                          ->willReturnOnConsecutiveCalls($langSrc, $langDst);

        // Execute test
        $request = $this->sut->map($attribute, $project);

        // ASSERT
        $this->assertInstanceOf(ItemPostRequest::class, $request);

        $this->assertEquals($projectId, $request->getProjectId());
        $this->assertEquals($langSrc, $request->getSource());
        $this->assertEquals($langDst, $request->getTarget());
        $this->assertEquals(AttributeItemPostMapper::ENTITY_TYPE, $request->getTextType());

        $itemData = $request->getData();

        $meta = $itemData->getMeta();
        $this->assertEquals($entityId, $meta['item_id']);
        $this->assertEquals($entityId, $meta['entity_id']);
        $this->assertEquals($attributeCode, $meta['attribute_code']);
        $this->assertEquals($entityTypeId, $meta['entity_type_id']);
        $this->assertEquals(AttributeItemPostMapper::ENTITY_TYPE, $meta['entity_type']);

        $data = $itemData->getData();

        $this->assertArrayHasKey('options', $data);

        $optionsResult = $data['options'];

        $this->assertArrayHasKey($optionValue, $optionsResult);
        $this->assertEquals($optionLabel, $optionsResult[$optionValue]);
    }
}
