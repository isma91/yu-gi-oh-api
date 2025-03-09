<?php
namespace App\Command;
/**
 * DANGER ZONE
 */
ini_set("max_execution_time", 0);
ini_set("max_input_time", 0);
ini_set("memory_limit", -1);

use App\Entity\Card;
use App\Entity\CardCardCollection;
use App\Entity\CardCollection;
use App\Entity\CardExtraDeck;
use App\Entity\CardMainDeck;
use App\Entity\CardPicture;
use App\Entity\CardSet;
use App\Entity\CardSideDeck;
use App\Entity\Country;
use App\Entity\DatabaseYGO;
use App\Entity\SubProperty;
use App\Entity\SubType;
use App\Repository\ArchetypeRepository;
use App\Repository\CardAttributeRepository;
use App\Repository\CardCollectionRepository;
use App\Repository\CardExtraDeckRepository;
use App\Repository\CardMainDeckRepository;
use App\Repository\CardPictureRepository;
use App\Repository\CardRepository;
use App\Repository\CardSetRepository;
use App\Repository\CardSideDeckRepository;
use App\Repository\CategoryRepository;
use App\Repository\CountryRepository;
use App\Repository\DatabaseYGORepository;
use App\Repository\PropertyRepository;
use App\Repository\PropertyTypeRepository;
use App\Repository\RarityRepository;
use App\Repository\SetRepository;
use App\Repository\SubCategoryRepository;
use App\Repository\SubPropertyRepository;
use App\Repository\SubPropertyTypeRepository;
use App\Repository\SubTypeRepository;
use App\Repository\TypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use App\Service\Logger as LoggerService;
use App\Exception\CronException;

#[AsCommand(name: "app:ocg-tcg-converter")]
class OCGTCGConverter extends Command
{
    protected static $defaultName = 'app:ocg-tcg-converter';
    private string $baseUri = "https://db.ygoprodeck.com/api/v7/";
    private array $arrayUri = [
        "card" => "cardinfo.php",
        "db" => "checkDBVer.php",
        "set" => "cardsets.php",
    ];
    private GuzzleClient $guzzleClient;
    private string $cardUploadPath;
    private Country $unitedStateCountry;
    public function __construct(
        ParameterBagInterface $param,
        private readonly EntityManagerInterface $em,
        private readonly DatabaseYGORepository $databaseYGORepository,
        private readonly CardRepository $cardRepository,
        private readonly Filesystem $filesystem,
        private readonly LoggerService $loggerService,
        private readonly CountryRepository $countryRepository,
        private readonly CardCollectionRepository $cardCollectionRepository,
        private readonly CardSideDeckRepository $cardSideDeckRepository,
        private readonly CardMainDeckRepository $cardMainDeckRepository,
        private readonly CardExtraDeckRepository $cardExtraDeckRepository,
        private readonly CardPictureRepository $cardPictureRepository,
        private readonly SubPropertyRepository $subPropertyRepository,
        private readonly SubTypeRepository $subTypeRepository,
        private readonly CardSetRepository $cardSetRepository
    )
    {
        $this->cardUploadPath = $param->get('CARD_UPLOAD_DIR');
        $this->guzzleClient = new GuzzleClient([
            "allow_redirect" => TRUE,
            "http_errors" => TRUE,
            "base_uri" => $this->baseUri
        ]);
        $this->loggerService->setLevel(LoggerService::ERROR)->setIsCron(TRUE);
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setDescription("Check each card in our database to see if we have a duplicate (OCG & TCG) to remove the OCG one.")
            ->addOption(
                "idYGO",
                NULL,
                InputOption::VALUE_REQUIRED,
                'If you want to check a specific card'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'If you want to limit the number of new card added or card with new picture updated.'
            );
    }

    protected function outputDone(OutputInterface $output): void
    {
        $output->writeln('Done !!');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $countryResult = $this->countryRepository->findBy(["alpha3" => "USA"]);
            if (NULL === $countryResult) {
                $this->loggerService->setException(
                    new CronException(
                        "Can't find the united state country !!",
                        $this::$defaultName
                    )
                )->addErrorExceptionOrTrace();
                return Command::FAILURE;
            }
            $this->unitedStateCountry = $countryResult[0];

            $limitOptionValue = $input->getOption("limit");
            $limitCardToCheck = NULL;
            if ($limitOptionValue !== NULL) {
                $limitCardToCheck = (int)$limitOptionValue;
                if ($limitCardToCheck <= 0) {
                    $limitCardToCheck = NULL;
                }
            }

            $checkSpecificCardIdYGO = $input->getOption("idYGO");
            $cardIdYGOToCheck = NULL;
            if ($checkSpecificCardIdYGO !== NULL) {
                $cardIdYGOToCheck = (int)$checkSpecificCardIdYGO;
            }
            $outputStyleInfoBold = new OutputFormatterStyle("green", NULL, ["bold"]);
            $outputStyleBold = new OutputFormatterStyle(NULL, NULL, ["bold"]);
            $output->getFormatter()->setStyle("info-bold", $outputStyleInfoBold);
            $output->getFormatter()->setStyle("bold", $outputStyleBold);

            $output->write('Get current DatabaseYGO info...');
            $databaseYGOEntity = $this->getCurrentDatabaseYGO();
            $this->outputDone($output);

            if ($databaseYGOEntity === NULL) {
                $output->writeln('<error>No Database YGO find locally !!</error>');
                throw new CronException("No Database YGO find locally !!");
            }

            $output->writeln('<info-bold>Check begin...</info-bold>');
            if ($cardIdYGOToCheck === NULL) {
                $output->write('Getting all Card info from local database...');
            } else {
                $output->write(sprintf('Getting the card info with idYGO %d from local database...', $cardIdYGOToCheck));
            }

            if ($cardIdYGOToCheck === NULL) {
                $cardEntityArray = $this->getAllLocalCardMaybeOCG();
            } else {
                $requestCardInfoResult = $this->cardRepository->findOneBy(['idYGO' => $cardIdYGOToCheck]);
                if ($requestCardInfoResult === NULL) {
                    $output->write(sprintf('<error>No card info with idYGO %d from local database found !!</error>', $cardIdYGOToCheck));
                    return Command::FAILURE;
                }
                $cardEntityArray = [
                    $requestCardInfoResult->getIdYGO() => $requestCardInfoResult
                ];
            }
            $this->outputDone($output);
            $countCardChecked = 0;

            /** @var Card $cardEntityMaybeOCG */
            foreach ($cardEntityArray as $cardEntityMaybeOCGIdYGO => $cardEntityMaybeOCG) {
                $output->writeln(
                    sprintf(
                        'Checking Card n°<info-bold>%d</info-bold>...',
                        $countCardChecked
                    )
                );
                if ($limitCardToCheck !== NULL && $countCardChecked >= $limitCardToCheck) {
                    break;
                }
                $cardInfoRemote = $this->getCardInfoFromIdYGO($cardEntityMaybeOCGIdYGO);
                //can only make 10 req/sec to remote api
                sleep(0.5);
                if (null === $cardInfoRemote) {
                    $this->loggerService->setException(
                        new CronException(
                            sprintf(
                                "No Card Info found from ygoprodeck, idYGO => %s",
                                $cardEntityMaybeOCGIdYGO
                            ),
                            $this::$defaultName
                        )
                    )->addErrorExceptionOrTrace();
                    $countCardChecked++;
                    continue;
                }
                $trueIdYGO = $cardInfoRemote['id'];
                if ($cardEntityMaybeOCGIdYGO === $trueIdYGO) {
                    $cardEntityMaybeOCG->setIsMaybeOCG(FALSE);
                    $this->em->persist($cardEntityMaybeOCG);
                    $this->em->flush();
                    $output->writeln(
                        sprintf(
                            'Card <info-bold>%s</info-bold> not OCG...',
                            $cardEntityMaybeOCG->getName()
                        )
                    );
                    $countCardChecked++;
                    $this->outputDone($output);
                    continue;
                }
                $cardEntityTCG = $this->getLocalCardInfoFromIdYGO($trueIdYGO);
                if (null === $cardEntityTCG) {
                    $this->loggerService->setLevel(LoggerService::INFO)
                        ->setException(
                            new CronException(
                                sprintf(
                                    "No Card Info found from database, idYGO => %s",
                                    $trueIdYGO
                                ),
                                $this::$defaultName
                            )
                        )->addErrorExceptionOrTrace();
                    $this->loggerService->setLevel(LoggerService::ERROR);
                    $countCardChecked++;
                    continue;
                }
                $output->writeln(
                    sprintf(
                        'Find one !! The card <info-bold>%s</info-bold> have idYGO <info-bold>%s</info-bold> (OCG) & idYGO <info-bold>%s</info-bold> (TCG)...',
                        $cardEntityMaybeOCG->getName(),
                        $cardEntityMaybeOCGIdYGO,
                        $trueIdYGO
                    )
                );
                $cardEntityIdOCG = $cardEntityMaybeOCG->getId();
                $output->writeln('Remove the OCG card from Collection...');
                $cardCollections = $this->cardCollectionRepository->findAllContainingCard($cardEntityIdOCG);
                if (count($cardCollections) > 0) {
                    $changes = $this->replaceAllOccurrenceToCardCollectionFromOCGToTCG(
                        $cardCollections,
                        $cardEntityMaybeOCG,
                        $cardEntityTCG
                    );
                    $output->writeln('Apply changes in Card Collections...');
                    $this->applyChanges($changes, $output);
                }
                $output->writeln('Remove the OCG card from Main Deck...');
                $mainDecks = $this->cardMainDeckRepository->findAllContainingCard($cardEntityIdOCG);
                if (count($mainDecks) > 0) {
                    $changes = $this->replaceAllOccurrenceToCardMainDeckFromOCGToTCG(
                        $mainDecks,
                        $cardEntityMaybeOCG,
                        $cardEntityTCG
                    );
                    $output->writeln('Apply changes in Main Decks...');
                    $this->applyChanges($changes, $output);
                }
                $output->writeln('Remove the OCG card from Extra Deck...');
                $extraDecks = $this->cardExtraDeckRepository->findAllContainingCard($cardEntityIdOCG);
                if (count($extraDecks) > 0) {
                    $changes = $this->replaceAllOccurrenceToCardExtraDeckFromOCGToTCG(
                        $extraDecks,
                        $cardEntityMaybeOCG,
                        $cardEntityTCG
                    );
                    $output->writeln('Apply changes in Extra Decks...');
                    $this->applyChanges($changes, $output);
                }
                $output->writeln('Remove the OCG card from Side Deck...');
                $sideDecks = $this->cardSideDeckRepository->findAllContainingCard($cardEntityIdOCG);
                if (count($sideDecks) > 0) {
                    $changes = $this->replaceAllOccurrenceToCardSideDeckFromOCGToTCG(
                        $sideDecks,
                        $cardEntityMaybeOCG,
                        $cardEntityTCG
                    );
                    $output->writeln('Apply changes in Side Decks...');
                    $this->applyChanges($changes, $output);
                }
                $output->write('Remove the picture folder of the OCG card...');
                $this->removeCardPictureFolderFromOCG($cardEntityMaybeOCG);
                $this->outputDone($output);
                $output->writeln('Remove all Picture from the OCG card...');
                $cardPictures = $this->cardPictureRepository->findAllContainingCard($cardEntityIdOCG);
                if (count($cardPictures) > 0) {
                    $changes = $this->removeAllCardPictureFromOCG(
                        $cardPictures,
                        $cardEntityMaybeOCG
                    );
                    $output->writeln('Apply changes in Card Pictures...');
                    $this->applyChanges($changes, $output);
                }
                $output->writeln('Remove all Sub Property of the OCG card...');
                $subProperties = $this->subPropertyRepository->findAllContainingCard($cardEntityIdOCG);
                if (count($subProperties) > 0) {
                    $changes = $this->removeAllSubPropertiesFromOCG(
                        $subProperties,
                        $cardEntityMaybeOCG
                    );
                    $output->writeln('Apply changes in Sub Properties...');
                    $this->applyChanges($changes, $output);
                }
                $output->writeln('Remove all Sub Type of the OCG card...');
                $subTypes = $this->subTypeRepository->findAllContainingCard($cardEntityIdOCG);
                if (count($subTypes) > 0) {
                    $changes = $this->removeAllSubTypesFromOCG(
                        $subTypes,
                        $cardEntityMaybeOCG
                    );
                    $output->writeln('Apply changes in Sub Types...');
                    $this->applyChanges($changes, $output);
                }
                $output->writeln('Remove all Card/Rarity Set of the OCG card...');
                $cardSets = $this->cardSetRepository->findAllContainingCard($cardEntityIdOCG);
                if (count($cardSets) > 0) {
                    $changes = $this->removeAllCardRaritySetsFromOCG(
                        $cardSets,
                        $cardEntityMaybeOCG
                    );
                    $output->writeln('Apply changes in Card/Rarity Sets...');
                    $this->applyChanges($changes, $output);
                }
                $output->write('Remove the OCG Card...');
                $this->em->remove($cardEntityMaybeOCG);
                $output->writeln('Flushing... It may take some time...');
                $this->em->flush();
                $this->outputDone($output);
                $countCardChecked++;
            }
            $this->em->clear();
            $output->writeln('<info-bold>Converting Done !!</info-bold>');
        }  catch (Exception $e) {
            $this->loggerService->setException($e)
                ->addErrorExceptionOrTrace();
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

    protected function getCurrentDatabaseYGO(): ?DatabaseYGO
    {
        $result = $this->databaseYGORepository->findBy([], ["id" => "DESC"], 1, 0);
        if (empty($result) === TRUE) {
            return NULL;
        }
        return $result[0];
    }

    /**
     * @param string $uri
     * @return array|null
     * @throws GuzzleException
     * @throws JsonException
     */
    protected function getRequestFromUri(string $uri): ?array
    {
        $request = $this->guzzleClient->get($uri);
        $httpCode = $request->getStatusCode();
        if ($httpCode === 200) {
            return json_decode($request->getBody()->getContents(), TRUE, 512, JSON_THROW_ON_ERROR);
        }
        return NULL;
    }

    /**
     * @param int $idYGO
     * @return array|null
     */
    protected function getCardInfoFromIdYGO(int $idYGO): ?array
    {
        $url = sprintf(
            "%s?id=%d",
            $this->arrayUri["card"],
            $idYGO
        );
        try {
            $response = $this->getRequestFromUri($url);
            if ($response !== NULL) {
                $data = $response["data"];
                if (true === is_array($data) && count($data) > 0) {
                    return $data[0];
                }
            }
        } catch (GuzzleException|JsonException $e) {
            $this->loggerService->setException($e)
                ->addErrorExceptionOrTrace();
        }
        return NULL;
    }

    protected function getLocalCardInfoFromIdYGO(int $idYGO): ?Card
    {
        $cardResult = $this->cardRepository->findBy(['idYGO' => $idYGO]);
        if (0 === count($cardResult)) {
            return null;
        }
        return $cardResult[0];
    }

    protected function getAllLocalCardMaybeOCG(): array
    {
        $array = [];
        $cards = $this->cardRepository->findBy(['isMaybeOCG' => TRUE]);
        foreach ($cards as $card) {
            $array[$card->getIdYGO()] = $card;
        }
        return $array;
    }

    /**
     * Replaces all occurrences of an OCG card with its TCG equivalent in card collections.
     * Also updates collection artwork if it was using an OCG card picture.
     *
     * @param CardCollection[] $cardCollections Collections to process
     * @param Card $cardOCG The OCG card to be replaced
     * @param Card $cardTCG The TCG card to use as replacement
     * @return array Associative array containing entities to remove, create and update
     *               ['remove' => CardCardCollection[], 'create' => CardCardCollection[], 'update' => CardCollection[]]
     * @throws CronException If either card has no pictures
     */
    protected function replaceAllOccurrenceToCardCollectionFromOCGToTCG(
        array $cardCollections,
        Card $cardOCG,
        Card $cardTCG
    ): array
    {
        $cardIdOCG = $cardOCG->getId() ?? 0;
        $now = new \DateTime();
        $toRemove = [];
        $toCreate = [];
        $toUpdate = [];
        $cardOCGPictureIdArray = [];
        $cardOCGPictures = $cardOCG->getPictures();
        if (0 === $cardOCGPictures->count()) {
            throw new CronException(
                sprintf(
                    "No Picture found for Card OCG id => %s",
                    $cardOCG->getId() ?? 0
                ),
                $this::$defaultName
            );
        }
        foreach ($cardOCGPictures as $cardOCGPicture) {
            $cardOCGPictureIdArray[] = $cardOCGPicture->getId();
        }
        $cardTCGPictures = $cardTCG->getPictures();
        if (0 === $cardTCGPictures->count()) {
            throw new CronException(
                sprintf(
                    "No Picture found for Card TCG id => %s",
                    $cardTCG->getId() ?? 0
                ),
                $this::$defaultName
            );
        }
        $cardTCGPicture = $cardTCG->getPictures()->get(0);
        foreach ($cardCollections as $cardCollection) {
            $collectionModified = FALSE;
            $cardCardCollections = $cardCollection->getCardCardCollections();
            $cardCollectionPictureId = $cardCollection->getArtworkId();
            if (
                NULL !== $cardCollectionPictureId &&
                TRUE === in_array($cardCollectionPictureId, $cardOCGPictureIdArray, true)
            ) {
                $cardCollection->setArtwork($cardTCGPicture);
                $collectionModified = TRUE;
            }
            foreach ($cardCardCollections as $cardCardCollection) {
                $cardInCollection = $cardCardCollection->getCard();
                if (null === $cardInCollection || $cardInCollection->getId() !== $cardIdOCG) {
                    continue;
                }
                $collectionModified = TRUE;
                $newCardCardCollection = new CardCardCollection();
                $newCardCardCollection->setCard($cardTCG)
                    ->setCountry($this->unitedStateCountry)
                    ->setNbCopie($cardCardCollection->getNbCopie() ?? 1)
                    ->setPicture($cardTCGPicture)
                    ->setCardCollection($cardCollection)
                    ->setCreatedAt($cardCardCollection->getCreatedAt() ?? $now)
                    ->setUpdatedAt($cardCardCollection->getUpdatedAt() ?? $now);


                $cardCollection->addCardCardCollection($newCardCardCollection)
                    ->removeCardCardCollection($cardCardCollection);

                $toRemove[] = $cardCardCollection;
                $toCreate[] = $newCardCardCollection;
            }
            if (TRUE === $collectionModified) {
                $toUpdate[] = $cardCollection;
            }
        }
        $toUpdate[] = $this->unitedStateCountry;
        return [
            'remove' => $toRemove,
            'create' => $toCreate,
            'update' => $toUpdate
        ];
    }

    /**
     * @param CardMainDeck[] $mainDecks
     * @param Card $cardOCG
     * @param Card $cardTCG
     * @return array
     */
    protected function replaceAllOccurrenceToCardMainDeckFromOCGToTCG(
        array $mainDecks,
        Card $cardOCG,
        Card $cardTCG
    ): array
    {
        $toUpdate = [];
        foreach ($mainDecks as $mainDeck) {
            $mainDeck->removeCard($cardOCG)
                ->addCard($cardTCG);
            $cardOCG->removeCardMainDeck($mainDeck);
            $cardTCG->addCardMainDeck($mainDeck);
            $toUpdate[] = $mainDeck;
        }
        $toUpdate[] = $cardOCG;
        $toUpdate[] = $cardTCG;
        return [
            'remove' => [],
            'create' => [],
            'update' => $toUpdate
        ];
    }

    /**
     * @param CardExtraDeck[] $extraDecks
     * @param Card $cardOCG
     * @param Card $cardTCG
     * @return array
     */
    protected function replaceAllOccurrenceToCardExtraDeckFromOCGToTCG(
        array $extraDecks,
        Card $cardOCG,
        Card $cardTCG
    ): array
    {
        $toUpdate = [];
        foreach ($extraDecks as $extraDeck) {
            $extraDeck->removeCard($cardOCG)
                ->addCard($cardTCG);
            $cardOCG->removeCardExtraDeck($extraDeck);
            $cardTCG->addCardExtraDeck($extraDeck);
            $toUpdate[] = $extraDeck;
        }
        $toUpdate[] = $cardOCG;
        $toUpdate[] = $cardTCG;
        return [
            'remove' => [],
            'create' => [],
            'update' => $toUpdate
        ];
    }

    /**
     * @param CardSideDeck[] $sideDecks
     * @param Card $cardOCG
     * @param Card $cardTCG
     * @return array
     */
    protected function replaceAllOccurrenceToCardSideDeckFromOCGToTCG(
        array $sideDecks,
        Card $cardOCG,
        Card $cardTCG
    ): array
    {
        $toUpdate = [];
        foreach ($sideDecks as $sideDeck) {
            $sideDeck->removeCard($cardOCG)
                ->addCard($cardTCG);
            $cardOCG->removeCardSideDeck($sideDeck);
            $cardTCG->addCardSideDeck($sideDeck);
            $toUpdate[] = $sideDeck;
        }
        $toUpdate[] = $cardOCG;
        $toUpdate[] = $cardTCG;
        return [
            'remove' => [],
            'create' => [],
            'update' => $toUpdate
        ];
    }

    /**
     * @param CardPicture[] $cardPictures
     * @param Card $cardOCG
     * @return array
     */
    protected function removeAllCardPictureFromOCG(array $cardPictures, Card $cardOCG): array
    {
        $toRemove = [];
        $toUpdate = [];
        foreach ($cardPictures as $cardPicture) {
            $cardOCG->removePicture($cardPicture);
            $toRemove[] = $cardPicture;
        }
        $toUpdate[] = $cardOCG;
        return [
            'remove' => $toRemove,
            'create' => [],
            'update' => $toUpdate
        ];
    }

    /**
     * @param Card $cardOCG
     * @return void
     */
    protected function removeCardPictureFolderFromOCG(Card $cardOCG): void
    {
        try {
            $cardUuidString = $this->getEntityUuidAsString($cardOCG);
            $folderPath = sprintf(
                "%s/%s",
                $this->cardUploadPath,
                $cardUuidString
            );
            if (TRUE === $this->filesystem->exists($folderPath)) {
                $this->filesystem->remove($folderPath);
            }
        } catch (IOExceptionInterface|\Exception $e) {
            $this->loggerService->setException($e)
                ->addErrorExceptionOrTrace();
        }
    }

    /**
     * @param SubProperty[] $subProperties
     * @param Card $cardOCG
     * @return array
     */
    protected function removeAllSubPropertiesFromOCG(array $subProperties, Card $cardOCG): array
    {
        $toUpdate = [];
        foreach ($subProperties as $subProperty) {
            $subProperty->removeCard($cardOCG);
            $cardOCG->removeSubProperty($subProperty);
            $toUpdate[] = $subProperty;
        }
        $toUpdate[] = $cardOCG;
        return [
            'create' => [],
            'update' => $toUpdate,
            'remove' => []
        ];
    }

    /**
     * @param SubType[] $subTypes
     * @param Card $cardOCG
     * @return array
     */
    protected function removeAllSubTypesFromOCG(array $subTypes, Card $cardOCG): array
    {
        $toUpdate = [];
        foreach ($subTypes as $subType) {
            $subType->removeCard($cardOCG);
            $cardOCG->removeSubType($subType);
            $toUpdate[] = $subType;
        }
        $toUpdate[] = $cardOCG;
        return [
            'create' => [],
            'update' => $toUpdate,
            'remove' => []
        ];
    }

    /**
     * @param CardSet[] $cardSets
     * @param Card $cardOCG
     * @return array
     */
    protected function removeAllCardRaritySetsFromOCG(array $cardSets, Card $cardOCG): array
    {
        $toRemove = [];
        $toUpdate = [];
        foreach ($cardSets as $cardSet) {
            $rarities = $cardSet->getRarities();
            $sets = $cardSet->getSets();
            if ($rarities->count() > 0) {
                foreach ($rarities as $rarity) {
                    $rarity->removeCardSet($cardSet);
                    $toUpdate[] = $rarity;
                }
            }
            if ($sets->count() > 0) {
                foreach ($sets as $set) {
                    $set->removeCardSet($cardSet);
                    $toUpdate[] = $set;
                }
            }
            $toRemove[] = $cardSet;
            $cardOCG->removeCardSet($cardSet);
        }
        $toUpdate[] = $cardOCG;
        return [
            'create' => [],
            'update' => $toUpdate,
            'remove' => $toRemove
        ];
    }

    protected function getEntityUuidAsString(object $entity): string
    {
        return $entity->getUuid()->__toString();
    }

    /**
     * Applies changes to the database in batches to optimize memory usage
     *
     * This method processes three types of entity changes:
     * - Entities to remove from the database
     * - New entities to create (persist)
     * - Existing entities to update (persist)
     *
     * Operations are executed in a specific order (create, update, remove) to maintain referential integrity.
     * Entities are kept in the persistence context between update and remove operations to ensure proper
     * reference handling, particularly for relationships that need to be updated before related entities
     * can be safely removed.
     *
     * Each type of operation is processed in separate batches to optimize memory usage.
     * If an exception occurs during processing, the EntityManager is cleared and the error is logged.
     *
     * @param array $changes Associative array containing entities to process
     *                       ['remove' => [...], 'create' => [...], 'update' => [...]]
     * @param OutputInterface $output Output Interface to display information to the user
     * @return void
     */
    protected function applyChanges(array $changes, OutputInterface $output): void
    {
        try {
            $output->writeln('Processing Create...');
            $this->processBatch($changes['create'], 'persist');

            $output->writeln('Processing Update...');
            $this->processBatch($changes['update'], 'persist');

            $output->writeln('Processing Remove...');
            $this->processBatch($changes['remove'], 'remove');

        } catch (\Exception $e) {
            $this->loggerService->setException(
                new CronException(
                    sprintf("Error during entity updates: %s", $e->getMessage()),
                    $this::$defaultName
                )
            )->addErrorExceptionOrTrace();
        }
    }

    private function processBatch(array $entities, string $operation): void
    {
        foreach ($entities as $entity) {
            $this->em->$operation($entity);
        }
    }
}