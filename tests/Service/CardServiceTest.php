<?php

namespace App\Tests\Service;

use App\Repository\CardRepository;
use App\Service\Card;
use App\Service\Tool\Card\ORM;
use Doctrine\ORM\EntityNotFoundException;

class CardServiceTest extends AbstractTestService
{
    private Card $service;

    public function setUp(): void
    {
        $this->service = self::getService(Card::class);
        parent::setUp();
    }

    public function testCardGetORMService():void
    {
        $this->assertInstanceOf(ORM::class, $this->service->getORMService());
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testCardGetInfo(): void
    {
        $cardRepository = self::getService(CardRepository::class);
        $cards = $cardRepository->findBy([], NULL, 1);
        if (empty($cards) === TRUE) {
            throw new EntityNotFoundException("No card found, maybe you forgot to run the Import before testing ??");
        }
        $cardToCheck = $cards[0];
        $jwt = self::getJWT();
        [
            "error" => $error,
            "card" => $cardInfo
        ] = $this->service->getCardInfo($jwt, $cardToCheck);
        $this->assertEmpty($error);
        $this->assertSame($cardInfo["id"], $cardToCheck->getId());
    }

    public function testCardGetRandom(): void
    {
        $errors = [];
        $cardIds = [];
        for ($i = 0; $i < 5; $i++) {
            [
                "error" => $error,
                "card" => $card
            ] = $this->service->getRandomCardInfo();
            $errors[] = $error;
            $this->assertNotEmpty($card);
            $cardIds[] = $card["id"];
        }
        $this->assertEmpty(array_filter($errors));
        $this->assertNotEmpty(array_unique($cardIds));
    }
}
