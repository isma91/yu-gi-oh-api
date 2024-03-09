<?php

namespace App\Service\Tool\CardPicture;

use App\Service\Tool\FileInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class File implements FileInterface
{
    private Filesystem $filesystem;
    private ParameterBagInterface $param;
    private const DEFAULT_FILENAME = "default.png";
    private array $prefixArray = [
        "picture" => "CARD_UPLOAD_DIR",
        "pictureSmall" => "CARD_UPLOAD_DIR",
        "artwork" => "CARD_UPLOAD_DIR",
    ];

    public function __construct(Filesystem $filesystem, ParameterBagInterface $param)
    {
        $this->filesystem = $filesystem;
        $this->param = $param;
    }

    /**
     * Absolute path of the default file in card folder
     * @return string
     */
    public function getDefaultFilePath(): string
    {
        return $this->param->get("CARD_UPLOAD_DIR") . DIRECTORY_SEPARATOR . self::DEFAULT_FILENAME;
    }

    public function getFilePath(string $prefix, array $option): string
    {
        [
            "uuid" => $uuid,
            "idYGO" => $idYGO,
            "name" => $name
        ] = $option;
        $file = $this->getDefaultFilePath();
        $fileFullPath = sprintf(
            '%s/%s/%s/%s',
                $this->param->get($this->prefixArray[$prefix]),
            $uuid,
            $idYGO,
            $name
        );
        return ($this->filesystem->exists($fileFullPath) === FALSE) ? $file : $fileFullPath;
    }
}