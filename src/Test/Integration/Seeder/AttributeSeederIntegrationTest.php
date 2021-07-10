<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Test\Integration\Seeder;

use Eurotext\TranslationManager\Test\Integration\IntegrationTestAbstract;
use Eurotext\TranslationManager\Test\Integration\Provider\ProjectProvider;
use Eurotext\TranslationManagerEav\Seeder\AttributeSeeder;

class AttributeSeederIntegrationTest extends IntegrationTestAbstract
{
    /** @var AttributeSeeder */
    protected $sut;

    /** @var ProjectProvider */
    private $projectProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = $this->objectManager->create(AttributeSeeder::class);

        $this->projectProvider = $this->objectManager->get(ProjectProvider::class);
    }

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function testItShouldSeedProjectProducts()
    {
        $name = __CLASS__ . '-attribute-seeder';

        $project = $this->projectProvider->createProject($name);

        $result = $this->sut->seed($project);

        $this->assertTrue($result);
    }

}
