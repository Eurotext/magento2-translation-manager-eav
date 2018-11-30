<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerAttribute\Model;

use Eurotext\TranslationManager\Model\AbstractProjectEntity;
use Eurotext\TranslationManagerAttribute\Model\ResourceModel\ProjectAttributeCollection;
use Eurotext\TranslationManagerAttribute\Model\ResourceModel\ProjectAttributeResource;

class ProjectAttribute extends AbstractProjectEntity
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
}
