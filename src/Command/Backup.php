<?php
namespace App\Command;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Google\Service\Drive\DriveFile;
use JsonException;
use phpseclib3\Exception\FileNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\SluggerInterface;
use Google\Exception as GoogleException;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDriveService;
use Google\Service\Drive\DriveFile as GoogleDriveFile;
use App\Service\Logger as LoggerService;
use Google\Service\Exception as GoogleServiceException;
use App\Exception\CronException;

#[AsCommand(name: "app:backup")]
class Backup extends Command
{
    private ParameterBagInterface $param;
    private LoggerService $loggerService;
    private GoogleClient $googleClient;
    private GoogleDriveService $googleDriveService;
    private string $backupFolderId;
    private string $googleDriveFolderMimeType = "application/vnd.google-apps.folder";
    private string $backupFolderName = "Backup";

    private string $filePrefix;
    /**
     * @var array[
     * "year" => string,
     * "month" => string,
     * "day" => string
     * ]
     */
    private array $currentDateFormat;
    /**
     * @var array[
     * "host" => string,
     * "port" => int,
     * "user" => string,
     * "password" => string,
     * "dbname" => string
     * ]
     */
    private array $dbalParam;

    private string $logDir;
    protected static $defaultName = 'app:backup';

    /**
     * @param ParameterBagInterface $param
     * @param EntityManagerInterface $entityManager
     * @param GoogleClient $googleClient
     * @param LoggerService $loggerService
     */
    public function __construct(
        ParameterBagInterface $param,
        EntityManagerInterface $entityManager,
        GoogleClient $googleClient,
        LoggerService $loggerService
    )
    {
        $currentDate = new DateTime();
        $currentYear = $currentDate->format("Y");
        $currentMonth = $currentDate->format("m");
        $currentDay = $currentDate->format("d");
        $this->currentDateFormat = [
            "year" => $currentYear,
            "month" => $currentMonth,
            "day" => $currentDay
        ];
        $this->filePrefix = sprintf("%s-%s-%s", $currentYear, $currentMonth, $currentDay);
        $this->dbalParam = $entityManager->getConnection()->getParams();
        $this->logDir = $param->get("LOG_DIR");
        $this->param = $param;
        $this->loggerService = $loggerService;
        $this->loggerService->setLevel(LoggerService::ERROR)->setIsCron(TRUE);
        $this->googleClient = $googleClient;
        $this->googleClient->setScopes([
            GoogleDriveService::DRIVE,
            GoogleDriveService::DRIVE_FILE,
            GoogleDriveService::DRIVE_METADATA_READONLY,
            GoogleDriveService::DRIVE_READONLY,
        ]);
        parent::__construct();
    }

    /**
     * @return void
     */
    public function configure(): void
    {
        $this
            ->setDescription("Create a dump of the current state of DB + zip all current log file of the day to send it to a drive");
    }

    /**
     * Authenticate from the GoogleClient to use the Google Drive Service
     * Try to find the 'Backup' folder in 'My Drive' before the actual Backup.
     * @return void
     * @throws GoogleServiceException|FileNotFoundException
     */
    private function _initializeGoogleDrive(): void
    {
        $this->googleDriveService = new GoogleDriveService($this->googleClient);
        $myDrive = $this->googleDriveService->files->get("root");
        $query = sprintf(
            "mimeType='%s' and name='%s'",
            $this->googleDriveFolderMimeType,
            $this->backupFolderName
        );
        $result = $this->googleDriveService->files->listFiles([
            'q' => $query
        ]);
        if ($result->count() === 0) {
            throw new FileNotFoundException(
                sprintf(
                    "The folder %s is not found at the root of My Drive",
                    $this->backupFolderName
                )
            );
        }
        $this->backupFolderId = $result->getFiles()[0]->getId();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            [
                "year" => $currentYear,
                "month" => $currentMonth,
            ] = $this->currentDateFormat;
            if ($this->param->get("APP_ENV") !== "prod") {
                return Command::SUCCESS;
            }
            $this->_initializeGoogleDrive();
            $currentYearFolder = $this->searchFolder($currentYear);
            if ($currentYearFolder === NULL) {
                $currentYearFolderId = $this->createFolder($currentYear);
            } else {
                $currentYearFolderId = $currentYearFolder->getId();
            }
            $currentMonthFolder = $this->searchFolder($currentMonth, $currentYearFolderId);
            if ($currentMonthFolder === NULL) {
                $currentMonthFolderId = $this->createFolder($currentMonth, $currentYearFolderId);
            } else {
                $currentMonthFolderId = $currentMonthFolder->getId();
            }
            $databaseFilePath = $this->createDumpDatabase();
            $gzipMimeType = "application/gzip";
            $this->uploadFile($databaseFilePath, $gzipMimeType, $currentMonthFolderId);
            $zipPath = $this->zipLogFile();
            if ($zipPath !== null) {
                $this->uploadFile($zipPath, $gzipMimeType, $currentMonthFolderId);
            }
            $this->deleteOldLogFiles();
        } catch (GoogleException|GoogleServiceException|JsonException|FileNotFoundException|Exception $e) {
            $this->loggerService->setException($e)
                ->addErrorExceptionOrTrace();
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

    /**
     * Search folder from Backup folder
     * @param string $folderName
     * @param string|null $folderIdParent
     * @return DriveFile|null
     * @throws GoogleServiceException
     */
    protected function searchFolder(string $folderName, ?string $folderIdParent = NULL): ?GoogleDriveFile
    {
        $folderIdParent = $folderIdParent ?? $this->backupFolderId;
        $query = sprintf(
            'name = "%s" and mimeType = "%s" and parents in "%s"',
            $folderName,
            $this->googleDriveFolderMimeType,
            $folderIdParent
        );
        $fileResult = $this->googleDriveService->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name)'
        ]);
        if ($fileResult->count() === 0) {
            return NULL;
        }
        return $fileResult->getFiles()[0];
    }

    /**
     * Create folder from a specific idParent
     * If no IdParent is set, we put the Backup folder instead
     * @param string $folderName
     * @param string|null $folderIdParent
     * @return string
     * @throws GoogleServiceException
     */
    protected function createFolder(string $folderName, ?string $folderIdParent = NULL): string
    {
        $folder = new GoogleDriveFile();
        $folder->setName($folderName);
        $folder->setMimeType($this->googleDriveFolderMimeType);
        $folderIdParent = $folderIdParent ?? $this->backupFolderId;
        $folder->setParents([$folderIdParent]);
        return $this->googleDriveService->files->create($folder)->getId();
    }

    /**
     * Create a dump of the current database
     * @return string
     */
    protected function createDumpDatabase(): string
    {
        [
            "host" => $dbHost,
            "user" => $dbUser,
            "password" => $dbPassword,
            "dbname" => $dbName,
            "port" => $dbPort
        ] = $this->dbalParam;
        $filename = $this->filePrefix . ".sql.gz";
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        $cmd = sprintf(
            "mysqldump --host=%s --port=%d --user=%s --password=%s %s | gzip -c > %s",
            $dbHost, $dbPort, $dbUser, $dbPassword, $dbName, $filePath
        );
        exec($cmd);
        return $filePath;
    }

    /**
     * @param string $filePath
     * @param string $mimeType
     * @param string $folderIdParent
     * @return DriveFile
     * @throws GoogleServiceException
     */
    protected function uploadFile(
        string $filePath,
        string $mimeType,
        string $folderIdParent
    ): GoogleDriveFile
    {
        $file = new GoogleDriveFile();
        $file->setName(basename($filePath));
        $file->setMimeType($mimeType);
        $file->setParents([$folderIdParent]);
        return $this->googleDriveService->files->create(
            $file,
            [
                'data' => file_get_contents($filePath),
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]
        );
    }

    /**
     * @param string $fileRegex
     * @return bool
     */
    protected function checkIfLogFileExist(string $fileRegex): bool
    {
        $file = glob($this->logDir . DIRECTORY_SEPARATOR . $fileRegex);
        return count($file) > 0;
    }

    /**
     * Delete old log file who's not current date and not the .gitkeep file
     * @return void
     */
    protected function deleteOldLogFiles(): void
    {
        $fileRegex = $this->filePrefix . "*.txt";
        $cmd = sprintf("find %s -type f -not -name '%s' -not -name '.gitkeep' -delete", $this->logDir, $fileRegex);
        exec($cmd);
    }

    /**
     * Zip all log file of the current day or return null if there is no log file
     * @return string|null
     */
    protected function zipLogFile(): ?string
    {
        $fileRegex = $this->filePrefix . "*.txt";
        $filesExist = $this->checkIfLogfileExist($fileRegex);
        if ($filesExist === FALSE) {
            return NULL;
        }
        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->filePrefix . ".log.zip";
        $cmd = sprintf("cd %s && zip %s %s", $this->logDir, $zipPath, $fileRegex);
        exec($cmd);
        return $zipPath;
    }
}