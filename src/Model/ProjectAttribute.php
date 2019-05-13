<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerEav\Model;

use Eurotext\TranslationManager\Model\AbstractProjectEntity;
use Eurotext\TranslationManagerEav\Api\Data\ProjectAttributeInterface;
use Eurotext\TranslationManagerEav\Model\ResourceModel\ProjectAttributeCollection;
use Eurotext\TranslationManagerEav\Model\ResourceModel\ProjectAttributeResource;
use Eurotext\TranslationManagerEav\Setup\ProjectAttributeSchema;

class ProjectAttribute extends AbstractProjectEntity implements ProjectAttributeInterface
{
    const CACHE_TAG = 'eurotext_project_attribute';

    protected function _construct()
    {
        $this->_init(ProjectAttributeResource::class);
        $this->_setResourceModel(ProjectAttributeResource::class, ProjectAttributeCollection::class);
    }

    protected function getCacheTag(): string
    {
        return self::CACHE_TAG;
    }

    public function getAttributeCode(): string
    {
        return $this->getData(ProjectAttributeSchema::ATTRIBUTE_CODE) ?: '';
    }

    public function setAttributeCode(string $attributeCode)
    {
        $this->setData(ProjectAttributeSchema::ATTRIBUTE_CODE, $attributeCode);
    }

    public function getEavEntityType(): string
    {
        return $this->getData(ProjectAttributeSchema::EAV_ENTITY_TYPE) ?: '';
    }

    public function setEavEntityType(string $eavEntityType)
    {
        $this->setData(ProjectAttributeSchema::EAV_ENTITY_TYPE, $eavEntityType);
    }
}
