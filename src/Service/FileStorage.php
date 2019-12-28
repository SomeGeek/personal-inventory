<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

use App\Document\InventoryItem;

class FileStorage
{
    /** @var string */
    protected $basePath;

    /**
     * Constructor
     *
     * @param string $basePath See services.yaml
     */
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function getItemFilePath(InventoryItem $item)
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $item->getId();
    }

    /**
     * Save files during upload.
     *
     * @param InventoryItem $item
     * @param UploadedFile[] $files
     */
    public function saveItemFiles(InventoryItem $item, array $files)
    {
        $itemPath = $this->getItemFilePath($item);
        if (!file_exists($itemPath)) {
            mkdir($itemPath);
        }
        $time = time();
        $count = 0;
        foreach ($files as $file) {
            if (!$file->isValid()) {
                throw new \RuntimeException($file->getErrorMessage());
            }
            $extension = $file->guessExtension();
            if (!$extension) {
                $extension = 'bin';
            }
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = \transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
            $newFilename = $safeFilename.'-'.$time.$count.'.' . $extension;
            //$originalFilename = $time . 'f' . $count . '.' . $extension;
            $file->move($itemPath, $newFilename);
            $count++;
        }
    }

    /**
     * Get file names associated with an item.
     *
     * @param InventoryItem $item
     * @return string[] Array of file names (excluding path)
     */
    public function getItemFiles(InventoryItem $item): array
    {
        $files = [];
        $path = $this->getItemFilePath($item);
        if (file_exists($path)) {
            $iter = new \DirectoryIterator($path);
            foreach ($iter as $file) {
                if (!$file->isDot()) {
                    $name = $file->getFilename();
                    $files[] = $name;
                }
            }
        }
        return $files;
    }

    /**
     * Get the full path to an item's file.
     *
     * @param InventoryItem $item
     * @param string $filename The file name
     * @return string
     */
    public function getFilePath(InventoryItem $item, string $filename)
    {
        $path = $this->getItemFilePath($item) . DIRECTORY_SEPARATOR . $filename;
        if (!file_exists($path)) {
            return '';
        }
        return $path;
    }

    /**
     * Remove an item's file from storage
     *
     * @param InventoryItem $item
     * @param string $filename
     */
    public function deleteItemFile(InventoryItem $item, string $filename)
    {
        $path = $this->getItemFilePath($item);
        $files = [$filename];
        foreach ($files as $filename) {
            if (file_exists($path . DIRECTORY_SEPARATOR . $filename)) {
                unlink($path . DIRECTORY_SEPARATOR . $filename);
            }
        }
    }
}
