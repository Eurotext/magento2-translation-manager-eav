<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Mapper;

use Eurotext\RestApiClient\Response\Project\ItemGetResponse;
use Magento\Eav\Api\Data\AttributeInterface;

class AttributeItemGetMapper
{
    public function map(ItemGetResponse $itemGetResponse, AttributeInterface $attribute): AttributeInterface
    {
        $item = $itemGetResponse->getItemData();

        // @todo map response to attribute

        return $attribute;
    }
}