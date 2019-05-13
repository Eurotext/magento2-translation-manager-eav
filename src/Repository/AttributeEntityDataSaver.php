<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Repository;

use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\EntityDataSaverInterface;
use Eurotext\TranslationManagerEav\Seeder\AttributeSeeder;
use Eurotext\TranslationManagerEav\Service\CleanProjectAttributesService;

class AttributeEntityDataSaver implements EntityDataSaverInterface
{
    /**
     * @var AttributeSeeder
     */
    private $entitySeeder;

    /**
     * @var CleanProjectAttributesService
     */
    private $cleanProjectAttributes;

    public function __construct(AttributeSeeder $entitySeeder, CleanProjectAttributesService $cleanProjectAttributes)
    {
        $this->entitySeeder = $entitySeeder;
        $this->cleanProjectAttributes = $cleanProjectAttributes;
    }

    public function save(ProjectInterface $project, array &$data): bool
    {
        if (!array_key_exists('attributes', $data)) {
            return true;
        }

        $dataAttributes = $data['attributes'];

        $enableAttributesSync = false;
        if (array_key_exists('is_active', $dataAttributes)) {
            $isActive = (int)$dataAttributes['is_active'];
            if ($isActive === 1) {
                $enableAttributesSync = true;
            }
        }

        if ($enableAttributesSync === true) {
            // seed all attributes
            $this->entitySeeder->seed($project);
        } else {
            $this->cleanProjectAttributes->clean($project);
        }

        return true;
    }
}