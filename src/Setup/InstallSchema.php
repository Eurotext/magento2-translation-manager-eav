<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerAttribute\Setup;

use Eurotext\TranslationManagerAttribute\Setup\Service\CreateProjectAttributeSchema;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var CreateProjectAttributeSchema
     */
    private $createProjectAttributeSchema;

    public function __construct(
        CreateProjectAttributeSchema $createProjectAttributeSchema
    ) {
        $this->createProjectAttributeSchema = $createProjectAttributeSchema;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->createProjectAttributeSchema->execute($setup);
    }
}
