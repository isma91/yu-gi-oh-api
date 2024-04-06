<?php

namespace App\Tests\Entity;

use App\Entity\CardPicture;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Exception\NotSupported;

class CardPictureEntityTest extends AbstractTestEntity
{

    /**
     * @param string $cardUuid
     * @param int $idYGO
     * @param string $fileName
     * @return string
     */
    public function getCardPictureUrlEndString(string $cardUuid, int $idYGO, string $fileName): string
    {
        return sprintf(
            "%s/%d/%s",
            $cardUuid,
            $idYGO,
            $fileName
        );
    }

    /**
     * @throws NotSupported
     * @throws EntityNotFoundException
     */
    public function testCardPictureGetUrl(): void
    {
        $cardPicture = self::getOneEntity(CardPicture::class);
        $idYGO = $cardPicture->getIdYGO();
        $cardEntity = $cardPicture->getCard();
        $picture = $cardPicture->getPicture();
        $pictureUrl = $cardPicture->getPictureUrl();
        $pictureSmall = $cardPicture->getPictureSmall();
        $pictureSmallUrl = $cardPicture->getPictureSmallUrl();
        $artwork = $cardPicture->getArtwork();
        $artworkUrl = $cardPicture->getArtworkUrl();
        if ($cardEntity === NULL) {
            $this->assertNull($pictureUrl);
        } else {
            if ($picture === NULL) {
                $this->assertNull($pictureUrl);
            } else {
                $pictureEndString = $this->getCardPictureUrlEndString(
                    $cardEntity->getUuid()->__toString(),
                    $idYGO,
                    $picture
                );
                $this->assertStringEndsWith($pictureEndString, $pictureUrl);
            }
            if ($pictureSmall === NULL) {
                $this->assertNull($pictureSmallUrl);
            } else {
                $pictureSmallEndString = $this->getCardPictureUrlEndString(
                    $cardEntity->getUuid()->__toString(),
                    $idYGO,
                    $pictureSmall
                );
                $this->assertStringEndsWith($pictureSmallEndString, $pictureSmallUrl);
            }
            if ($artwork === NULL) {
                $this->assertNull($artworkUrl);
            } else {
                $artworkEndString = $this->getCardPictureUrlEndString(
                    $cardEntity->getUuid()->__toString(),
                    $idYGO,
                    $artwork
                );
                $this->assertStringEndsWith($artworkEndString, $artworkUrl);
            }
        }
    }
}
