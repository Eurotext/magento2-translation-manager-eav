<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerAttribute\Model\ResourceModel;

use Eurotext\TranslationManagerAttribute\Setup\ProjectAttributeSchema;

class ProjectAttributeResource extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init(ProjectAttributeSchema::TABLE_NAME, ProjectAttributeSchema::ID);
    }
}
