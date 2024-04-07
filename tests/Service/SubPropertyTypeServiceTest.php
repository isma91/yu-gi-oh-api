<?php

namespace App\Tests\Service;

use App\Service\SubPropertyType;
use App\Service\Tool\SubPropertyType\ORM;
use Doctrine\ORM\EntityNotFoundException;

class SubPropertyTypeServiceTest extends AbstractTestService
{
    private SubPropertyType $service;

    public function setUp(): void
    {
        $this->service = self::getService(SubPropertyType::class);
        parent::setUp();
    }

    public function testSubPropertyTypeGetORMService():void
    {
        $this->assertInstanceOf(ORM::class, $this->service->getORMService());
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testSubPropertyTypeGetAll(): void
    {
        self::getAll($this->service, "subPropertyType");
    }
}
