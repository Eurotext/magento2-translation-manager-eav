<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Setup\Service;

use Eurotext\TranslationManagerEav\Setup\ProjectAttributeSchema;
use Magento\Framework\DB\Ddl\Table as DbDdlTable;
use Magento\Framework\Setup\SchemaSetupInterface;

class CreateProjectAttributeSchema
{
    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     *
     * @throws \Zend_Db_Exception
     */
    public function execute(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();

        $table = $connection->newTable($setup->getTable(ProjectAttributeSchema::TABLE_NAME));

        $table->addColumn(
            ProjectAttributeSchema::ID,
            DbDdlTable::TYPE_BIGINT,
            20,
            ['primary' => true, 'unsigned' => true, 'nullable' => false, 'auto_increment' => true,],
            'Project Product ID'
        );
        $table->addColumn(
            ProjectAttributeSchema::EXT_ID,
            DbDdlTable::TYPE_BIGINT,
            20,
            ['unsigned' => true, 'nullable' => false],
            'External ID provided by Eurotext'
        );
        $table->addColumn(
            ProjectAttributeSchema::PROJECT_ID,
            DbDdlTable::TYPE_BIGINT,
            20,
            ['unsigned' => true, 'nullable' => false,],
            'Project ID'
        );
        $table->addColumn(
            ProjectAttributeSchema::ENTITY_ID,
            DbDdlTable::TYPE_BIGINT,
            20,
            ['unsigned' => true, 'nullable' => false,],
            'Attribute ID'
        );
        $table->addColumn(
            ProjectAttributeSchema::ATTRIBUTE_CODE,
            DbDdlTable::TYPE_TEXT,
            50,
            ['nullable' => false],
            'Attribute Code'
        );
        $table->addColumn(
            ProjectAttributeSchema::EAV_ENTITY_TYPE,
            DbDdlTable::TYPE_TEXT,
            40,
            ['nullable' => false],
            'EAV Entity Type'
        );
        $table->addColumn(
            ProjectAttributeSchema::STATUS,
            DbDdlTable::TYPE_TEXT,
            20,
            ['nullable' => false],
            'Status'
        );
        $table->addColumn(
            ProjectAttributeSchema::LAST_ERROR,
            DbDdlTable::TYPE_TEXT,
            null,
            ['nullable' => true],
            'Last error details and message'
        );
        $table->addColumn(
            ProjectAttributeSchema::CREATED_AT,
            DbDdlTable::TYPE_TIMESTAMP,
            null,
            [],
            'Created at'
        );
        $table->addColumn(
            ProjectAttributeSchema::UPDATED_AT,
            DbDdlTable::TYPE_TIMESTAMP,
            null,
            ['default' => DbDdlTable::TIMESTAMP_INIT_UPDATE],
            'Last Update'
        );

        $idxName = $setup->getIdxName($table->getName(), [ProjectAttributeSchema::EXT_ID]);
        $table->addIndex($idxName, [ProjectAttributeSchema::EXT_ID]);

        $idxName = $setup->getIdxName($table->getName(), [ProjectAttributeSchema::PROJECT_ID]);
        $table->addIndex($idxName, [ProjectAttributeSchema::PROJECT_ID]);

        $idxName = $setup->getIdxName($table->getName(), [ProjectAttributeSchema::ENTITY_ID]);
        $table->addIndex($idxName, [ProjectAttributeSchema::ENTITY_ID]);

        $connection->createTable($table);
    }
}
