<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Test\Integration\Sender;

use Eurotext\RestApiClient\Api\Project\ItemV1Api;
use Eurotext\RestApiClient\Api\ProjectV1Api;
use Eurotext\TranslationManager\Service\Project\CreateProjectServiceInterface;
use Eurotext\TranslationManager\Test\Builder\ConfigurationMockBuilder;
use Eurotext\TranslationManager\Test\Integration\IntegrationTestAbstract;
use Eurotext\TranslationManager\Test\Integration\Provider\ProjectProvider;
use Eurotext\TranslationManagerEav\Api\ProjectAttributeRepositoryInterface;
use Eurotext\TranslationManagerEav\Repository\ProjectAttributeRepository;
use Eurotext\TranslationManagerEav\Sender\AttributeSender;
use Eurotext\TranslationManagerEav\Test\Integration\Provider\AttributeProvider;
use Eurotext\TranslationManagerEav\Test\Integration\Provider\ProjectAttributeProvider;

class AttributeSenderIntegrationTest extends IntegrationTestAbstract
{
    /** @var ProjectAttributeRepositoryInterface */
    private $projectEntityRepository;

    /** @var AttributeSender */
    private $sut;

    /** @var ProjectAttributeProvider */
    private $projectEntityProvider;

    /** @var ProjectProvider */
    private $projectProvider;

    /** @var CreateProjectServiceInterface */
    private $createProject;

    protected function setUp(): void
    {
        parent::setUp();

        $configBuiler = new ConfigurationMockBuilder($this);
        $config       = $configBuiler->buildConfiguration();

        $itemApi = new ItemV1Api($config);

        $this->sut = $this->objectManager->create(
            AttributeSender::class,
            [
                'itemApi' => $itemApi,
            ]
        );

        $projectApi = new ProjectV1Api($config);

        $this->createProject = $this->objectManager->create(
            CreateProjectServiceInterface::class,
            ['projectApi' => $projectApi]
        );

        $this->projectProvider       = $this->objectManager->get(ProjectProvider::class);
        $this->projectEntityProvider = $this->objectManager->get(ProjectAttributeProvider::class);

        $this->projectEntityRepository = $this->objectManager->get(ProjectAttributeRepository::class);
    }

    /**
     * @magentoDataFixture loadFixture
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function testItShouldSendProjectEntities()
    {
        $entityId      = 10;
        $attributeCode = 'etm_integration_tests_1';
        $eavEntityType = 'catalog_product';
        $projectName   = __CLASS__ . '-attribute-sender';

        $project = $this->projectProvider->createProject($projectName);

        $projectEntity = $this->projectEntityProvider->createProjectAttribute(
            $project->getId(), $entityId, $attributeCode, $eavEntityType
        );

        $resultProject = $this->createProject->execute($project);
        $this->assertTrue($resultProject);

        $result = $this->sut->send($project);

        $this->assertTrue($result);

        $projectEntity = $this->projectEntityRepository->getById($projectEntity->getId());

        $extId = $projectEntity->getExtId();

        $this->assertGreaterThan(0, $extId, 'The ext_id should be the one from Eurotext');

    }

    public static function loadFixture()
    {
        AttributeProvider::createSelctAttributeWithOptions();
    }
}
