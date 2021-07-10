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
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class AttributeItemGetMapperUnitTest extends UnitTestAbstract
{
    /** @var AttributeOptionLabelInterfaceFactory|MockObject */
    private $optionLabelFactory;
    /** @var AttributeFrontendLabelInterfaceFactory|MockObject */
    private $frontendLabelFactory;
    /** @var StoreManagerInterface|MockObject */
    private $storeManager;

    /** @var StoreInterface|MockObject */
    private $store;

    /** @var AttributeItemGetMapper */
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->frontendLabelFactory = $this->createMock(AttributeFrontendLabelInterfaceFactory::class);
        $this->optionLabelFactory = $this->createMock(AttributeOptionLabelInterfaceFactory::class);

        $this->store = $this->createMock(StoreInterface::class);

        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($this->store);

        $this->sut = $this->objectManager->getObject(
            AttributeItemGetMapper::class,
            [
                'frontendLabelFactory' => $this->frontendLabelFactory,
                'optionLabelFactory' => $this->optionLabelFactory,
                'storeManager' => $this->storeManager,
            ]
        );
    }

    public function testMap()
    {
        $storeViewDst = 2;
        $label = 'some-frontend-label';
        $optionValue = '111';
        $optionLabel = 'Option Label 1';
        $storeId = 4711;

        $this->store->expects($this->once())->method('getId')->willReturn($storeId);
        $this->storeManager->expects($this->exactly(2))->method('setCurrentStore')
                           ->withConsecutive(...[[0], [$storeId]]);

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
