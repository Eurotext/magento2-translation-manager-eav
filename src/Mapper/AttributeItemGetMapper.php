<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Mapper;

use Eurotext\RestApiClient\Response\Project\ItemGetResponse;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterface;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterfaceFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;

class AttributeItemGetMapper
{
    /**
     * @var AttributeOptionLabelInterfaceFactory
     */
    private $optionLabelFactory;

    /**
     * @var AttributeFrontendLabelInterfaceFactory
     */
    private $frontendLabelFactory;
    private StoreManagerInterface $storeManager;

    public function __construct(
        AttributeFrontendLabelInterfaceFactory $frontendLabelFactory,
        AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->optionLabelFactory = $optionLabelFactory;
        $this->frontendLabelFactory = $frontendLabelFactory;
        $this->storeManager = $storeManager;
    }

    public function map(
        ItemGetResponse $itemGetResponse,
        AttributeInterface $attribute,
        int $storeId
    ): AttributeInterface {
        $item = $itemGetResponse->getItemData();

        $label = (string)$item->getDataValue('label');
        if (!empty($label)) {
            $this->mapLabel($attribute, $label, $storeId);
        }

        $options = $item->getDataValue('options');
        if (is_array($options) && count($options) > 0) {
            $this->mapOptions($attribute, $options, $storeId);
        }

        return $attribute;
    }

    private function mapLabel(AttributeInterface $attribute, string $label, int $storeId)
    {
        // Set Attribute translation for Store
        $frontendLabels = $attribute->getFrontendLabels();

        // @todo find existing frontendLabel and overwrite

        /** @var AttributeFrontendLabelInterface $frontendLabel */
        $frontendLabel = $this->frontendLabelFactory->create();
        $frontendLabel->setLabel($label);
        $frontendLabel->setStoreId($storeId);

        $frontendLabels[] = $frontendLabel;
        $attribute->setFrontendLabels($frontendLabels);
    }

    private function mapOptions(AttributeInterface $attribute, array $translatedOptions, int $storeId)
    {
        $options = $this->getAttributeOptions($attribute);

        foreach ($translatedOptions as $optionValue => $optionLabel) {
            $option = $this->findAttributeOptionByValue($options, (string)$optionValue);
            if ($option === null) {
                // Option not found, might have been deleted, so we skip it
                continue;
            }

            $storeLabels = $option->getStoreLabels();

            // @todo find existing frontendLabel and overwrite

            /** @var AttributeOptionLabelInterface $storeLabel */
            $storeLabel = $this->optionLabelFactory->create();
            $storeLabel->setLabel($optionLabel);
            $storeLabel->setStoreId($storeId);

            $storeLabels[] = $storeLabel;

            $option->setStoreLabels($storeLabels);
        }

        $attribute->setOptions($options);
    }

    private function getAttributeOptions(AttributeInterface $attribute): ?array
    {
        // Switch to admin store to not update the admin / default label
        // attribute->getOptions uses the storeManager to retrieve the current store
        // the current store at this point in time was already set to the target store of the Eurotext project
        // in \Eurotext\TranslationManager\Service\Project\FetchProjectEntitiesService
        // so we have to revert back to admin store, get options, reset to target store
        // we could also use attribute->setStoreId, though that is not in the interface
        $storeIdBefore = $this->storeManager->getStore()->getId();
        $this->storeManager->setCurrentStore(0);

        $options = $attribute->getOptions();

        $this->storeManager->setCurrentStore($storeIdBefore);

        return $options;
    }

    /**
     * @param AttributeOptionInterface[] $options
     * @param string $value
     *
     * @return AttributeOptionInterface|null
     */
    private function findAttributeOptionByValue(array $options, string $value)
    {
        foreach ($options as $option) {
            if ($option->getValue() === $value) {
                return $option;
            }
        }

        return null;
    }
}
