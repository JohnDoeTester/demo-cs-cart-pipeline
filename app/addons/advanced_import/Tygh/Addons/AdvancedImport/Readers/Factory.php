<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Addons\AdvancedImport\Readers;

use Tygh\Addons\AdvancedImport\Exceptions\DownloadException;
use Tygh\Addons\AdvancedImport\Exceptions\FileNotFoundException;
use Tygh\Addons\AdvancedImport\Exceptions\ReaderNotFoundException;
use Tygh\Enum\Addons\AdvancedImport\PresetFileTypes;
use Tygh\Exceptions\PermissionsException;
use Tygh\Addons\AdvancedImport\FileManager;
use Tygh\Tygh;

class Factory
{
    /** @var int|null $company_id */
    protected $company_id;

    /** @var FileManager $file_manager */
    protected $file_manager;

    /** @var array<string, string> $allowed_extensions */
    protected $allowed_extensions;

    /**
     * Factory constructor.
     *
     * @param int|null      $company_id     Current user company ID
     * @param FileManager   $file_manager   File manager instance
     */
    public function __construct($company_id, FileManager $file_manager)
    {
        $this->company_id = (int) $company_id;
        $this->file_manager = $file_manager;
        $this->allowed_extensions = Tygh::$app['addons.advanced_import.allowed_extensions'];
    }

    /**
     * Gets file reader.
     *
     * @param array $preset Preset to read file for
     *
     * @return \Tygh\Addons\AdvancedImport\Readers\IReader Reader instance
     * @throws \Tygh\Exceptions\PermissionsException
     * @throws \Tygh\Addons\AdvancedImport\Exceptions\FileNotFoundException
     * @throws \Tygh\Addons\AdvancedImport\Exceptions\ReaderNotFoundException
     * @throws \Tygh\Addons\AdvancedImport\Exceptions\DownloadException
     */
    public function get(array $preset)
    {
        $file_to_load = $preset['file'];

        if (
            preg_match('!^(?P<company_id_in_path>\d+)/(?P<file_to_load>.+)!', $file_to_load, $matches)
            && ($this->company_id && (int) $matches['company_id_in_path'] !== $this->company_id)
        ) {
            throw new PermissionsException();
        }

        if ($preset['file_type'] === PresetFileTypes::URL) {
            $file = $this->file_manager->download($preset['file'], $this->company_id);
            if (!$file) {
                throw new DownloadException();
            }
            $file_path = $file['path'];
            $file_to_load = $file['name'];
        } else {
            $file_to_load = preg_replace("!^{$this->company_id}/!", '', $file_to_load);
            $file_path = $this->file_manager->getFilePath($file_to_load, $this->company_id);
        }

        if (!$file_path) {
            throw new FileNotFoundException();
        }

        $ext = $this->validateExtension(fn_get_file_ext($file_to_load));
        if (empty($ext) || !$this->readerExists($ext)) {
            throw new ReaderNotFoundException();
        }

        $reader_class = $this->getReaderClass($ext);

        $options = isset($preset['options'])
            ? $preset['options']
            : [];

        /** @var \Tygh\Addons\AdvancedImport\Readers\IReader $reader */
        $reader = new $reader_class($file_path, $options);

        return $reader;
    }

    /**
     * Downloads file.
     *
     * @param string   $url        Url
     * @param int|null $company_id Company to download file for
     *
     * @return array|null
     *
     * @deprecated since 4.11.4. Use the Tygh::$app['addons.advanced_import.file_manager'] service to download.
     * @see \Tygh\Addons\AdvancedImport\FileManager
     */
    public function download($url, $company_id = null)
    {
        return $this->file_manager->download($url, $company_id);
    }

    /**
     * Gets filepath to a file on server.
     *
     * @param string     $filename   Filename
     * @param int|null   $company_id Company to search file for
     * @param array|null $file_dirs  Directories to search in
     *
     * @return null|string
     *
     * @deprecated since 4.11.4. Use the Tygh::$app['addons.advanced_import.file_manager'] service to get file path.
     * @see \Tygh\Addons\AdvancedImport\FileManager
     */
    public function getFilePath($filename, $company_id = null, array $file_dirs = null)
    {
        return $this->file_manager->getFilePath($filename, $company_id, $file_dirs);
    }

    /**
     * Initializes files directories.
     *
     * @param int|null $company_id Company to initialize files directories for
     *
     * @return array
     *
     * @deprecated since 4.11.4. Use the Tygh::$app['addons.advanced_import.file_manager'] service to initialize files directories.
     * @see \Tygh\Addons\AdvancedImport\FileManager
     */
    public function initFilesDirectories($company_id = null)
    {
        return $this->file_manager->initFilesDirectories($company_id);
    }

    /**
     * Moves file to a private files directory of a company. Filename will be changed if a file
     * with the same name already located in the private directory.
     *
     * @param string        $filename        Filename in the target directory.
     * @param string        $source_path     Current file location.
     * @param int|null      $company_id      Owning company of the file.
     *
     * @return string       new filename
     *
     * @deprecated since 4.11.4. Use the Tygh::$app['addons.advanced_import.file_manager'] service to initialize files directories.
     * @see \Tygh\Addons\AdvancedImport\FileManager
     */
    public function moveUpload($filename, $source_path, $company_id = null)
    {
        return $this->file_manager->moveUpload($filename, $source_path, $company_id);
    }

    /**
     * Handles preset file upload process.
     *
     * For files uploaded by URL, performs validation by mime type.
     * For local and server uploades uses core upload behaviour.
     *
     * @param array    $preset     Preset data
     * @param int|null $company_id Company to download file for
     *
     * @return array Upload info with preset ID as an array key and fileinfo as value
     *
     * @deprecated since 4.11.4. Use the Tygh::$app['addons.advanced_import.file_manager'] service to initialize files directories.
     * @see \Tygh\Addons\AdvancedImport\FileManager
     */
    public function uploadPresetFile(array $preset, $company_id = null)
    {
        return $this->file_manager->uploadPresetFile($preset, $company_id);
    }

    /**
     * Removes file from a company private directory.
     *
     * @param string   $filename    File that must be removed
     * @param int|null $company_id  Owning company of the file
     *
     * @deprecated since 4.11.4. Use the Tygh::$app['addons.advanced_import.file_manager'] service to initialize files directories.
     * @see \Tygh\Addons\AdvancedImport\FileManager
     */
    public function removeFile($filename, $company_id = null)
    {
        $this->file_manager->removeFile($filename, $company_id);
    }

    /**
     * Checks if the reader for a specific file format exists.
     *
     * @param string $extension File extension
     *
     * @return bool
     */
    public function readerExists($extension)
    {
        $reader_class = $this->getReaderClass($extension);

        return class_exists($reader_class);
    }

    /**
     * Gets classname of the reader for a specific file format.
     *
     * @param string $extension File extension
     *
     * @return string
     */
    public function getReaderClass($extension)
    {
        return '\Tygh\Addons\AdvancedImport\Readers\\' . fn_camelize(strtolower($extension));
    }

    /**
     * Checks if the file's MIME type is in the list of the supported ones.
     *
     * @param string $extension File extension
     *
     * @return string
     */
    private function validateExtension($extension)
    {
        $extension = strtolower($extension);
        $extension = array_filter(
            $this->allowed_extensions,
            static function ($acceptable_extensions) use ($extension) {
                if (in_array($extension, $acceptable_extensions)) {
                    return true;
                }

                return false;
            },
            ARRAY_FILTER_USE_BOTH
        );

        return is_array($extension) ? key($extension) : '';
    }
}
