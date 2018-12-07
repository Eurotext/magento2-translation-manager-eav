<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerEav\Api\Data;

use Eurotext\TranslationManager\Api\Data\ProjectEntityInterface;

interface ProjectAttributeInterface extends ProjectEntityInterface
{
    public function getAttributeCode(): string;

    public function setAttributeCode(string $attributeCode);

    public function getEavEntityType(): string;

    public function setEavEntityType(string $eavEntityType);
}
