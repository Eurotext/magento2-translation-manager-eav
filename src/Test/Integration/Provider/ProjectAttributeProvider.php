<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Test\Integration\Provider;

use Eurotext\TranslationManagerEav\Api\Data\ProjectAttributeInterface;
use Eurotext\TranslationManagerEav\Model\ProjectAttribute;
use Eurotext\TranslationManagerEav\Repository\ProjectAttributeRepository;
use Magento\TestFramework\Helper\Bootstrap;

class ProjectAttributeProvider
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    /** @var ProjectAttributeRepository */
    private $projectAttributeRepository;

    public function __construct()
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->projectAttributeRepository = $this->objectManager->get(ProjectAttributeRepository::class);
    }

    /**
     *
     * @param int $projectId
     * @param int $productId
     * @param string $attributeCode
     * @param string $eavEntityType
     * @param string $status
     *
     * @return ProjectAttributeInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function createProjectAttribute(
        int $projectId,
        int $productId,
        string $attributeCode,
        string $eavEntityType = 'catalog_product',
        string $status = ProjectAttribute::STATUS_NEW
    ): ProjectAttributeInterface {
        /** @var ProjectAttribute $object */
        $object = $this->objectManager->create(ProjectAttribute::class);
        $object->setProjectId($projectId);
        $object->setEntityId($productId);
        $object->setEavEntityType($eavEntityType);
        $object->setAttributeCode($attributeCode);
        $object->setStatus($status);

        return $this->projectAttributeRepository->save($object);
    }
}
