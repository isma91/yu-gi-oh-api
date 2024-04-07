<?php

namespace App\Tests\Service;

use App\Repository\CardRepository;
use App\Repository\SetRepository;
use App\Service\Archetype;
use App\Service\Card;
use App\Service\CardAttribute;
use App\Service\Category;
use App\Service\PropertyType;
use App\Service\Search;
use App\Service\Set;
use App\Service\SubPropertyType;
use App\Service\SubType;
use App\Service\Type;
use Doctrine\ORM\EntityNotFoundException;

class SearchServiceTest extends AbstractTestService
{
    private Search $service;

    public function setUp(): void
    {
        $this->service = self::getService(Search::class);
        parent::setUp();
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testSearchCard(): void
    {
        $jwt = self::getJWT();
        $cardRepository = self::getService(CardRepository::class);
        $cosmoQueen = $cardRepository->findOneBy(["slugName" => "cosmo-queen"]);
        //check that all these card exist
        if ($cosmoQueen === NULL) {
            throw new EntityNotFoundException(
                "Card 'Cosmo Queen' not found, maybe you don't run the Import before testing ??"
            );
        }
        $cardCategory = $cosmoQueen->getCategory()->getId();
        $cardAttribute = $cosmoQueen->getAttribute()->getId();
        $cardType = $cosmoQueen->getType()->getId();
        $cardLevel = $cosmoQueen->getProperty();
        $cardLevelValue = (int)$cardLevel->getName();
        $cardLevelType = $cardLevel->getPropertyType()->getId();
        $searchCardRequest = [
            "offset" => 0,
            "limit" => 1,
            "name" => "cosmo",
            "category" => $cardCategory,
            "cardAttribute" => [$cardAttribute],
            "type" => [$cardType],
            "propertyType" => $cardLevelType,
            "property" => [$cardLevelValue, $cardLevelValue],
            "archetype" => NULL,
            "subCategory" => NULL,
            "subPropertyType" => NULL,
            "subProperty" => NULL,
            "subType" => NULL,
            "isPendulum" => "null",
            "isEffect" => "null",
        ];
        $cardService = self::getService(Card::class);
        $archetypeService = self::getService(Archetype::class);
        $cardAttributeService = self::getService(CardAttribute::class);
        $categoryService = self::getService(Category::class);
        $propertyTypeService = self::getService(PropertyType::class);
        $subPropertyTypeService = self::getService(SubPropertyType::class);
        $subTypeService = self::getService(SubType::class);
        $typeService = self::getService(Type::class);
        [
            "error" => $error,
            "card" => $cardInfo
        ] = $this->service->card(
            $jwt,
            $searchCardRequest,
            $cardService,
            $archetypeService,
            $cardAttributeService,
            $categoryService,
            $propertyTypeService,
            $subPropertyTypeService,
            $subTypeService,
            $typeService
        );
        $this->assertEmpty($error);
        $this->assertSame($cardInfo[0]["id"], $cosmoQueen->getId());
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testSearchSet(): void
    {
        $jwt = self::getJWT();
        $setRepository = self::getService(SetRepository::class);
        $sets = $setRepository->findBy(["code" => "RA01"]);
        if (empty($sets) === TRUE) {
            throw new EntityNotFoundException("Set with coe 'RA01' not found, maybe you forgot to run the Import before testing ??");
        }
        $searchSetRequest = [
            "offset" => 0,
            "limit" => 1,
            "code" => $sets[0]->getCode(),
            "yearBegin" => NULL,
            "yearEnd" => NULL,
            "name" => NULL,
        ];
        $setService = self::getService(Set::class);
        [
            "error" => $error,
            "set" => $setInfo
        ] = $this->service->set(
            $jwt,
            $searchSetRequest,
            $setService
        );
        $this->assertEmpty($error);
        $this->assertSame($setInfo[0]["id"], $sets[0]->getId());
    }
}
