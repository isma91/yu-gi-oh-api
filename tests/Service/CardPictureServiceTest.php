<?php

namespace App\Tests\Service;

use App\Repository\CardRepository;
use App\Service\CardPicture;
use Doctrine\ORM\EntityNotFoundException;
use function PHPUnit\Framework\fileExists;

class CardPictureServiceTest extends AbstractTestService
{
    private CardPicture $service;

    public function setUp(): void
    {
        $this->service = self::getService(CardPicture::class);
        parent::setUp();
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testCardPictureGetPicture(): void
    {
        $cardRepository = self::getService(CardRepository::class);
        $slugName = "cosmo-queen";
        $result = $cardRepository->findBy(["slugName" => $slugName]);
        if (empty($result) === TRUE) {
            throw new EntityNotFoundException(
                sprintf(
                    "Card with slugName '%s' not found, maybe you don't run Import before testing ??",
                    $slugName
                )
            );
        }
        $card = $result[0];
        $cardPicture = $card->getPictures()[0];
        $name = $cardPicture->getPictureSmall();
        $filePath = $this->service->getPicture($card, $cardPicture, $name);
        $this->assertTrue((bool)fileExists($filePath));
    }
}
