<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\CardRepository;
use Doctrine\ORM\EntityNotFoundException;
use JsonException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use function PHPUnit\Framework\fileExists;

class CardPictureControllerTest extends AbstractWebTestCase
{
    private string $baseUrl = "/card-picture";

    /**
     * @throws JsonException
     */
    public function testCardPictureBase(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * Get Card picture info to be used in route
     * @return array[
     * "uuid" => string,
     * "idYGO" => integer,
     * "name" => string,
     * ]
     * @throws EntityNotFoundException
     */
    public function getCardPictureInfo(): array
    {
        static::initiateClient();
        $cardRepository = self::$client->getContainer()->get(CardRepository::class);
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
        $cardUuid = $result[0]->getUuid()->__toString();
        $cardPicture = $result[0]->getPictures()[0];
        $idYGO = $cardPicture->getIdYGO();
        $name = $cardPicture->getPictureSmall();
        return ["uuid" => $cardUuid, "idYGO" => $idYGO, "name" => $name];
    }

    /**
     * @throws JsonException
     * @throws EntityNotFoundException
     */
    public function testCardPictureGetPicture(): void
    {
        [
            "uuid" => $uuid,
            "idYGO" => $idYGO,
            "name" => $name,
        ] = $this->getCardPictureInfo();
        $url = sprintf(
            "%s/display/%s/%d/%s",
            $this->baseUrl,
            $uuid,
            $idYGO,
            $name
        );
        [
            "status" => $status,
            "content" => $file
        ] = self::getRequestInfo($url, self::REQUEST_GET);
        $this->assertSame(Response::HTTP_OK, $status);
        if ($file instanceof SymfonyFile === FALSE) {
            throw new FileNotFoundException(
                sprintf(
                    "File in 'var/upload/card/%s/%d%s' not found",
                    $uuid, $idYGO, $name
                )
            );
        }
        $this->assertTrue((bool)fileExists($file->getPathname()));
    }
}
