<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerProduct\Test\Unit\Repository;

use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManagerEav\Repository\AttributeEntityDataSaver;
use Eurotext\TranslationManagerEav\Seeder\AttributeSeeder;
use Eurotext\TranslationManagerEav\Service\CleanProjectAttributesService;
use Eurotext\TranslationManagerProduct\Service\CleanProductsService;
use Eurotext\TranslationManagerProduct\Test\Unit\UnitTestAbstract;
use PHPUnit\Framework\MockObject\MockObject;

class AttributeEntityDataSaverTest extends UnitTestAbstract
{
    /** @var AttributeEntityDataSaver */
    private $sut;

    /** @var CleanProductsService|MockObject */
    private $cleanAttributes;

    /** @var AttributeSeeder|MockObject */
    private $entitySeeder;

    protected function setUp()
    {
        parent::setUp();

        $this->entitySeeder = $this->createMock(AttributeSeeder::class);
        $this->cleanAttributes = $this->createMock(CleanProjectAttributesService::class);

        $this->sut = $this->objectManager->getObject(
            AttributeEntityDataSaver::class, [
                'entitySeeder' => $this->entitySeeder,
                'cleanProjectAttributes' => $this->cleanAttributes,
            ]
        );
    }

    public function testItShouldSeedEntities()
    {
        $data = ['attributes' => ['is_active' => '1']];

        $project = $this->createMock(ProjectInterface::class);
        /** @var ProjectInterface $project */

        $this->entitySeeder->expects($this->once())->method('seed')
                           ->with($project)->willReturn(true);

        $this->cleanAttributes->expects($this->never())->method('clean');

        $save = $this->sut->save($project, $data);

        $this->assertTrue($save);
    }

    public function testItShouldCleanEntities()
    {
        $data = ['attributes' => ['is_active' => '0']];

        $project = $this->createMock(ProjectInterface::class);
        /** @var ProjectInterface $project */

        $this->entitySeeder->expects($this->never())->method('seed');

        $this->cleanAttributes->expects($this->once())->method('clean')
                              ->with($project)->willReturn(true);

        $save = $this->sut->save($project, $data);

        $this->assertTrue($save);
    }

    public function testItShouldReturnIfAttributesIsNotSet()
    {
        $data = [];

        $project = $this->createMock(ProjectInterface::class);
        /** @var ProjectInterface $project */

        $result = $this->sut->save($project, $data);

        $this->assertTrue($result);
    }

}