<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Test\Unit\Mapper;

use Eurotext\RestApiClient\Response\Project\ItemGetResponse;
use Eurotext\TranslationManagerEav\Mapper\AttributeItemGetMapper;
use Eurotext\TranslationManagerEav\Test\Unit\UnitTestAbstract;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterface;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterfaceFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;

class AttributeItemGetMapperUnitTest extends UnitTestAbstract
{
    /** @var AttributeOptionLabelInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $optionLabelFactory;

    /** @var AttributeFrontendLabelInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $frontendLabelFactory;

    /** @var AttributeItemGetMapper */
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->frontendLabelFactory = $this->createMock(AttributeFrontendLabelInterfaceFactory::class);
        $this->optionLabelFactory = $this->createMock(AttributeOptionLabelInterfaceFactory::class);

        $this->sut = $this->objectManager->getObject(
            AttributeItemGetMapper::class,
            [
                'frontendLabelFactory' => $this->frontendLabelFactory,
                'optionLabelFactory' => $this->optionLabelFactory,
            ]
        );
    }

    public function testMap()
    {
        $storeViewDst = 2;
        $label = 'some-frontend-label';
        $optionValue = '111';
        $optionLabel = 'Option Label 1';

        $attributeLabel = $this->createMock(AttributeFrontendLabelInterface::class);
        $attributeLabel->expects($this->once())->method('setLabel')->with($label);
        $attributeLabel->expects($this->once())->method('setStoreId')->with($storeViewDst);

        $this->frontendLabelFactory->expects($this->once())->method('create')->willReturn($attributeLabel);

        $attrOptionLabel = $this->createMock(AttributeOptionLabelInterface::class);
        $attrOptionLabel->expects($this->once())->method('setLabel')->with($optionLabel);
        $attrOptionLabel->expects($this->once())->method('setStoreId')->with($storeViewDst);
        $this->optionLabelFactory->expects($this->once())->method('create')->willReturn($attrOptionLabel);

        $optionOne = $this->createMock(AttributeOptionInterface::class);
        $optionOne->method('getValue')->willReturn($optionValue);
        $optionOne->expects($this->once())->method('getStoreLabels')->willReturn([]);
        $optionOne->expects($this->once())->method('setStoreLabels')->with([$attrOptionLabel]);

        $options = [$optionOne];

        $frontendLabels = [];

        /** @var \PHPUnit_Framework_MockObject_MockObject|AttributeInterface $attribute */
        $attribute = $this->createMock(AttributeInterface::class);
        $attribute->expects($this->once())->method('getFrontendLabels')->willReturn($frontendLabels);
        $attribute->expects($this->once())->method('setFrontendLabels')->with();
        $attribute->expects($this->once())->method('getOptions')->willReturn($options);
        $attribute->expects($this->once())->method('setOptions')->with($options);

        // Execute test
        $itemGetResponse = new ItemGetResponse();
        $itemGetResponse->setData(
            [
                '__meta' => [],
                'label' => $label,
                'options' => [
                    $optionValue => $optionLabel,
                    '222' => 'Option no longer exists in Magento Database',
                ],
            ]
        );

        $attributeResult = $this->sut->map($itemGetResponse, $attribute, $storeViewDst);

        // ASSERT
        $this->assertInstanceOf(AttributeInterface::class, $attributeResult);
        $this->assertEquals($attribute, $attributeResult);
    }
}
