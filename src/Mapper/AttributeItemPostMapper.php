<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerEav\Mapper;

use Eurotext\RestApiClient\Request\Data\Project\ItemData;
use Eurotext\RestApiClient\Request\Project\ItemPostRequest;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\ScopeConfigReaderInterface;
use Magento\Eav\Api\Data\AttributeInterface;

/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */
class AttributeItemPostMapper
{
    const ENTITY_TYPE = 'specialized-text';

    /**
     * @var ScopeConfigReaderInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigReaderInterface $scopeConfigReader)
    {
        $this->scopeConfig = $scopeConfigReader;
    }

    public function map(AttributeInterface $attribute, ProjectInterface $project): ItemPostRequest
    {
        $languageSrc  = $this->scopeConfig->getLocaleForStore($project->getStoreviewSrc());
        $languageDest = $this->scopeConfig->getLocaleForStore($project->getStoreviewDst());

        $options          = [];
        $attributeOptions = $attribute->getOptions();
        foreach ($attributeOptions as $optionKey => $option) {
            $options[$option->getValue()] = $option->getLabel();
        }

        $data = [
            'label'   => $attribute->getDefaultFrontendLabel(),
            'options' => $options,
        ];

        $meta = [
            'item_id'        => $attribute->getAttributeId(),
            'attribute_code' => $attribute->getAttributeCode(),
            'entity_id'      => $attribute->getAttributeId(),
            'entity_type'    => self::ENTITY_TYPE,
            'entity_type_id' => $attribute->getEntityTypeId(),
        ];

        $itemData = new ItemData($data, $meta);

        $itemRequest = new ItemPostRequest(
            $project->getExtId(),
            $languageSrc,
            $languageDest,
            self::ENTITY_TYPE,
            '',
            $itemData
        );

        return $itemRequest;
    }
}