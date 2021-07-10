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
use Eurotext\TranslationManager\Test\Integration\Provider\StoreProvider;
use Eurotext\TranslationManagerEav\Model\ProjectAttribute;
use Eurotext\TranslationManagerEav\Repository\ProjectAttributeRepository;
use Eurotext\TranslationManagerEav\Retriever\AttributeRetriever;
use Eurotext\TranslationManagerEav\Sender\AttributeSender;
use Eurotext\TranslationManagerEav\Test\Integration\Provider\AttributeProvider;
use Eurotext\TranslationManagerEav\Test\Integration\Provider\ProjectAttributeProvider;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;

class AttributeRetrieverTest extends IntegrationTestAbstract
{
    protected static $storeId;

    /** @var AttributeRepositoryInterface */
    private $attributeRepository;

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

    protected function setUp(): void
    {
        parent::setUp();

        $config = (new ConfigurationMockBuilder($this))->buildConfiguration();

        $itemApi = new ItemV1Api($config);
        $this->projectApi = new ProjectV1Api($config);

        $this->sut = $this->objectManager->create(AttributeRetriever::class, ['itemApi' => $itemApi]);

        $this->createProject = $this->objectManager->create(
            CreateProjectServiceInterface::class, ['projectApi' => $this->projectApi]
        );

        $this->sender = $this->objectManager->create(AttributeSender::class, ['itemApi' => $itemApi]);

        $this->projectProvider = $this->objectManager->get(ProjectProvider::class);
        $this->projectAttributeProvider = $this->objectManager->get(ProjectAttributeProvider::class);
        $this->projectAttributeRepository = $this->objectManager->get(ProjectAttributeRepository::class);
        $this->attributeRepository = $this->objectManager->get(AttributeRepositoryInterface::class);
    }

    /**
     * This test checks the translations are stored as expected.
     *
     * It does so by doing the following
     * 1. Fixtures: create store, create attribute with options, create project, create projectAttribute
     * 2. Create Project at Eurotext
     * 3. Send Project Attributes to Eurotext using AttributeSender
     * 4. Trigger Translation in sandbox
     * 5. Retrieve project from Eurotext using AttributeRetriever
     * 6. Assert the Attribute was saved with the translated values for the store from the fixture
     *
     * @magentoDataFixture loadFixture
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testItShouldRetrieveProjectAttributes()
    {
        $productId = 10;
        $name = __CLASS__ . '-attribute-retriever';
        $attributeCode = 'etm_integration_tests_1';
        $eavEntityType = 'catalog_product';

        // Get Expected Labels for the attribute
        $expectedLabels = $this->initExpectedLabels($eavEntityType, $attributeCode);

        // Temporary project using store-id from provider
        $project = $this->projectProvider->createProject($name);
        $project->setStoreviewSrc(1);
        $project->setStoreviewDst(self::$storeId);

        // An Project Entity Attribute
        $projectAttribute = $this->projectAttributeProvider->createProjectAttribute(
            $project->getId(), $productId, $attributeCode, $eavEntityType
        );
        $projectAttributeId = $projectAttribute->getId();

        // Create project at Eurotext
        $resultProjectCreate = $this->createProject->execute($project);
        $this->assertTrue($resultProjectCreate, 'Project created');

        // Send Project Attributes to Eurotext
        $resultSend = $this->sender->send($project);
        $this->assertTrue($resultSend,'Project attributes sent');

        // trigger translation progress
        $this->projectApi->translate(new ProjectTranslateRequest($project->getExtId()));

        sleep(3); // Sleep to let some time pass so the api has time to process the request

        try {
            // Set The area code otherwise image resizing will fail
            /** @var State $appState */
            $appState = $this->objectManager->get(State::class);
            $appState->setAreaCode('adminhtml');
        } catch (LocalizedException $e) {
        }

        // Retrieve Project from Eurotext
        $result = $this->sut->retrieve($project);

        $this->assertTrue($result, 'Project retrieved');

        // Validate the project entity attribute and check status
        $projectEntity = $this->projectAttributeRepository->getById($projectAttributeId);
        $this->assertGreaterThan(0, $projectEntity->getExtId());
        $this->assertEquals(ProjectAttribute::STATUS_IMPORTED, $projectEntity->getStatus());

        // Get the attribute and validate its values
        $attribute = $this->attributeRepository->get($eavEntityType, $attributeCode);

        $this->assertEquals($attributeCode, $attribute->getAttributeCode());

        // store_id needs to be set to attribtue because in getOptions it is used to retrieve the values for the store
        $attribute->setStoreId(self::$storeId);

        $options = $attribute->getOptions();
        foreach ($options as $option) {
            /** @var AttributeOptionInterface $option */
            $value = $option->getValue();
            $label = $option->getLabel();

            if (!array_key_exists($value, $expectedLabels)) {
                continue;
            }

            $this->assertEquals($expectedLabels[$value], $label);
        }
    }

    public static function loadFixture()
    {
        AttributeProvider::createSelctAttributeWithOptions();

        self::$storeId = (int)StoreProvider::createStore('store_dest')->getId();
    }

    private function initExpectedLabels(string $eavEntityType, string $attributeCode): array
    {
        $attribute = $this->attributeRepository->get($eavEntityType, $attributeCode);
        $origOptions = $attribute->getOptions();

        $expectedLabels = [];
        foreach ($origOptions as $origOption) {
            $expectedLabels[$origOption->getValue()] = strrev($origOption->getLabel());
        }

        return $expectedLabels;
    }
}
