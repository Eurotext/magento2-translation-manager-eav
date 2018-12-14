<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Test\Integration\Retriever;

use Eurotext\RestApiClient\Api\Project\ItemV1Api;
use Eurotext\RestApiClient\Api\ProjectV1Api;
use Eurotext\RestApiClient\Request\ProjectTranslateRequest;
use Eurotext\TranslationManager\Service\Project\CreateProjectServiceInterface;
use Eurotext\TranslationManager\Test\Builder\ConfigurationMockBuilder;
use Eurotext\TranslationManager\Test\Integration\IntegrationTestAbstract;
use Eurotext\TranslationManager\Test\Integration\Provider\ProjectProvider;
use Eurotext\TranslationManagerEav\Repository\ProjectAttributeRepository;
use Eurotext\TranslationManagerEav\Retriever\AttributeRetriever;
use Eurotext\TranslationManagerEav\Sender\AttributeSender;
use Eurotext\TranslationManagerEav\Test\Integration\Provider\ProjectAttributeProvider;
use Eurotext\TranslationManagerProduct\Model\ProjectProduct;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;

class AttributeRetrieverTest extends IntegrationTestAbstract
{
    /** @var ProjectAttributeRepository */
    private $projectAttributeRepository;

    /** @var AttributeRetriever */
    private $sut;

    /** @var ProjectAttributeProvider */
    private $projectAttributeProvider;

    /** @var ProjectProvider */
    private $projectProvider;

    /** @var AttributeSender */
    private $sender;

    /** @var CreateProjectServiceInterface */
    private $createProject;

    /** @var ProjectV1Api */
    private $projectApi;

    protected function setUp()
    {
        parent::setUp();

        $config = (new ConfigurationMockBuilder($this))->buildConfiguration();

        $itemApi          = new ItemV1Api($config);
        $this->projectApi = new ProjectV1Api($config);

        $this->sut = $this->objectManager->create(AttributeRetriever::class, ['itemApi' => $itemApi]);

        $this->createProject = $this->objectManager->create(
            CreateProjectServiceInterface::class, ['projectApi' => $this->projectApi]
        );

        $this->sender = $this->objectManager->create(AttributeSender::class, ['itemApi' => $itemApi]);

        $this->projectProvider            = $this->objectManager->get(ProjectProvider::class);
        $this->projectAttributeProvider   = $this->objectManager->get(ProjectAttributeProvider::class);
        $this->projectAttributeRepository = $this->objectManager->get(ProjectAttributeRepository::class);
    }

    /**
     * @magentoDataFixture loadFixture
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testItShouldRetrieveProjectAttributes()
    {
        $productId     = 10;
        $name          = __CLASS__ . '-attribute-retriever';
        $attributeCode = 'etm_integration_tests_1';
        $eavEntityType = 'catalog_product';

        $project = $this->projectProvider->createProject($name);

        $projectAttribute  = $this->projectAttributeProvider->createProjectAttribute(
            $project->getId(), $productId, $attributeCode, $eavEntityType
        );
        $projectAttributeId = $projectAttribute->getId();

        // Create project at Eurotext
        $resultProjectCreate = $this->createProject->execute($project);
        $this->assertTrue($resultProjectCreate);

        // Send Project Products to Eurotext
        $resultSend = $this->sender->send($project);
        $this->assertTrue($resultSend);

        // trigger translation progress
        $this->projectApi->translate(new ProjectTranslateRequest($project->getExtId()));

        try {
            // Set The area code otherwise image resizing will fail
            /** @var State $appState */
            $appState = $this->objectManager->get(State::class);
            $appState->setAreaCode('adminhtml');
        } catch (LocalizedException $e) {
        }

        // Retrieve Project from Eurotext
        $result = $this->sut->retrieve($project);

        $this->assertTrue($result);

        $projectEntity = $this->projectAttributeRepository->getById($projectAttributeId);
        $this->assertGreaterThan(0, $projectEntity->getExtId());
        $this->assertEquals(ProjectProduct::STATUS_IMPORTED, $projectEntity->getStatus());
    }

    public static function loadFixture()
    {
        include __DIR__ . '/../_fixtures/provide_attributes.php';
    }
}
