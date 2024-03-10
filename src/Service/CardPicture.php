<?php

namespace App\Service;

use App\Service\Tool\CardPicture\File as CardPictureFileService;
use App\Service\Tool\Card\ORM as CardORMService;
use Exception;

class CardPicture
{
    private CardPictureFileService $cardPictureFileService;
    private CardORMService $cardORMService;

    public function __construct(
        CustomGeneric $customGenericService,
        CardPictureFileService $cardPictureFileService,
        CardORMService $cardORMService
    )
    {
        $this->cardPictureFileService = $cardPictureFileService;
        $this->cardORMService = $cardORMService;
    }

    /**
     * @param string $uuid
     * @param int $idYGO
     * @param string $name
     * @return string
     */
    public function getPicture(string $uuid, int $idYGO, string $name): string
    {
        $file = $this->cardPictureFileService->getDefaultFilePath();
        try {
            $cardEntity = $this->cardORMService->findByUuid($uuid);
            if ($cardEntity === NULL) {
                return $file;
            }
            $cardPictures = $cardEntity->getPictures();
            if ($cardPictures->count() <= 0) {
                return $file;
            }
            $fieldName = NULL;
            foreach ($cardPictures as $cardPicture) {
                if ($cardPicture->getIdYGO() === $idYGO) {
                    if ($cardPicture->getPicture() === $name) {
                        $fieldName = "picture";
                    } elseif ($cardPicture->getPictureSmall() === $name) {
                        $fieldName = "pictureSmall";
                    } elseif ($cardPicture->getArtwork() === $name) {
                        $fieldName = "artwork";
                    }
                }
                if ($fieldName !== NULL) {
                    break;
                }
            }
            if ($fieldName === NULL) {
                return $file;
            }
            $option = ["uuid" => $uuid, "idYGO" => $idYGO, "name" => $name];
            $file = $this->cardPictureFileService->getFilePath($fieldName, $option);
        } catch (Exception $e) {
            return $file;
        }
        return $file;
    }
}