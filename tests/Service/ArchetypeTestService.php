<?php

namespace App\Tests\Service;

use App\Service\Archetype;
use App\Service\Tool\Archetype\ORM;
use Doctrine\ORM\EntityNotFoundException;

class ArchetypeTestService extends AbstractTestService
{
    private Archetype $service;

    public function setUp(): void
    {
        $this->service = self::getService(Archetype::class);
        parent::setUp();
    }

    public function testArchetypeGetORMService():void
    {
        $this->assertInstanceOf(ORM::class, $this->service->getORMService());
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testArchetypeGetAll(): void
    {
        self::getAll($this->service, "archetype");
    }
}
