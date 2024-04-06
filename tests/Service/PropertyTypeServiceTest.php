<?php

namespace App\Tests\Service;

use App\Service\PropertyType;
use App\Service\Tool\PropertyType\ORM;
use Doctrine\ORM\EntityNotFoundException;

class PropertyTypeServiceTest extends AbstractTestService
{
    private PropertyType $service;

    public function setUp(): void
    {
        $this->service = self::getService(PropertyType::class);
        parent::setUp();
    }

    public function testPropertyTypeGetORMService():void
    {
        $this->assertInstanceOf(ORM::class, $this->service->getORMService());
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testPropertyTypeGetAll(): void
    {
        self::getAll($this->service, "propertyType");
    }
}
