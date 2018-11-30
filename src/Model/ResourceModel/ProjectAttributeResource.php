<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerEav\Model\ResourceModel;

use Eurotext\TranslationManagerEav\Setup\ProjectAttributeSchema;

class ProjectAttributeResource extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init(ProjectAttributeSchema::TABLE_NAME, ProjectAttributeSchema::ID);
    }
}
