<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerAttribute\Model\ResourceModel;

use Eurotext\TranslationManagerAttribute\Model\ProjectAttribute;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class ProjectAttributeCollection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(ProjectAttribute::class, ProjectAttributeResource::class);
    }
}
