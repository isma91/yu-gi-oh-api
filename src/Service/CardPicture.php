<?php

namespace App\Service;

use App\Entity\Card as CardEntity;
use App\Entity\CardPicture as CardPictureEntity;
use App\Service\Tool\CardPicture\File as CardPictureFileService;
use Exception;

class CardPicture
{
    private CardPictureFileService $cardPictureFileService;

    public function __construct(
        CustomGeneric $customGenericService,
        CardPictureFileService $cardPictureFileService
    )
    {
        $this->cardPictureFileService = $cardPictureFileService;
    }

    /**
     * @param CardEntity $card
     * @param CardPictureEntity $cardPicture
     * @param string $name
     * @return string
     */
    public function getPicture(CardEntity $card, CardPictureEntity $cardPicture, string $name): string
    {
        $file = $this->cardPictureFileService->getDefaultFilePath();
        try {
            $cardPictureCard = $cardPicture->getCard();
            $cardUuid = $card->getUuid();
            if (
                $cardPictureCard === NULL ||
                $cardUuid === NULL ||
                $cardPictureCard->getId() !== $card->getId()
            ) {
                return $file;
            }
            $idYGO = $cardPicture->getIdYGO();
            if ($cardPicture->getPicture() === $name) {
                $fieldName = "picture";
            } elseif ($cardPicture->getPictureSmall() === $name) {
                $fieldName = "pictureSmall";
            } elseif ($cardPicture->getArtwork() === $name) {
                $fieldName = "artwork";
            } else {
                return $file;
            }
            $option = ["uuid" => $cardUuid->__toString(), "idYGO" => $idYGO, "name" => $name];
            $file = $this->cardPictureFileService->getFilePath($fieldName, $option);
        } catch (Exception $e) {
            return $file;
        }
        return $file;
    }
}