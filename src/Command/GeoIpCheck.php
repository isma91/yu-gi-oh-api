<?php

namespace App\Command;

use App\Entity\MaxmindVersion as MaxmindVersionEntity;
use App\Repository\MaxmindVersionRepository;
use App\Service\Logger as LoggerService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PharData;
use PharException;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:geo-ip',
    description: 'Check if we have the latest version of the MaxMind databases and download if it\'s not.',
)]
class GeoIpCheck extends Command
{
    private EntityManagerInterface $em;
    private ParameterBagInterface $param;
    private string $checksumHashType = "sha256";
    /**
     * the "checksum" key url is not complete, we intentionally
     * remove the hashing type to be added after with $checksumHashType in case Maxmind change it
     * @var array|array[]
     */
    private array $maxmindInfoArray = [
        "asn" => [
            "url" => "https://download.maxmind.com/geoip/databases/GeoLite2-ASN/download?suffix=tar.gz",
            "checksum" => "https://download.maxmind.com/geoip/databases/GeoLite2-ASN/download?suffix=tar.gz.",
            "file" => "GeoLite2-ASN"
        ],
        "city" => [
            "url" => "https://download.maxmind.com/geoip/databases/GeoLite2-City/download?suffix=tar.gz",
            "checksum" => "https://download.maxmind.com/geoip/databases/GeoLite2-City/download?suffix=tar.gz.",
            "file" => "GeoLite2-City"
        ],
        "country" => [
            "url" => "https://download.maxmind.com/geoip/databases/GeoLite2-Country/download?suffix=tar.gz",
            "checksum" => "https://download.maxmind.com/geoip/databases/GeoLite2-Country/download?suffix=tar.gz.",
            "file" => "GeoLite2-Country"
        ],
    ];
    private GuzzleClient $guzzleClient;
    private LoggerService $loggerService;
    private Filesystem $filesystem;
    private MaxmindVersionEntity $maxmindVersionEntity;
    protected static $defaultName = 'app:geo-ip';

    public function __construct(
        EntityManagerInterface $em,
        ParameterBagInterface $param,
        Filesystem $filesystem,
        MaxmindVersionRepository $maxmindVersionRepository,
        LoggerService $loggerService
    )
    {
        $this->em = $em;
        $this->param = $param;
        $this->guzzleClient = new GuzzleClient([
            "allow_redirect" => TRUE,
            "http_errors" => TRUE,
        ]);
        $this->filesystem = $filesystem;
        $result = $maxmindVersionRepository->findBy([], ["id" => "DESC"], 1, 0);
        if (empty($result) === TRUE) {
            $this->maxmindVersionEntity = new MaxmindVersionEntity();
            $this->maxmindVersionEntity->setCreatedAt(new DateTime())->setUpdatedAt(new DateTime());
        } else {
            $this->maxmindVersionEntity = $result[0];
        }
        $this->loggerService = $loggerService;
        $this->loggerService->setLevel(LoggerService::ERROR)->setIsCron(TRUE);
        parent::__construct();
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getMaxmindDatabaseFilename(string $type): string
    {
        return $this->param->get(sprintf("MAXMIND_%s_FILENAME", strtoupper($type)));
    }

    /**
     * Safely set new variable to the MaxmindVersion entity
     * @param string $field
     * @param string $dateString
     * @return void
     */
    protected function updateMaxmindVersionEntity(string $field, string $dateString): void
    {
        $dateTime = NULL;
        $methodName = "set" . ucfirst($field);
        try {
            $dateTime = new DateTime($dateString);
        } catch (Exception $e) {
            $this->loggerService->addLog(
                sprintf("DateTime not valid with dateString => %s for maxmind field => %s", $dateString, $field)
            );
        }
        if ($dateTime !== NULL && method_exists($this->maxmindVersionEntity, $methodName) === TRUE) {
            $this->maxmindVersionEntity->$methodName($dateTime);
        }
    }

    /**
     * @param string $url
     * @param string|null $pathToFile
     * @param string $method
     * @return HttpResponseInterface
     */
    protected function sendMaxmindRequest(string $url, ?string $pathToFile, string $method = "get"): HttpResponseInterface
    {
        $method = strtolower($method);
        $requestOption = [
            "auth" => [$this->param->get("MAXMIND_ACCOUNT_ID"), $this->param->get("MAXMIND_LICENSE_KEY")]
        ];
        if ($pathToFile !== NULL) {
            $requestOption["sink"] = $pathToFile;
        }
        return $this->guzzleClient->$method($url, $requestOption);
    }

    /**
     * Create an empty file in /tmp to be fulfilled in near futur
     * @param string $extension
     * @return string
     */
    protected function createEmptyFile(string $extension): string
    {
        $filename = uniqid("maxmind", TRUE) . "." . $extension;
        $filePath = sys_get_temp_dir() . "/" . $filename;
        $this->filesystem->dumpFile($filePath, '');
        return $filePath;
    }

    /**
     * Download the $type Maxmind database and set the new date as version
     * @param string $type
     * @return string|null
     * @throws GuzzleException
     */
    protected function downloadDatabase(string $type): ?string
    {
        ["url" => $url] = $this->maxmindInfoArray[$type];
        $filePath = $this->createEmptyFile("tar.gz");
        $request = $this->sendMaxmindRequest($url, $filePath);
        $httpCode = $request->getStatusCode();
        if ($httpCode === 200) {
            $newDateTimeArray = $request->getHeader("Last-Modified");
            if (empty($newDateTimeArray) === FALSE) {
                $this->updateMaxmindVersionEntity($type, $newDateTimeArray[0]);
            }
            return $filePath;
        }
        return NULL;
    }

    /**
     * Download the checksum of the precedent Maxmind database file
     * @param string $type
     * @return string|null
     * @throws GuzzleException
     */
    protected function downloadChecksum(string $type): ?string
    {
        ["checksum" => $url] = $this->maxmindInfoArray[$type];
        $url .= $this->checksumHashType;
        $filePath = $this->createEmptyFile("txt");
        $request = $this->sendMaxmindRequest($url, $filePath);
        $httpCode = $request->getStatusCode();
        if ($httpCode === 200) {
            return $filePath;
        }
        return NULL;
    }

    /**
     * Hash the content of Maxmind database file and compare it with the supposed good checksum
     * @param string $type
     * @param string $databasePath
     * @param string $checksumPath
     * @return bool
     */
    protected function compareChecksum(string $type, string $databasePath, string $checksumPath): bool
    {
        if ($this->filesystem->exists($databasePath) === FALSE) {
            $this->loggerService->addLog(
                sprintf("Maxmind database for '%s' in '%s' not exist", $type, $databasePath)
            );
            return FALSE;
        }
        if ($this->filesystem->exists($checksumPath) === FALSE) {
            $this->loggerService->addLog(
                sprintf(
                    "Checksum file in '%s' for Maxmind database for '%s' in '%s' not exist",
                    $checksumPath,
                    $type,
                    $databasePath
                )
            );
            return FALSE;
        }
        $hashedDatabaseFile = hash_file($this->checksumHashType, $databasePath);
        $checksumTxtFileContent = file_get_contents($checksumPath);
        return str_contains($checksumTxtFileContent, $hashedDatabaseFile);
    }

    /**
     * Extract waited file and put in MAXMIND_DIR location
     * @param string $filePath
     * @param string $type
     * @throws PharException
     */
    protected function extractFile(string $filePath, string $type): void
    {
        [
            "file" => $filenameBegin
        ] = $this->maxmindInfoArray[$type];
        $maxmindDir = $this->param->get("MAXMIND_DIR");
        $pharData = new PharData($filePath);
        $folderToOpen = NULL;
        $maxmindDBFilename = $this->getMaxmindDatabaseFilename($type);
        foreach ($pharData as $file) {
            if ($file->isDir() === TRUE && str_starts_with($file->getFilename(), $filenameBegin) === TRUE) {
                $folderToOpen = $file;
                break;
            }
        }
        if ($folderToOpen !== NULL) {
            $folderPathname = $folderToOpen->getPathname();
            $files = scandir($folderPathname);
            foreach ($files as $file) {
                if (str_starts_with($file, $filenameBegin) === TRUE) {
                    //remove also the phar wrapper stream
                    $fileInternalPath = str_replace([$filePath, "phar://"], '', $folderPathname) . "/" . $file;
                    if (str_starts_with($fileInternalPath, "/") === TRUE) {
                        $fileInternalPath = substr($fileInternalPath, 1);
                    }
                    //at this point we have the file but in a folder, we are going to put it in the root of MAXMIND_DIR
                    $pharData->extractTo($maxmindDir, $fileInternalPath, TRUE);
                    $maxmindDBFileOldPath = $maxmindDir . "/" . $fileInternalPath;
                    if ($this->filesystem->exists($maxmindDBFileOldPath) === TRUE) {
                        $this->filesystem->rename($maxmindDBFileOldPath, $maxmindDir . "/" . $maxmindDBFilename, TRUE);
                        $this->filesystem->remove($maxmindDir . "/" . $folderToOpen->getFilename());
                    }
                    break;
                }
            }
        }
    }

    /**
     * Send HEAD request to get the latest date version of the $type Maxmind database
     * @param string $type
     * @return string|null
     */
    protected function getLatestVersion(string $type): ?DateTime
    {
        ["url" => $url] = $this->maxmindInfoArray[$type];
        $request = $this->sendMaxmindRequest($url, NULL, "head");
        $httpCode = $request->getStatusCode();
        if ($httpCode === 200) {
            $newDateTimeArray = $request->getHeader("Last-Modified");
            if (empty($newDateTimeArray) === FALSE) {
                $dateTime = NULL;
                try {
                    $dateTime = new DateTime($newDateTimeArray[0]);
                } catch (Exception $e) {
                    $this->loggerService->addLog(
                        sprintf(
                            "DateTime not valid with dateString => %s for maxmind type => %s",
                            $newDateTimeArray[0],
                            $type
                        )
                    );
                }
                return $dateTime;
            }
        }
        return NULL;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $needPersist = FALSE;
            foreach ($this->maxmindInfoArray as $type => $infoArray) {
                try {
                    $filePath = $this->param->get("MAXMIND_DIR") . "/" . $this->getMaxmindDatabaseFilename($type);
                    $needUpdate = FALSE;
                    $methodName = "get" . ucfirst($type);
                    $currentDbVersion = $this->maxmindVersionEntity->$methodName();
                    if ($currentDbVersion === NULL || $this->filesystem->exists($filePath) === FALSE) {
                        $needUpdate = TRUE;
                        $needPersist = TRUE;
                    } else {
                        $latestVersion = $this->getLatestVersion($type);
                        if ($latestVersion === NULL) {
                            $this->loggerService->addLog(
                                sprintf("Can't get the latest version of type '%s'", $type)
                            );
                        } else {
                            $needUpdate = ($currentDbVersion < $latestVersion);
                        }
                    }
                    if ($needUpdate === TRUE) {
                        $databaseFilePath = $this->downloadDatabase($type);
                        if ($databaseFilePath === NULL) {
                            $this->loggerService->addLog(sprintf("Fail to download database type '%s'", $type));
                            continue;
                        }
                        $checksumFilePath = $this->downloadChecksum($type);
                        if ($checksumFilePath === NULL) {
                            $this->loggerService->addLog(sprintf("Fail to download checksum type '%s'", $type));
                            continue;
                        }
                        $goodChecksum = $this->compareChecksum($type, $databaseFilePath, $checksumFilePath);
                        if ($goodChecksum === FALSE) {
                            $this->loggerService->addLog(
                                sprintf(
                                    "Checksum fail for type '%s' database in '%s' and checksum in '%s'",
                                    $type,
                                    $databaseFilePath,
                                    $checksumFilePath
                                )
                            );
                            continue;
                        }
                        $this->extractFile($databaseFilePath, $type);
                        $this->filesystem->remove([$databaseFilePath, $checksumFilePath]);
                    }
                } catch (GuzzleException|Exception $e) {
                    $this->loggerService->setException($e)
                        ->addErrorExceptionOrTrace();
                    continue;
                }
            }
            if ($needPersist === TRUE) {
                $this->em->persist($this->maxmindVersionEntity);
                $this->em->flush();
            }
        } catch (Exception $e) {
            $this->loggerService->setException($e)
                ->addErrorExceptionOrTrace();
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
