<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Set;
use App\Repository\CardRepository;
use App\Repository\SetRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Exception\ORMException;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

class SetControllerTest extends AbstractWebTestCase
{
    private string $baseUrl = "/set";

    /**
     * @throws JsonException
     */
    public function testSetBase(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testSetGetInfoWithIdNotNumber(): void
    {
        [
            "status" => $status
        ] = static::runProtectedRoute($this->baseUrl . "/info/azerty");
        $this->assertSame(Response::HTTP_NOT_FOUND, $status);
    }

    /**
     * @throws JsonException
     */
    public function testSetGetInfoWithIdNegativeNumber(): void
    {
        [
            "status" => $status
        ] = static::runProtectedRoute($this->baseUrl . "/info/-1234");
        $this->assertSame(Response::HTTP_NOT_FOUND, $status);
    }

    /**
     * @param array $filter
     * @return Set|null
     */
    public function getSetFromFilter(array $filter): ?Set
    {
        self::initiateClient();
        return self::$client->getContainer()->get(SetRepository::class)->findOneBy($filter);
    }

    /**
     * @throws JsonException
     * @throws ORMException
     */
    public function testSetGetInfoWithBadId(): void
    {
        $setId = 0;
        if ($this->getSetFromFilter(["id" => $setId]) !== NULL) {
            throw new ORMException("Set with id => " . $setId . " can't exist !!");
        }
        [
            "status" => $status,
            "content" => $content
        ] = static::runProtectedRoute($this->baseUrl . "/info/" . $setId);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $status);
        $this->assertEmpty($content["data"]["set"]);
    }

    /**
     * @throws JsonException
     * @throws EntityNotFoundException
     */
    public function testSetGetInfoWithGoodId(): void
    {
        $set = $this->getSetFromFilter(["code" => "RA01"]);
        if ($set === NULL) {
            throw new EntityNotFoundException("Set with code 'RA01' not found, maybe you forgot to run the Import before testing ??");
        }
        $ashBlossom = self::$client->getContainer()->get(CardRepository::class)->findOneBy(["slugName" => "ash-blossom-and-joyous-spring"]);
        if ($ashBlossom === NULL) {
            throw new EntityNotFoundException("Card Ash Blossom not found, maybe you forgot to run the Import before testing ??");
        }
        [
            "status" => $status,
            "content" => $content
        ] = static::runProtectedRoute($this->baseUrl . "/info/" . $set->getId());
        $this->assertSame(Response::HTTP_OK, $status);
        $this->assertNotEmpty($content["data"]["set"]);
        $ashBlossomFounded = FALSE;
        foreach ($content["data"]["set"]["cardSets"] as $cardSet) {
            if ($cardSet["card"]["id"] === $ashBlossom->getId()) {
                $ashBlossomFounded = TRUE;
                break;
            }
        }
        $this->assertTrue($ashBlossomFounded);
    }
}
