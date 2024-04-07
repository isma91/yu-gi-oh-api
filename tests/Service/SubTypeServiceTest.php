<?php

namespace App\Tests\Service;

use App\Service\SubType;
use App\Service\Tool\SubType\ORM;
use Doctrine\ORM\EntityNotFoundException;

class SubTypeServiceTest extends AbstractTestService
{
    private SubType $service;

    public function setUp(): void
    {
        $this->service = self::getService(SubType::class);
        parent::setUp();
    }

    public function testSubTypeGetORMService():void
    {
        $this->assertInstanceOf(ORM::class, $this->service->getORMService());
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testSubTypeGetAll(): void
    {
        self::getAll($this->service, "subType");
    }
}
