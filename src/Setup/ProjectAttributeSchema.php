<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Setup;

use Eurotext\TranslationManager\Api\Setup\ProjectEntitySchema;

class ProjectAttributeSchema implements ProjectEntitySchema
{
    const TABLE_NAME = 'eurotext_project_attributes';

    const ATTRIBUTE_CODE  = 'attribute_code';
    const EAV_ENTITY_TYPE = 'eav_entity_type';
}
