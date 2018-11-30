<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Entity;

use Eurotext\TranslationManager\Api\EntityTypeInterface;

class AttributeEntityType implements EntityTypeInterface
{
    const CODE        = 'attribute';
    const DESCRIPTION = 'Attribute';

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getDescription(): string
    {
        return (string)__(self::DESCRIPTION);
    }
}