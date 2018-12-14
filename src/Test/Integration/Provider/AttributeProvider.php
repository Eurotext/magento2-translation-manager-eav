<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Test\Integration\Provider;

class AttributeProvider
{
    public static function createSelctAttributeWithOptions()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $entityTypeCode = 'catalog_product';

        /** @var \Magento\Eav\Model\Entity\Type $entityType */
        $entityType = $objectManager->create(\Magento\Eav\Model\Entity\Type::class)->loadByCode($entityTypeCode);

        $entityTypeId = $entityType->getId();

        $attributeCode = 'etm_integration_tests_1';
        $attributeData = [
            [
                'attribute_code'                => $attributeCode,
                'entity_type_id'                => $entityTypeId,
                'frontend_label'                => ['ETM Integration Tests 1'],
                'frontend_input'                => 'select',
                'backend_type'                  => 'varchar',
                'backend_model'                 => \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class,
                'is_global'                     => 1,
                'is_required'                   => 0,
                'is_user_defined'               => 1,
                'is_unique'                     => 0,
                'is_searchable'                 => 0,
                'is_visible_in_advanced_search' => 0,
                'is_comparable'                 => 0,
                'is_filterable'                 => 1,
                'is_filterable_in_search'       => 0,
                'is_used_for_promo_rules'       => 0,
                'is_html_allowed_on_front'      => 1,
                'is_visible_on_front'           => 1,
                'used_in_product_listing'       => 0,
                'used_for_sort_by'              => 0,

                'option' => [
                    'value' => [
                        'option_1' => ['Option 1'],
                        'option_2' => ['Option 2'],
                        'option_3' => ['Option 3'],
                        'option_4' => ['Option 4 "!@#$%^&*'],
                    ],
                    'order' => [
                        'option_1' => 1,
                        'option_2' => 2,
                        'option_3' => 3,
                        'option_4' => 4,
                    ],
                ],
            ],
        ];

        foreach ($attributeData as $data) {
            $options = [];
            if (isset($data['options'])) {
                $options = $data['options'];
                unset($data['options']);
            }

            /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
            $attribute = $objectManager->create(\Magento\Eav\Model\Entity\Attribute::class);
            $attribute->setData($data);
            $attribute->save();
        }
    }
}
