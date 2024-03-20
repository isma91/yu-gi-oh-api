<?php
namespace App\Command;

use App\Entity\Archetype;
use App\Entity\Card;
use App\Entity\CardAttribute;
use App\Entity\CardPicture;
use App\Entity\CardSet;
use App\Entity\Category;
use App\Entity\DatabaseYGO;
use App\Entity\Property;
use App\Entity\PropertyType;
use App\Entity\Rarity;
use App\Entity\Set;
use App\Entity\SubCategory;
use App\Entity\SubProperty;
use App\Entity\SubPropertyType;
use App\Entity\SubType;
use App\Entity\Type;
use App\Repository\ArchetypeRepository;
use App\Repository\CardAttributeRepository;
use App\Repository\CardRepository;
use App\Repository\CategoryRepository;
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
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use GuzzleHttp\Client;
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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(name: "app:import")]
class Import extends Command
{
    private EntityManagerInterface $em;
    private SluggerInterface $slugger;
    protected static $defaultName = 'app:import';
    private string $baseUri = "https://db.ygoprodeck.com/api/v7/";
    private array $arrayUri = [
        "card" => "cardinfo.php",
        "db" => "checkDBVer.php",
        "set" => "cardsets.php",
    ];
    private GuzzleClient $guzzleClient;
    private DatabaseYGORepository $databaseYGORepository;
    private SetRepository $setRepository;
    private CardRepository $cardRepository;
    private CategoryRepository $categoryRepository;
    private SubCategoryRepository $subCategoryRepository;
    private TypeRepository $typeRepository;
    private SubTypeRepository $subTypeRepository;
    private PropertyTypeRepository $propertyTypeRepository;
    private PropertyRepository $propertyRepository;
    private SubPropertyTypeRepository $subPropertyTypeRepository;
    private SubPropertyRepository $subPropertyRepository;
    private ArchetypeRepository $archetypeRepository;
    private RarityRepository $rarityRepository;
    private CardAttributeRepository $cardAttributeRepository;
    private Filesystem $filesystem;
    private string $cardUploadPath;

    /**
     * @param EntityManagerInterface $em
     * @param DatabaseYGORepository $databaseYGORepository
     * @param SetRepository $setRepository
     * @param CardRepository $cardRepository
     * @param ArchetypeRepository $archetypeRepository
     * @param RarityRepository $rarityRepository
     * @param SluggerInterface $slugger
     * @param CategoryRepository $categoryRepository
     * @param SubCategoryRepository $subCategoryRepository
     * @param TypeRepository $typeRepository
     * @param PropertyTypeRepository $propertyTypeRepository
     * @param PropertyRepository $propertyRepository
     * @param SubTypeRepository $subTypeRepository
     * @param SubPropertyTypeRepository $subPropertyTypeRepository
     * @param SubPropertyRepository $subPropertyRepository
     * @param CardAttributeRepository $cardAttributeRepository
     * @param Filesystem $filesystem
     * @param ParameterBagInterface $param
     */
    public function __construct(
        EntityManagerInterface $em,
        DatabaseYGORepository $databaseYGORepository,
        SetRepository $setRepository,
        CardRepository $cardRepository,
        ArchetypeRepository $archetypeRepository,
        RarityRepository $rarityRepository,
        SluggerInterface $slugger,
        CategoryRepository $categoryRepository,
        SubCategoryRepository $subCategoryRepository,
        TypeRepository $typeRepository,
        PropertyTypeRepository $propertyTypeRepository,
        PropertyRepository $propertyRepository,
        SubTypeRepository $subTypeRepository,
        SubPropertyTypeRepository $subPropertyTypeRepository,
        SubPropertyRepository $subPropertyRepository,
        CardAttributeRepository $cardAttributeRepository,
        Filesystem $filesystem,
        ParameterBagInterface $param
    )
    {
        $this->em = $em;
        $this->databaseYGORepository = $databaseYGORepository;
        $this->setRepository = $setRepository;
        $this->cardRepository = $cardRepository;
        $this->categoryRepository = $categoryRepository;
        $this->subCategoryRepository = $subCategoryRepository;
        $this->typeRepository = $typeRepository;
        $this->propertyTypeRepository = $propertyTypeRepository;
        $this->propertyRepository = $propertyRepository;
        $this->subTypeRepository = $subTypeRepository;
        $this->subPropertyTypeRepository = $subPropertyTypeRepository;
        $this->subPropertyRepository = $subPropertyRepository;
        $this->archetypeRepository = $archetypeRepository;
        $this->rarityRepository = $rarityRepository;
        $this->cardAttributeRepository =$cardAttributeRepository;
        $this->slugger = $slugger;
        $this->filesystem = $filesystem;
        $this->cardUploadPath = $param->get('CARD_UPLOAD_DIR');
        $this->guzzleClient = new GuzzleClient([
            "allow_redirect" => TRUE,
            "http_errors" => TRUE,
            "base_uri" => $this->baseUri
        ]);
        parent::__construct();
    }

    /**
     * @return void
     */
    public function configure(): void
    {
        $this
            ->setDescription("Check if we need to add new Card/Set from a json file downloaded from remote api and convert it to our database")
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'If you want to limit the number of new card added or card with new picture updated.'
            )
            ->addOption(
                'no-dbygo-update',
                NULL,
                InputOption::VALUE_NONE,
                'If you want to not update the DatabaseYGO entity, mostly use the first time Import is launch.'
            );
    }

    /**
     * @param string $str
     * @return string
     */
    protected function slugify(string $str): string
    {
        return $this->slugger->slug($str)->lower()->toString();
    }

    /**
     * @param OutputInterface $output
     * @return void
     */
    protected function outputDone(OutputInterface $output): void
    {
        $output->writeln('Done !!');
    }

    /**
     * @param OutputInterface $output
     * @param int $count
     * @param string $entityName
     * @param bool $isNew
     * @return void
     */
    protected function outputCountEntity(
        OutputInterface $output,
        int $count,
        string $entityName,
        bool $isNew = FALSE
    ): void
    {
        $string = ($isNew === TRUE) ? "new" : "current";
        $output->writeln(
            sprintf(
                'Number of %s <bold>%s</bold>: <info-bold>%d</info-bold>',
                $string,
                $entityName,
                $count
            )
        );
    }

    /**
     * @param OutputInterface $output
     * @param string $entityName
     * @return void
     */
    protected function outputGetAllLocally(OutputInterface $output, string $entityName): void
    {
        $output->write(sprintf('Getting all <bold>%s</bold> locally...', $entityName));
    }

    /**
     * @param OutputInterface $output
     * @param string $entityName
     * @param string $name
     * @return void
     */
    protected function outputNewEntityCreated(OutputInterface $output, string $entityName, string $name): void
    {
        $output->writeln(
            sprintf(
                'New <bold>%s</bold> created: <info-bold>%s</info-bold>',
                $entityName,
                $name
            )
        );
    }

    /**
     * @param OutputInterface $output
     * @param string $entityName
     * @return void
     */
    protected function outputAddingEntityToCard(OutputInterface $output, string $entityName): void
    {
        $output->writeln(sprintf('Adding <bold>%s</bold> to Card...', $entityName));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $noDatabaseYgoUpdate = $input->getOption("no-dbygo-update");
            $limitOptionValue = $input->getOption("limit");
            $limitCardToAdd = NULL;
            if ($limitOptionValue !== NULL) {
                $limitCardToAdd = (int)$limitOptionValue;
                if ($limitCardToAdd <= 0) {
                    $limitCardToAdd = NULL;
                }
            }
            $outputStyleInfoBold = new OutputFormatterStyle("green", NULL, ["bold"]);
            $outputStyleBold = new OutputFormatterStyle(NULL, NULL, ["bold"]);
            $output->getFormatter()->setStyle("info-bold", $outputStyleInfoBold);
            $output->getFormatter()->setStyle("bold", $outputStyleBold);
            $output->write('Get current DatabaseYGO info...');
            $databaseYGOEntity = $this->getCurrentDatabaseYGO();
            $this->outputDone($output);

            if ($databaseYGOEntity === NULL) {
                $output->writeln('<comment>No Database YGO find locally !!</comment>');
                $databaseYGOEntity = $this->createDatabaseYGO();
            } else {
                $databaseYGOEntityDate = $databaseYGOEntity->getLastUpdate();
                $output->writeln(
                    sprintf(
                        'Current YGO DB Version: <info-bold>%s</info-bold>',
                        $databaseYGOEntity->getDatabaseVersion()
                    )
                );
                $databaseYGOEntityDateString = $databaseYGOEntityDate?->format("Y-m-d H:i:s");
                $output->writeln(
                    sprintf(
                        'Current YGO DB update date: <info-bold>%s</info-bold>',
                        $databaseYGOEntityDateString
                    )
                );
            }

            $output->write('Get last DatabaseYGO info from URI...');
            $lastDatabaseYGO = $this->getLastDatabaseYGO();
            if ($lastDatabaseYGO === NULL) {
                $output->writeln('Error !!');
                $output->writeln('<error>Can\'t get databaseYGO info !!</error>');
                return Command::FAILURE;
            }
            $this->outputDone($output);

            [
                "database_version" => $dbVersion,
                "last_update" => $dbDatetime
            ] = $lastDatabaseYGO;
            $output->writeln(sprintf('Last DB Version: <info-bold>%s</info-bold>', $dbVersion));
            $output->writeln(sprintf('Last DB update date: <info-bold>%s</info-bold>', $dbDatetime));

            $needImport = $this->compareCurrentAndLastDatabaseYGO($databaseYGOEntity, $lastDatabaseYGO);
            if ($needImport === FALSE) {
                $output->writeln('<info-bold>No import needed !!</info-bold>');
                return Command::SUCCESS;
            }
            $output->writeln('<info-bold>Import begin...</info-bold>');

            $output->write('Getting all Card info from URI...');
            $requestCardInfoArray = $this->getAllCardInfo();
            $this->outputDone($output);

            $this->outputGetAllLocally($output, "Set");
            $setArray = $this->getAllSet();
            $setSlugNameArray = array_keys($setArray);
            $this->outputDone($output);
            $this->outputCountEntity($output, count($setArray), "Set");

            $output->write('Getting all Set info from URI...');
            $setNewArray = $this->getAllNewSet($setSlugNameArray);
            $setNewKeyArray = array_keys($setNewArray);
            $this->outputDone($output);
            $this->outputCountEntity($output, count($setNewArray), "Set", TRUE);

            $this->outputGetAllLocally($output, "Category");
            $categoryArray = $this->getAllCategory();
            $this->outputDone($output);
            $this->outputCountEntity($output, count($categoryArray), "Category");

            $this->outputGetAllLocally($output, "SubCategory");
            $subCategoryArray = $this->getAllSubCategory();
            $subCategoryNewArray = [];
            $this->outputDone($output);
            $this->outputCountEntity($output, count($subCategoryArray), "SubCategory");

            $this->outputGetAllLocally($output, "Archetype");
            $archetypeArray = $this->getAllArchetype();
            $archetypeNewArray = [];
            $this->outputDone($output);
            $this->outputCountEntity($output, count($archetypeArray), "Archetype");

            $this->outputGetAllLocally($output, "Rarity");
            $rarityArray = $this->getAllRarity();
            $rarityNewArray = [];
            $this->outputDone($output);
            $this->outputCountEntity($output, count($rarityArray), "Rarity");

            $this->outputGetAllLocally($output, "Type");
            $typeArray = $this->getAllType();
            $typeNewArray = [];
            $this->outputDone($output);
            $this->outputCountEntity($output, count($typeArray), "Type");

            $this->outputGetAllLocally($output, "SubType");
            $subTypeArray = $this->getAllSubType();
            $subTypeNewArray = [];
            $this->outputDone($output);
            $this->outputCountEntity($output, count($subTypeArray), "SubType");

            $this->outputGetAllLocally($output, "Attribute");
            $attributeArray = $this->getAllAttribute();
            $attributeNewArray = [];
            $this->outputDone($output);
            $this->outputCountEntity($output, count($attributeArray), "Attribute");

            $this->outputGetAllLocally($output, "PropertyType");
            $propertyTypeArray = $this->getAllPropertyType();
            $this->outputDone($output);
            $this->outputCountEntity($output, count($propertyTypeArray), "PropertyType");

            $this->outputGetAllLocally($output, "Property");
            $propertyArray = $this->getAllProperty();
            $this->outputDone($output);
            $this->outputCountEntity($output, count($propertyArray), "Property");

            $this->outputGetAllLocally($output, "SubPropertyType");
            $subPropertyTypeArray = $this->getAllSubPropertyType();
            $this->outputDone($output);
            $this->outputCountEntity($output, count($subPropertyTypeArray), "SubPropertyType");

            $this->outputGetAllLocally($output, "SubProperty");
            $subPropertyArray = $this->getAllSubProperty();
            $this->outputDone($output);
            $this->outputCountEntity($output, count($subPropertyArray), "SubProperty");

            $forbiddenArray = [
                "type" => ["skill"],
                "frameType" => ["skill"],
            ];
            $uselessArray = [
                "type" => ["card"],
            ];
            $cardPictureNewArray = [];
            $cardEntityArray = [];
            $cardSetNewArray = [];
            $countNewCard = 0;
            $subCategoryMonsterArray = [];
            $categoryMonsterEntity = $categoryArray["monster"];
            $categoryMonsterSubCategoryArray = $categoryMonsterEntity->getSubCategories();
            foreach ($categoryMonsterSubCategoryArray as $subCategory) {
                $subCategoryMonsterArray[$subCategory->getSlugName()] = $subCategory;
            }
            $subCategoryMonsterKeyArray = array_keys($subCategoryMonsterArray);
            foreach ($requestCardInfoArray as $cardInfoArray) {
                if ($limitCardToAdd !== NULL && $countNewCard >= $limitCardToAdd) {
                    break;
                }
                [
                    "id" => $cardInfoId,
                    "name" => $cardInfoName,
                    "desc" => $cardInfoDesc,
                    "type" => $cardInfoType,
                    "frameType" => $cardInfoFrameType,
                    "race" => $cardInfoRace,
                    "card_images" => $cardInfoCardImageArray,
                ] = $cardInfoArray;
                $cardInfoTypeArray = explode(" ", $cardInfoType);
                $cardInfoTypeArrayWithSlugNameAsKey = $this->addSlugNameAsKeyInArray($cardInfoTypeArray);
                $cardInfoTypeArray = array_keys($cardInfoTypeArrayWithSlugNameAsKey);
                //mostly skill card but, we never know with Konami...
                if ($this->inArrayAll($forbiddenArray["type"], $cardInfoTypeArray) === TRUE) {
                    continue;
                }
                $cardInfoTypeArray = $this->deleteMultipleArrayElement($cardInfoTypeArray, $uselessArray["type"]);

                $newCard = FALSE;
                $updateCardPicture = FALSE;
                $cardEntity = $this->cardRepository->findOneBy(["idYGO" => $cardInfoId]);
                if ($cardEntity === NULL) {
                    $output->writeln(
                        sprintf(
                            'Card  <info-bold>%s</info-bold> new (new card n°<info-bold>%d</info-bold>) !! Creating from scratch...',
                            $cardInfoName,
                            $countNewCard
                        )
                    );
                    $newCard = TRUE;
                    $cardEntity = new Card();
                    $cardEntity->setUuid(Uuid::v7())
                        ->setIdYGO($cardInfoId)
                        ->setName($cardInfoName)
                        ->setSlugName($this->slugify($cardInfoName))
                        ->setDescription($cardInfoDesc)
                        ->setSlugDescription($this->slugify($cardInfoDesc));
                } else {
                    $output->writeln(
                        sprintf(
                            'Card <info-bold>%s</info-bold> already existed !! Try to update it...',
                            $cardInfoName
                        )
                    );
                }
                $cardEntityUuidString = $this->getEntityUuidAsString($cardEntity);
                $cardInfoRaceSlugName = $this->slugify($cardInfoRace);
                //Initiate all variable !!
                $isEffect = NULL;
                $categoryEntity = NULL;
                $subCategoryEntity = NULL;
                $cardCategorySlugName = NULL;
                $cardInfoArchetype = NULL;
                $archetypeEntity = NULL;
                $rarityEntity = NULL;
                $cardInfoCardSetArray = [];
                $cardInfoAttribute = NULL;
                $cardInfoPendulumScale = NULL;
                $cardInfoLinkRating = NULL;
                $cardInfoLinkArrowArray = [];
                $cardInfoLevel = NULL;
                $isXYZ = FALSE;
                $isLink = FALSE;
                $isToken = FALSE;
                $isPendulum = FALSE;
                $cardPendulumDescription = NULL;
                $cardPendulumMonsterDescription = NULL;
                if (isset($cardInfoArray["archetype"]) === TRUE) {
                    $cardInfoArchetype = $cardInfoArray["archetype"];
                }
                if (isset($cardInfoArray["card_sets"]) === TRUE) {
                    $cardInfoCardSetArray = $cardInfoArray["card_sets"];
                }
                if (isset($cardInfoArray["attribute"]) === TRUE) {
                    $cardInfoAttribute = $cardInfoArray["attribute"];
                }
                if (isset($cardInfoArray["scale"]) === TRUE) {
                    $cardInfoPendulumScale = (int)$cardInfoArray["scale"];
                }
                if (isset($cardInfoArray["linkval"]) === TRUE) {
                    $cardInfoLinkRating = (int)$cardInfoArray["linkval"];
                }
                if (isset($cardInfoArray["linkmarkers"]) === TRUE) {
                    $cardInfoLinkArrowArray = $cardInfoArray["linkmarkers"];
                }
                if (isset($cardInfoArray["level"]) === TRUE) {
                    $cardInfoLevel = $cardInfoArray["level"];
                }
                if (isset($cardInfoArray["pend_desc"]) === TRUE) {
                    $cardPendulumDescription = $cardInfoArray["pend_desc"];
                }
                if (isset($cardInfoArray["monster_desc"]) === TRUE) {
                    $cardPendulumMonsterDescription = $cardInfoArray["monster_desc"];
                }

                if (in_array("token", $cardInfoTypeArray, TRUE) === TRUE) {
                    $cardCategorySlugName = "token";
                }

                $monsterKeyNumber = array_search("monster", $cardInfoTypeArray, TRUE);
                $isMonster = $monsterKeyNumber !== FALSE;
                if ($cardCategorySlugName === "token") {
                    $isToken = TRUE;
                    $isEffect = FALSE;
                } elseif ($isMonster === TRUE) {
                    $cardCategorySlugName = "monster";
                    array_splice($cardInfoTypeArray, $monsterKeyNumber, 1);
                    $effectKeyNumber = array_search("effect", $cardInfoTypeArray, TRUE);
                    $normalKeyNumber = array_search("normal", $cardInfoTypeArray, TRUE);
                    $isEffect = $effectKeyNumber !== FALSE;
                    //sometimes we don't have "normal" or "effect" as type like link monster
                    if ($normalKeyNumber !== FALSE || $effectKeyNumber !== FALSE) {
                        $keyNumberToUse = ($isEffect === TRUE) ? $effectKeyNumber : $normalKeyNumber;
                        array_splice($cardInfoTypeArray, $keyNumberToUse, 1);
                    }
                    //sometime either "normal" or "effect" is not so, we check the frame type with "normal"
                    //or "ritual" because at this case all ritual are basic one
                    if ($normalKeyNumber === FALSE && $effectKeyNumber === FALSE) {
                        $frameTypeNormal = str_contains($cardInfoFrameType, "normal");
                        $frameTypeRitual = str_contains($cardInfoFrameType, "ritual");
                        $isEffect = !($frameTypeNormal === TRUE || $frameTypeRitual === TRUE);
                    }
                    if (empty($cardInfoTypeArray) === FALSE) {
                        $this->outputAddingEntityToCard($output, "SubType");
                        foreach ($cardInfoTypeArray as $cardInfoSubTypeSlugName) {
                            $subTypeIsSubCategoryMonster = in_array($cardInfoSubTypeSlugName, $subCategoryMonsterKeyArray, TRUE);
                            if ($subTypeIsSubCategoryMonster === FALSE) {
                                $cardInfoSubType = $cardInfoTypeArrayWithSlugNameAsKey[$cardInfoSubTypeSlugName];
                                $subTypeEntity = $this->findSubTypeFromSubTypeArray($cardInfoSubTypeSlugName, $subTypeArray);
                                if ($subTypeEntity === NULL) {
                                    $subTypeNewKeyArray = array_keys($subTypeNewArray);
                                    if (in_array($cardInfoSubTypeSlugName, $subTypeNewKeyArray, TRUE) === TRUE) {
                                        $subTypeEntity = $subTypeNewArray[$cardInfoSubTypeSlugName];
                                    } else {
                                        $subTypeEntity = $this->createSubType($cardInfoSubType);
                                        $subTypeNewArray[$cardInfoSubTypeSlugName] = $subTypeEntity;
                                        $this->outputNewEntityCreated(
                                            $output,
                                            "SubType",
                                            $cardInfoSubType
                                        );
                                    }
                                }
                                $cardEntity->addSubType($subTypeEntity);
                                $subTypeEntity->addCard($cardEntity);
                                $this->em->persist($subTypeEntity);
                            } else {
                                $subCategoryMonster = $subCategoryMonsterArray[$cardInfoSubTypeSlugName];
                                if ($subCategoryMonster === NULL) {
                                    //@todo add to logger
                                    [
                                        "newArray" => $subTypeNewArray,
                                        "array" => $subTypeArray
                                    ] = $this->removeCardEntityFromAllSubTypeArray(
                                        $cardEntity,
                                        $subTypeArray,
                                        $subTypeNewArray
                                    );
                                    continue;
                                }
                                //some ritual are normal monster, extra-deck monster are all isEffect from the api
                                if ($isEffect === FALSE && $subCategoryMonster->getSlugName() === "ritual") {
                                    $isEffect = FALSE;
                                } else {
                                    $isEffect = TRUE;
                                }
                                $cardEntity->setSubCategory($subCategoryMonster);
                            }
                            if ($cardInfoSubTypeSlugName === "pendulum") {
                                if ($cardInfoPendulumScale === NULL) {
                                    [
                                        "newArray" => $subTypeNewArray,
                                        "array" => $subTypeArray
                                    ] = $this->removeCardEntityFromAllSubTypeArray(
                                        $cardEntity,
                                        $subTypeArray,
                                        $subTypeNewArray
                                    );
                                    //@todo: add to logger
                                    continue;
                                }
                                $subPropertyTypeEntity = $subPropertyTypeArray["pendulum-scale"];
                                $subPropertyEntity = $this->findSubProperty(
                                    $cardInfoPendulumScale,
                                    $subPropertyArray,
                                    $subPropertyTypeEntity,
                                );
                                if ($subPropertyEntity === NULL) {
                                    [
                                        "newArray" => $subTypeNewArray,
                                        "array" => $subTypeArray
                                    ] = $this->removeCardEntityFromAllSubTypeArray(
                                        $cardEntity,
                                        $subTypeArray,
                                        $subTypeNewArray
                                    );
                                    //@todo: add to logger
                                    continue;
                                }
                                $subPropertyEntity->addCard($cardEntity);
                                $cardEntity->addSubProperty($subPropertyEntity)
                                    ->setPendulumDescription($cardPendulumDescription)
                                    ->setMonsterDescription($cardPendulumMonsterDescription);
                                $isPendulum = TRUE;
                            } elseif ($cardInfoSubTypeSlugName === "link") {
                                $isLink = TRUE;
                                $subPropertyTypeEntity = $subPropertyTypeArray["link-arrow"];
                                foreach ($cardInfoLinkArrowArray as $cardInfoLinkArrow) {
                                    $subPropertyEntity = $this->findSubProperty(
                                        $cardInfoLinkArrow,
                                        $subPropertyArray,
                                        $subPropertyTypeEntity,
                                    );
                                    if ($subPropertyEntity === NULL) {
                                        [
                                            "newArray" => $subTypeNewArray,
                                            "array" => $subTypeArray
                                        ] = $this->removeCardEntityFromAllSubTypeArray(
                                            $cardEntity,
                                            $subTypeArray,
                                            $subTypeNewArray
                                        );
                                        //@TODO: add to logger
                                        continue;
                                    }
                                    $subPropertyEntity->addCard($cardEntity);
                                    $cardEntity->addSubProperty($subPropertyEntity);
                                }
                            } elseif ($cardInfoSubTypeSlugName === "xyz") {
                                $isXYZ = TRUE;
                            }
                        }
                    }
                } else {
                    $cardCategorySlugName = $cardInfoTypeArray[0];
                }
                $cardEntity->setIsEffect($isEffect)
                    ->setIsPendulum($isPendulum);
                $categoryEntity = $this->findCategoryFromCategoryArray($cardCategorySlugName, $categoryArray);
                if ($categoryEntity === NULL) {
                    [
                        "newArray" => $subTypeNewArray,
                        "array" => $subTypeArray
                    ] = $this->removeCardEntityFromAllSubTypeArray(
                        $cardEntity,
                        $subTypeArray,
                        $subTypeNewArray
                    );
                    //@TODO: Put in logger
                    continue;
                }
                //Category who are not Token nor Monster are only Spell/Trap card
                if ($isToken === FALSE && $isMonster === FALSE) {
                    $this->outputAddingEntityToCard($output, "SubCategory");
                    $subCategoryEntity = $this->findSubCategory($cardInfoRaceSlugName, $subCategoryArray, $categoryEntity);
                    if ($subCategoryEntity === NULL) {
                        $subCategoryNewKeyArray = array_keys($subCategoryNewArray);
                        if (in_array($cardInfoRaceSlugName, $subCategoryNewKeyArray, TRUE) === TRUE) {
                            $subCategoryEntity = $subCategoryNewArray[$cardInfoRaceSlugName];
                        } else {
                            $subCategoryEntity = $this->createSubCategory($cardInfoRace);
                            $subCategoryNewArray[$cardInfoRaceSlugName] = $subCategoryEntity;
                            $this->outputNewEntityCreated(
                                $output,
                                "SubCategory",
                                $cardInfoRace
                            );
                        }
                    }
                    $categoryEntity->addSubCategory($subCategoryEntity);
                    $cardEntity->setSubCategory($subCategoryEntity);
                    $this->em->persist($subCategoryEntity);
                } else {
                    $cardEntity = $this->setAtkDefPoint($cardEntity, $cardInfoArray);
                    $this->outputAddingEntityToCard($output, "Type");
                    $monsterTypeEntity = $this->findTypeFromTypeArray($cardInfoRaceSlugName, $typeArray);
                    if ($monsterTypeEntity === NULL) {
                        $typeNewKeyArray = array_keys($typeNewArray);
                        if (in_array($cardInfoRaceSlugName, $typeNewKeyArray, TRUE) === TRUE) {
                            $monsterTypeEntity = $typeNewArray[$cardInfoRaceSlugName];
                        } else {
                            $monsterTypeEntity = $this->createType($cardInfoRace);
                            $typeNewArray[$cardInfoRaceSlugName] = $monsterTypeEntity;
                            $this->outputNewEntityCreated(
                                $output,
                                "Type",
                                $cardInfoRace
                            );
                        }
                    }
                    $cardEntity->setType($monsterTypeEntity);
                    $this->em->persist($monsterTypeEntity);
                    if ($cardInfoAttribute === NULL) {
                        $monsterTypeEntity->removeCard($cardEntity);
                        [
                            "newArray" => $subTypeNewArray,
                            "array" => $subTypeArray
                        ] = $this->removeCardEntityFromAllSubTypeArray(
                            $cardEntity,
                            $subTypeArray,
                            $subTypeNewArray
                        );
                        //@todo: add to logger
                        continue;
                    }
                    $cardInfoAttributeSlugName = $this->slugify($cardInfoAttribute);
                    $this->outputAddingEntityToCard($output, "Attribute");
                    $cardAttributeEntity = $this->findAttributeFromAttributeArray($cardInfoAttributeSlugName, $attributeArray);
                    if ($cardAttributeEntity === NULL) {
                        $attributeNewKeyArray = array_keys($attributeNewArray);
                        if (in_array($cardInfoAttributeSlugName, $attributeNewKeyArray, TRUE) === TRUE) {
                            $cardAttributeEntity = $attributeNewArray[$cardInfoAttributeSlugName];
                        } else {
                            $cardAttributeEntity = $this->createAttribute($cardInfoAttribute);
                            $attributeNewArray[$cardInfoAttributeSlugName] = $cardAttributeEntity;
                            $this->outputNewEntityCreated(
                                $output,
                                "Attribute",
                                $cardInfoAttribute
                            );
                        }
                    }
                    $cardEntity->setAttribute($cardAttributeEntity);
                    $this->em->persist($cardAttributeEntity);
                    $cardInfoPropertyValue = NULL;
                    if ($isLink === TRUE) {
                        if ($cardInfoLinkRating === NULL) {
                            $cardAttributeEntity->removeCard($cardEntity);
                            [
                                "newArray" => $subTypeNewArray,
                                "array" => $subTypeArray
                            ] = $this->removeCardEntityFromAllSubTypeArray(
                                $cardEntity,
                                $subTypeArray,
                                $subTypeNewArray
                            );
                            //@todo add to logger
                            continue;
                        }
                        $cardInfoPropertyValue = $cardInfoLinkRating;
                        $propertyTypeEntity = $propertyTypeArray["link-rating"];
                    } else {
                        if ($cardInfoLevel === NULL) {
                            $cardAttributeEntity->removeCard($cardEntity);
                            [
                                "newArray" => $subTypeNewArray,
                                "array" => $subTypeArray
                            ] = $this->removeCardEntityFromAllSubTypeArray(
                                $cardEntity,
                                $subTypeArray,
                                $subTypeNewArray
                            );
                            //@todo: add to logger
                            continue;
                        }
                        $cardInfoPropertyValue = $cardInfoLevel;
                        $propertyTypeSlugName = ($isXYZ === TRUE) ? "rank" : "level";
                        $propertyTypeEntity = $propertyTypeArray[$propertyTypeSlugName];
                    }
                    $this->outputAddingEntityToCard($output, "Property");
                    $propertyEntity = $this->findProperty(
                        $cardInfoPropertyValue,
                        $propertyArray,
                        $propertyTypeEntity
                    );
                    if ($propertyEntity === NULL) {
                        [
                            "newArray" => $subTypeNewArray,
                            "array" => $subTypeArray
                        ] = $this->removeCardEntityFromAllSubTypeArray(
                            $cardEntity,
                            $subTypeArray,
                            $subTypeNewArray
                        );
                        //@todo: add to logger
                        continue;
                    }
                    $cardEntity->setProperty($propertyEntity);
                    $this->em->persist($propertyEntity);
                }
                $cardEntity->setCategory($categoryEntity);
                if ($cardInfoArchetype !== NULL && $cardEntity->getArchetype() === NULL) {
                    $this->outputAddingEntityToCard($output, "Archetype");
                    $cardInfoArchetypeSlugName = $this->slugify($cardInfoArchetype);
                    $archetypeEntity = $this->findArchetypeFromArchetypeArray($cardInfoArchetypeSlugName, $archetypeArray);
                    if ($archetypeEntity === NULL) {
                        $archetypeNewKeyArray = array_keys($archetypeNewArray);
                        if (in_array($cardInfoArchetypeSlugName, $archetypeNewKeyArray, TRUE) === TRUE) {
                            $archetypeEntity = $archetypeNewArray[$cardInfoArchetypeSlugName];
                        } else {
                            $archetypeEntity = $this->createArchetype($cardInfoArchetype);
                            $archetypeNewArray[$cardInfoArchetypeSlugName] = $archetypeEntity;
                            $this->outputNewEntityCreated(
                                $output,
                                'Archetype',
                                $cardInfoArchetype
                            );
                        }
                    }
                    $cardEntity->setArchetype($archetypeEntity);
                    $this->em->persist($archetypeEntity);
                }

                if (count($cardInfoCardSetArray) > $cardEntity->getCardSets()->count()) {
                    $this->outputAddingEntityToCard($output, "CardSet");
                    foreach ($cardInfoCardSetArray as $cardSetInfoArray) {
                        [
                            "set_code" => $cardSetInfoCode,
                            "set_name" => $cardSetInfoName,
                            "set_rarity" => $cardSetInfoRarity
                        ] = $cardSetInfoArray;
                        $cardSetInfoSlugName = $this->slugify($cardSetInfoName);
                        $cardSetInfoRaritySlugName = $this->slugify($cardSetInfoRarity);
                        $cardSetInfoCodeArray = explode("-", $cardSetInfoCode);
                        $cardInfoSetCode = $cardSetInfoCodeArray[0];
                        $cardSetCode = NULL;
                        if (count($cardSetInfoCodeArray) === 2) {
                            $cardSetCode = $cardSetInfoCodeArray[1];
                        }
                        $cardSetAlreadyExist = $this->checkIfCardEntityHaveCardSet(
                            $cardEntity,
                            $cardSetInfoName,
                            $cardSetCode,
                            $cardSetInfoRaritySlugName
                        );
                        if ($cardSetAlreadyExist === FALSE) {
                            $setEntity = $this->findSetFromSetArray($cardSetInfoSlugName, $setArray);
                            if ($setEntity === NULL) {
                                if (in_array($cardSetInfoSlugName, $setNewKeyArray, TRUE) === FALSE) {
                                    continue;
                                }
                                $setEntity = $setNewArray[$cardSetInfoSlugName];
                            }
                            $rarityEntity = $this->findRarityFromRarityArray($cardSetInfoRaritySlugName, $rarityArray);
                            if ($rarityEntity === NULL) {
                                $rarityNewKeyArray = array_keys($rarityNewArray);
                                if (in_array($cardSetInfoRaritySlugName, $rarityNewKeyArray, TRUE) === TRUE) {
                                    $rarityEntity = $rarityNewArray[$cardSetInfoRaritySlugName];
                                } else {
                                    $rarityEntity = $this->createRarity($cardSetInfoRarity);
                                    $rarityNewArray[$cardSetInfoRaritySlugName] = $rarityEntity;
                                    $this->outputNewEntityCreated(
                                        $output,
                                        'Rarity',
                                        $cardSetInfoRarity
                                    );
                                }
                            }
                            $cardSetEntity = $this->createCardSet($setEntity, $rarityEntity, $cardSetCode);
                            $output->write('New <bold>CardSet</bold> created: ');
                            $output->write(
                                sprintf(
                                    '<bold>Set</bold>: <info-bold>%s</info-bold> ',
                                    $setEntity->getName(),
                                )
                            );
                            $output->writeln(
                                sprintf(
                                    '<bold>Rarity</bold>: <info-bold>%s</info-bold>',
                                    $rarityEntity->getName(),
                                )
                            );
                            $setEntity->addCardSet($cardSetEntity);
                            $rarityEntity->addCardSet($cardSetEntity);
                            $cardSetEntity->addSet($setEntity)
                                ->addRarity($rarityEntity)
                                ->setCard($cardEntity);
                            $cardEntity->addCardSet($cardSetEntity);
                            $this->em->persist($setEntity);
                            $this->em->persist($rarityEntity);
                            $this->em->persist($cardSetEntity);
                            $cardSetNewArray[] = $cardSetEntity;
                        }
                    }
                }

                if (count($cardInfoCardImageArray) > $cardEntity->getPictures()->count()) {
                    $this->outputAddingEntityToCard($output, "CardPicture");
                    foreach ($cardInfoCardImageArray as $cardInfoCardImageInfoArray) {
                        [
                            "id" => $pictureIdYGO,
                            "image_url" => $pictureUrl,
                            "image_url_small" => $pictureSmallUrl,
                            "image_url_cropped" => $artworkUrl,
                        ] = $cardInfoCardImageInfoArray;
                        $cardPictureExist = $this->checkIfCardEntityHaveCardPicture($cardEntity, $pictureIdYGO);
                        if ($cardPictureExist === FALSE) {
                            $updateCardPicture = TRUE;
                            $symfonyFilePictureArray = [
                                "picture" => $this->createFileFromUrl($pictureUrl),
                                "pictureSmall" => $this->createFileFromUrl($pictureSmallUrl),
                                "artwork" => $this->createFileFromUrl($artworkUrl),
                            ];
                            //can only make 10 req/sec to remote api
                            sleep(0.5);
                            $cardPictureEntity = new CardPicture();
                            $currentDateTimePicture = new DateTime();
                            foreach ($symfonyFilePictureArray as $pictureType => $file) {
                                if ($file === NULL) {
                                    //@todo: add to logger
                                    continue;
                                }
                                $methodName = "set" . lcfirst($pictureType);
                                $filename = sprintf(
                                    "%s.%s",
                                    $this->getUniqueNameFromPrefix($pictureType),
                                    $this->getExtensionFromFile($file)
                                );
                                $this->moveCard($cardEntityUuidString, $pictureIdYGO, $file, $filename);
                                $cardPictureEntity->$methodName($filename);
                            }
                            $cardPictureEntity->setCreatedAt($currentDateTimePicture)
                                ->setUpdatedAt($currentDateTimePicture)
                                ->setCard($cardEntity)
                                ->setIdYGO($pictureIdYGO);
                            $cardPictureNewArray[] = $cardPictureEntity;
                            $cardEntity->addPicture($cardPictureEntity);
                            $this->em->persist($cardPictureEntity);
                        }
                    }
                }
                //we only count when we need to make request to the remote api to avoid blacklist for too much request
                if ($newCard === TRUE || $updateCardPicture === TRUE) {
                    $countNewCard++;
                }
                $cardEntityArray[] = $cardEntity;
                $this->em->persist($cardEntity);
            }
            if ($noDatabaseYgoUpdate === FALSE) {
                $output->write('Updating <bold>DatabaseYGO</bold>...');
                $databaseYGOEntity->setDatabaseVersion((float)$dbVersion)
                    ->setLastUpdate(new DateTime($dbDatetime));
                $this->em->persist($databaseYGOEntity);
                $this->outputDone($output);
            }
            $entityArray = [
                $archetypeArray,
                $attributeArray,
                $categoryArray,
                $propertyArray,
                $propertyTypeArray,
                $rarityArray,
                $setArray,
                $subCategoryArray,
                $subPropertyArray,
                $subPropertyTypeArray,
                $subTypeArray,
                $typeArray,
            ];
            $newEntityArray = [
                "Archetype" =>  $archetypeNewArray,
                "Attribute" =>  $attributeNewArray,
                "CardPicture" =>  $cardPictureNewArray,
                "CardSet" =>  $cardSetNewArray,
                "Rarity" =>  $rarityNewArray,
                "Set" =>  $setNewArray,
                "SubCategory" =>  $subCategoryNewArray,
                "SubType" =>  $subTypeNewArray,
                "Type" =>  $typeNewArray,
            ];
            $output->writeln('Persisting already existed <bold>Entities</bold>...');
            foreach ($entityArray as $array) {
                foreach ($array as $entity) {
                    $this->em->persist($entity);
                }
            }
            $output->writeln("Persisting new <bold>Entities</bold>...");
            foreach ($newEntityArray as $entityName => $array) {
                $output->writeln(
                    sprintf(
                        'New <bold>%s</bold> created: <info-bold>%d</info-bold>',
                        $entityName,
                        count($array)
                    )
                );
                foreach ($array as $entity) {
                    $this->em->persist($entity);
                }
            }
            $output->writeln(
                sprintf(
                    'New <bold>%s</bold> created: <info-bold>%d</info-bold>',
                    'Card',
                    $countNewCard
                )
            );
            foreach ($cardEntityArray as $cardEntity) {
                $this->em->persist($cardEntity);
            }
            $output->writeln('Flushing... It may take some time...');
            $this->em->flush();
            $output->writeln('<info-bold>Import Done !!</info-bold>');
        }  catch (GuzzleException $e) {
            $url = $e->getRequest()->getUri()->__toString();
            dd($url, $e->getMessage());
            return Command::FAILURE;
        } catch (JsonException|Exception $e) {
            dd($e);
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

    /**
     * @return DatabaseYGO|null
     */
    protected function getCurrentDatabaseYGO(): ?DatabaseYGO
    {
        $result = $this->databaseYGORepository->findBy([], ["id" => "DESC"], 1, 0);
        if (empty($result) === TRUE) {
            return NULL;
        }
        return $result[0];
    }

    /**
     * @return DatabaseYGO
     */
    protected function createDatabaseYGO(): DatabaseYGO
    {
        $databaseYGOEntity = new DatabaseYGO();
        $current = new DateTime();
        return $databaseYGOEntity->setCreatedAt($current)
            ->setUpdatedAt($current);
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
        //@TODO: use a custom logger
        return NULL;
    }

    /**
     * @return array|null
     * @throws GuzzleException
     * @throws JsonException
     */
    protected function getLastDatabaseYGO(): ?array
    {
        $response = $this->getRequestFromUri($this->arrayUri["db"]);
        if ($response !== NULL) {
            return $response[0];
        }
        return NULL;
    }

    /**
     * @param DatabaseYGO|null $databaseYGOEntity
     * @param array $lastDatabaseYGOInfo
     * @return bool
     */
    protected function compareCurrentAndLastDatabaseYGO(
        ?DatabaseYGO $databaseYGOEntity,
        array $lastDatabaseYGOInfo
    ): bool
    {
        if ($databaseYGOEntity === NULL) {
            return TRUE;
        }
        $currentDBVersion = $databaseYGOEntity->getDatabaseVersion();
        $currentDBDate = $databaseYGOEntity->getLastUpdate();
        [
            "database_version" => $lastDBVersion,
            "last_update" => $lastDBDate
        ] = $lastDatabaseYGOInfo;
        $lastDBVersion = (float)$lastDBVersion;
        return $currentDBDate < $lastDBDate || $currentDBVersion < $lastDBVersion;
    }

    /**
     * @return array|null
     * @throws GuzzleException
     * @throws JsonException
     */
    protected function getAllCardInfo(): ?array
    {
        $response = $this->getRequestFromUri($this->arrayUri["card"]);
        if ($response !== NULL) {
            return $response["data"];
        }
        return NULL;
    }

    /**
     * @param object $repository
     * @param string $fieldName
     * @return array[string => Entity]
     */
    protected function getAllFromRepository(object $repository, string $fieldName = "slugName"): array
    {
        $entities = $repository->findAll();
        $array = [];
        $methodName = "get" . ucfirst($fieldName);
        foreach ($entities as $entity) {
            $string = $entity->$methodName();
            $array[$string] = $entity;
        }
        return $array;
    }

    /**
     * @return array[string => Set]
     */
    protected function getAllSet(): array
    {
        return $this->getAllFromRepository($this->setRepository);
    }

    /**
     * @return array
     * @throws GuzzleException
     * @throws JsonException
     */
    protected function getAllSetInfo(): array
    {
        $response = $this->getRequestFromUri($this->arrayUri["set"]);
        return $response ?? [];
    }

    /**
     * @param array $setSlugNameArray
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    protected function getAllNewSet(array $setSlugNameArray): array
    {
        $setNewArray = [];
        $requestCardSetArray = $this->getAllSetInfo();
        foreach ($requestCardSetArray as $cardSetArray) {
            [
                "set_name" => $setName,
                "set_code" => $setCode,
                "num_of_cards" => $nbCard,
                "tcg_date" => $releaseDate,
            ] = $cardSetArray;
            $setSlugName = $this->slugify($setName);
            if (in_array($setSlugName, $setSlugNameArray, TRUE) === FALSE) {
                $current = new DateTime();
                $releaseDateTimestamp = strtotime($releaseDate);
                if ($releaseDateTimestamp <= 0 || $releaseDateTimestamp === FALSE) {
                    $releaseDateTime = NULL;
                } else {
                    $releaseDateTime = new DateTime($releaseDate);
                }
                if (empty($setCode) === TRUE) {
                    $setCode = "";
                }
                $setEntity = new Set();
                $setEntity->setName($setName)
                    ->setSlugName($setSlugName)
                    ->setCode($setCode)
                    ->setNbCard($nbCard)
                    ->setReleaseDate($releaseDateTime)
                    ->setCreatedAt($current)
                    ->setUpdatedAt($current);
                $setNewArray[$setSlugName] = $setEntity;
                $this->em->persist($setEntity);
            }
        }
        return $setNewArray;
    }

    /**
     * @return array[string => Category]
     */
    protected function getAllCategory(): array
    {
        return $this->getAllFromRepository($this->categoryRepository);
    }

    /**
     * @return array[string => SubCategory]
     */
    protected function getAllSubCategory(): array
    {
        return $this->getAllFromRepository($this->subCategoryRepository);
    }

    /**
     * @return array[string => Type]
     */
    protected function getAllType(): array
    {
        return $this->getAllFromRepository($this->typeRepository);
    }

    /**
     * @return array[string => SubType]
     */
    protected function getAllSubType(): array
    {
        return $this->getAllFromRepository($this->subTypeRepository);
    }

    /**
     * @return array[string => Archetype]
     */
    protected function getAllArchetype(): array
    {
        return $this->getAllFromRepository($this->archetypeRepository);
    }

    /**
     * @return array[string => Rarity]
     */
    protected function getAllRarity(): array
    {
        return $this->getAllFromRepository($this->rarityRepository);
    }

    /**
     * @return array[string => CardAttribute]
     */
    protected function getAllAttribute(): array
    {
        return $this->getAllFromRepository($this->cardAttributeRepository);
    }

    /**
     * @return array[string => PropertyType]
     */
    protected function getAllPropertyType(): array
    {
        return $this->getAllFromRepository($this->propertyTypeRepository);
    }

    /**
     * @return Property[]
     */
    protected function getAllProperty(): array
    {
        return $this->propertyRepository->findAll();
    }

    /**
     * @return array[string => SubPropertyType]
     */
    protected function getAllSubPropertyType(): array
    {
        return $this->getAllFromRepository($this->subPropertyTypeRepository);
    }

    /**
     * @return array[string => SubProperty]
     */
    protected function getAllSubProperty(): array
    {
        return $this->getAllFromRepository($this->subPropertyRepository);
    }

    /**
     * @param array $arrayToModify
     * @param array $elements
     * @return array
     */
    protected function deleteMultipleArrayElement(array $arrayToModify, array $elements): array
    {
        return array_values(array_diff($arrayToModify, $elements));
    }

    /**
     * Add slugName as key in $array to compare string and keep name for futur uses.
     * @param array $array
     * @return array
     */
    protected function addSlugNameAsKeyInArray(array $array): array
    {
        $newArray = [];
        $arraySlugName = array_map([$this, 'slugify'], $array);
        $count = count($array);
        for ($i = 0; $i < $count; $i++) {
            $newArray[$arraySlugName[$i]] = $array[$i];
        }
        return $newArray;
    }

    /**
     * @param array $needles
     * @param array $haystack
     * @return bool
     */
    protected function inArrayAll(array $needles, array $haystack): bool
    {
        return array_diff($needles, $haystack) === [];
    }

    /**
     * @param string|int $find
     * @param array $array
     * @return object|null
     */
    protected function findFromArray(string|int $find, array $array): ?object
    {
        $keyArray = array_keys($array);
        if (in_array($find, $keyArray, TRUE) === TRUE) {
            return $array[$find];
        }
        return NULL;
    }

    /**
     * @param string $categoryToFind
     * @param Category[] $categoryArray
     * @return Category|null
     */
    protected function findCategoryFromCategoryArray(string $categoryToFind, array $categoryArray): ?Category
    {
        return $this->findFromArray($categoryToFind, $categoryArray);
    }

    /**
     * @param string $archetypeToFind
     * @param array $archetypeArray
     * @return Archetype|null
     */
    protected function findArchetypeFromArchetypeArray(string $archetypeToFind, array $archetypeArray): ?Archetype
    {
        return $this->findFromArray($archetypeToFind, $archetypeArray);
    }

    /**
     * @param string $setToFind
     * @param Set[] $setArray
     * @return Set|null
     */
    protected function findSetFromSetArray(string $setToFind, array $setArray): ?Set
    {
        return $this->findFromArray($setToFind, $setArray);
    }

    /**
     * @param string $rarityToFind
     * @param Rarity[] $rarityArray
     * @return Rarity|null
     */
    protected function findRarityFromRarityArray(string $rarityToFind, array $rarityArray): ?Rarity
    {
        return $this->findFromArray($rarityToFind, $rarityArray);
    }

    /**
     * @param string $typeToFind
     * @param Type[] $typeArray
     * @return Type|null
     */
    protected function findTypeFromTypeArray(string $typeToFind, array $typeArray): ?Type
    {
        return $this->findFromArray($typeToFind, $typeArray);
    }

    /**
     * @param string $subTypeToFind
     * @param SubType[] $subTypeArray
     * @return SubType|null
     */
    protected function findSubTypeFromSubTypeArray(string $subTypeToFind, array $subTypeArray): ?SubType
    {
        return $this->findFromArray($subTypeToFind, $subTypeArray);
    }

    /**
     * @param string $attributeToFind
     * @param CardAttribute[] $attributeArray
     * @return CardAttribute|null
     */
    protected function findAttributeFromAttributeArray(string $attributeToFind, array $attributeArray): ?CardAttribute
    {
        return $this->findFromArray($attributeToFind, $attributeArray);
    }

    /**
     * @param string $propertyToFind
     * @param Property[] $propertyArray
     * @param PropertyType $propertyTypeEntity
     * @return Property|null
     */
    protected function findProperty(
        string $propertyToFind,
        array $propertyArray,
        PropertyType $propertyTypeEntity
    ): ?Property
    {
        $propertyToReturn = NULL;
        $propertyToFindSlugName = $this->slugify($propertyToFind);
        foreach ($propertyArray as $propertyEntity) {
            $propertyPropertyTypeEntity = $propertyEntity->getPropertyType();
            if (
                $propertyPropertyTypeEntity !== NULL &&
                $propertyEntity->getSlugName() === $propertyToFindSlugName &&
                $propertyPropertyTypeEntity->getId() === $propertyTypeEntity->getId()
            ) {
                $propertyToReturn = $propertyEntity;
                break;
            }
        }
        return $propertyToReturn;
    }

    /**
     * @param string $subPropertyToFind
     * @param SubProperty[] $subPropertyArray
     * @param SubPropertyType $subPropertyTypeEntity
     * @return SubProperty|null
     */
    protected function findSubProperty(
        string $subPropertyToFind,
        array $subPropertyArray,
        SubPropertyType $subPropertyTypeEntity
    ): ?SubProperty
    {
        $subPropertyToReturn = NULL;
        $subPropertyToFindSlugName = $this->slugify($subPropertyToFind);
        foreach ($subPropertyArray as $subPropertyEntity) {
            $subPropertySubPropertyTypeEntity = $subPropertyEntity->getSubPropertyType();
            if (
                $subPropertySubPropertyTypeEntity !== NULL &&
                $subPropertyEntity->getSlugName() === $subPropertyToFindSlugName &&
                $subPropertySubPropertyTypeEntity->getId() === $subPropertyTypeEntity->getId()
            ) {
                $subPropertyToReturn = $subPropertyEntity;
                break;
            }
        }
        return $subPropertyToReturn;
    }

    /**
     * @param string $subCategoryToFind
     * @param SubCategory[] $subCategoryArray
     * @param Category $categoryEntity
     * @return SubCategory|null
     */
    protected function findSubCategory(
        string $subCategoryToFind,
        array $subCategoryArray,
        Category $categoryEntity
    ): ?SubCategory
    {
        $subCategoryToReturn = NULL;
        $subCategoryToFindSlugName = $this->slugify($subCategoryToFind);
        $subCategories = $categoryEntity->getSubCategories();
        foreach ($subCategories as $categorySubCategory) {
            if ($categorySubCategory->getSlugName() === $subCategoryToFindSlugName) {
                foreach ($subCategoryArray as $subCategoryEntity) {
                    if ($categorySubCategory->getId() === $subCategoryEntity->getId()) {
                        $subCategoryToReturn = $subCategoryEntity;
                        break 2;
                    }
                }
            }
        }
        return $subCategoryToReturn;
    }

    /**
     * @param string $name
     * @param object $entity
     * @return object
     */
    protected function createBasicEntity(string $name, object $entity): object
    {
        $current = new DateTime();
        return $entity->setName($name)
            ->setSlugName($this->slugify($name))
            ->setCreatedAt($current)
            ->setUpdatedAt($current);
    }

    /**
     * @param string $name
     * @return SubCategory
     */
    protected function createSubCategory(string $name): SubCategory
    {
        $subCategory = new SubCategory();
        return $this->createBasicEntity($name, $subCategory);
    }

    /**
     * @param string $name
     * @return Archetype
     */
    protected function createArchetype(string $name): Archetype
    {
        $archetype = new Archetype();
        return $this->createBasicEntity($name, $archetype);
    }

    /**
     * @param string $name
     * @return Rarity
     */
    protected function createRarity(string $name): Rarity
    {
        $rarity = new Rarity();
        return $this->createBasicEntity($name, $rarity);
    }

    /**
     * @param Set $set
     * @param Rarity $rarity
     * @param string|null $code
     * @return CardSet
     */
    protected function createCardSet(Set $set, Rarity $rarity, ?string $code): CardSet
    {
        $current = new DateTime();
        $cardSet = new CardSet();
        return $cardSet->setCode($code)
            ->addSet($set)
            ->addRarity($rarity)
            ->setCreatedAt($current)
            ->setUpdatedAt($current);
    }

    /**
     * @param string $name
     * @return Type
     */
    protected function createType(string $name): Type
    {
        $type = new Type();
        return $this->createBasicEntity($name, $type);
    }

    /**
     * @param string $name
     * @return SubType
     */
    protected function createSubType(string $name): SubType
    {
        $subType = new SubType();
        return $this->createBasicEntity($name, $subType);
    }

    /**
     * @param string $name
     * @return CardAttribute
     */
    protected function createAttribute(string $name): CardAttribute
    {
        $cardAttribute = new CardAttribute();
        return $this->createBasicEntity($name, $cardAttribute);
    }

    /**
     * @param string $fileName
     * @return string
     */
    protected function createEmptyFile(string $fileName): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
        $this->filesystem->dumpFile($path, '');
        return $path;
    }

    /**
     * @param string $prefix
     * @return string
     */
    protected function getUniqueNameFromPrefix(string $prefix): string
    {
        return uniqid($prefix, TRUE);
    }

    /**
     * @param string $url
     * @return SymfonyFile|null
     * @throws GuzzleException
     */
    protected function createFileFromUrl(string $url): ?SymfonyFile
    {
        $client = new Client([
            "allow_redirects" => true,
            "http_errors" => false
        ]);
        $filename = '';
        $filePath = $this->createEmptyFile($this->getUniqueNameFromPrefix(''), "");
        $response = $client->get($url, ["sink" => $filePath]);
        if ($response->getStatusCode() === 200) {
            return new SymfonyFile($filePath);
        }
        return NULL;
    }

    /**
     * @param object $entity
     * @return string
     */
    protected function getEntityUuidAsString(object $entity): string
    {
        return $entity->getUuid()->__toString();
    }

    /**
     * @param SymfonyFile $file
     * @return string
     */
    protected function getExtensionFromFile(SymfonyFile $file): string
    {
        $mimeType = $file->getMimeType();
        return ($mimeType === "image/png") ? "png" : "jpg";
    }

    /**
     * @param string $cardUuid
     * @param string $pictureIdYGO
     * @param SymfonyFile $file
     * @param string $filename
     * @return void
     */
    protected function moveCard(string $cardUuid, string $pictureIdYGO, SymfonyFile $file, string $filename): void
    {
        $folderPath = sprintf(
            "%s/%s/%s",
            $this->cardUploadPath,
            $cardUuid,
            $pictureIdYGO
        );
        if ($this->filesystem->exists($folderPath) === FALSE) {
            $this->filesystem->mkdir($folderPath);
        }
        $file->move($folderPath, $filename);
    }

    /**
     * @param Card $cardEntity
     * @param string $cardSetInfoName
     * @param string|null $cardCode
     * @param string $cardRarity
     * @return bool
     */
    protected function checkIfCardEntityHaveCardSet(
        Card $cardEntity,
        string $cardSetInfoName,
        ?string $cardCode,
        string $cardRarity
    ): bool
    {
        $cardSetInfoSlugName = $this->slugify($cardSetInfoName);
        $cardSets = $cardEntity->getCardSets();
        $cardCodeSlugName = NULL;
        if ($cardCode !== NULL) {
            $cardCodeSlugName = $this->slugify($cardCode);
        }
        $cardRaritySlugName = $this->slugify($cardRarity);
        $cardSetAlreadyExist = FALSE;
        foreach ($cardSets as $cardSet) {
            $sets = $cardSet->getSets();
            $cardSetCode = $cardSet->getCode();
            $cardSetCodeSlugName = NULL;
            if ($cardSetCode !== NULL) {
                $cardSetCodeSlugName = $this->slugify($cardSetCode);
            }
            $cardSetWithSameSet = FALSE;
            foreach ($sets as $set) {
                if ($set->getSlugName() === $cardSetInfoSlugName) {
                    $cardSetWithSameSet = TRUE;
                    break;
                }
            }
            if ($cardSetWithSameSet === TRUE && ($cardSetCodeSlugName === $cardCodeSlugName)) {
                $rarities = $cardSet->getRarities();
                $cardSetWithSameRarity = FALSE;
                foreach ($rarities as $rarity) {
                    $raritySlugName = $rarity->getSlugName();
                    if ($cardRaritySlugName === $raritySlugName) {
                        $cardSetWithSameRarity = TRUE;
                        break;
                    }
                }
                if ($cardSetWithSameRarity === TRUE) {
                    $cardSetAlreadyExist = TRUE;
                    break;
                }
            }
        }
        return $cardSetAlreadyExist;
    }

    /**
     * @param Card $cardEntity
     * @param int $pictureIdYGO
     * @return bool
     */
    protected function checkIfCardEntityHaveCardPicture(Card $cardEntity, int $pictureIdYGO): bool
    {
        $cardPictures = $cardEntity->getPictures();
        $cardPictureExist = FALSE;
        foreach ($cardPictures as $cardPicture) {
            if ($cardPicture->getIdYGO() === $pictureIdYGO) {
                $cardPictureExist = TRUE;
                break;
            }
        }
        return $cardPictureExist;
    }

    /**
     * @param Card $cardEntity
     * @param array $cardInfoArray
     * @return Card
     */
    protected function setAtkDefPoint(Card $cardEntity, array $cardInfoArray): Card
    {
        $atk = NULL;
        if (isset($cardInfoArray["atk"]) === TRUE) {
            $atk = (int)$cardInfoArray["atk"];
        }
        $def = NULL;
        if (isset($cardInfoArray["def"]) === TRUE) {
            $def = (int)$cardInfoArray["def"];
        }
        return $cardEntity->setAttackPoints((int)$atk)
            ->setDefensePoints((int)$def);
    }

    /**
     * @param Card $cardEntity
     * @param SubType[] $subTypeArray
     * @param SubType[] $subTypeNewArray
     * @return array
     */
    protected function removeCardEntityFromAllSubTypeArray(
        Card $cardEntity,
        array $subTypeArray,
        array $subTypeNewArray,
    ): array
    {
        $cardSubTypes = $cardEntity->getSubTypes();
        $allArray = ["array" => $subTypeArray, "newArray" => $subTypeNewArray];
        foreach ($cardSubTypes as $cardSubType) {
            $cardSubTypeSlugName = $cardSubType->getSlugName();
            $subTypeToRemove = NULL;
            foreach ($allArray as $array) {
                foreach ($array as $subType) {
                    if ($subType->getSlugName() === $cardSubTypeSlugName) {
                        $subTypeToRemove = $subType;
                        break 2;
                    }
                }
            }
            if ($subTypeToRemove !== NULL) {
                $subTypeToRemove->removeCard($cardEntity);
            }
        }
        return $allArray;
    }
}