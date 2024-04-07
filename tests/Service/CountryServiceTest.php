<?php

namespace App\Tests\Service;

use App\Service\Country;
use App\Service\Tool\Country\ORM;
use Doctrine\ORM\EntityNotFoundException;

class CountryServiceTest extends AbstractTestService
{
    private Country $service;

    public function setUp(): void
    {
        $this->service = self::getService(Country::class);
        parent::setUp();
    }

    public function testCountryGetORMService():void
    {
        $this->assertInstanceOf(ORM::class, $this->service->getORMService());
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testCountryGetAll(): void
    {
        self::getAll($this->service, "country");
    }
}
