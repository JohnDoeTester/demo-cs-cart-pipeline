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

namespace Tygh\Addons\AdvancedImport;

use Tygh\Enum\Addons\AdvancedImport\PresetFileTypes;
use Tygh\Registry;

class FileManager
{
    /** @var array $file_dirs */
    protected $file_dirs;

    /** @var int $company_id */
    protected $company_id;

    /** @var array $allowed_ext */
    protected $allowed_ext;

    /** @var string  */
    const UPLOADED_FILE_NAME = 'upload';

    const TEMP_ARCHIVE_FOLDER = '__temp_images_';

    /**
     * File manager constructor.
     *
     * @param int   $company_id     Current user company ID
     * @param array $allowed_ext    List of allowed file extensions
     */
    public function __construct($company_id, array $allowed_ext)
    {
        $this->company_id = (int) $company_id;
        $this->allowed_ext = $allowed_ext;

        $this->file_dirs = $this->initFilesDirectories($company_id);
    }

    /**
     * Downloads file.
     *
     * @param string    $url        Url
     * @param int|null  $company_id Company to download file for
     * @param bool|null $is_archive Downloading archives
     *
     * @return array|null
     */
    public function download($url, $company_id = null, $is_archive = false)
    {
        $url = urldecode($url);

        $fileinfo = fn_get_url_data($url);
        if (!$fileinfo) {
            return null;
        }

        $ext = fn_get_file_ext($fileinfo['name']);

        if (!$this->checkAllowedExtension($ext, $is_archive)) {
            $mime_type = $this->getRemoteFileMimeType($url);
            $ext = $this->getFileExtensionByMimeType($mime_type);
        }

        if (!$ext || !$this->checkAllowedExtension($ext, $is_archive)) {
            return null;
        }

        if ($fileinfo['name'] == '') {
            $fileinfo['name'] = md5($url);
        }

        if (substr($fileinfo['name'], -(fn_strlen($ext) + 1)) !== '.' . $ext) {
            $fileinfo['name'] .= '.' . $ext;
        }

        if (!fn_check_uploaded_data($fileinfo, array())) {
            return null;
        }

        return $fileinfo;
    }

    /**
     * Gets filepath to a file on server.
     *
     * @param string     $filename   Filename
     * @param int|null   $company_id Company to search file for
     * @param array|null $file_dirs  Directories to search in
     *
     * @return null|string
     */
    public function getFilePath($filename, $company_id = null, array $file_dirs = null)
    {
        if (strpos($filename, 'C:\fakepath') === 0) {
            $filename = str_replace('C:\fakepath\\', '', $filename);
        }
        if ($file_dirs === null) {
            if ($company_id == $this->company_id) {
                $file_dirs = $this->file_dirs;
            } else {
                $file_dirs = $this->initFilesDirectories($company_id);
            }
        }

        foreach ($file_dirs as $dir) {
            if (file_exists($dir . $filename)) {
                return $dir . $filename;
            }
        }

        return null;
    }

    /**
     * Gets private and public files path for company ID.
     *
     * @param  int|null $company_id Company to get paths for
     *
     * @return array    Private and public paths
     */
    public function initFilesDirectories($company_id = null)
    {
        return [
            'private' => $this->getPrivateFilesPath($company_id),
            'public'  => $this->getPublicFilesPath($company_id),
        ];
    }

    /**
     * Moves file to a private files directory of a company. Filename will be changed if a file
     * with the same name already located in the private directory.ку
     *
     * @param string        $filename        Filename in the target directory.
     * @param string        $source_path     Current file location.
     * @param int|null      $company_id      Owning company of the file.
     *
     * @return string       new filename
     */
    public function moveUpload($filename, $source_path, $company_id = null)
    {
        $info = fn_pathinfo($this->getPrivateFilePath($filename, $company_id));

        while (file_exists($info['dirname'] . '/' . $info['filename'] . '.' . $info['extension'])) {
            $info['filename'] = fn_strtolower(fn_generate_code($info['filename'], 8));
        }

        $new_filename = $info['filename'] . '.' . $info['extension'];
        $uploaded_file_location = $this->getPrivateFilePath($new_filename, $company_id);

        fn_rename($source_path, $uploaded_file_location);

        return $new_filename;
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
     */
    public function uploadPresetFile(array $preset, $company_id = null)
    {
        $preset = array_merge(array(
            'file'      => '',
            'file_type' => PresetFileTypes::LOCAL,
            'preset_id' => 0,
        ), $preset);

        $file = array();
        if ($preset['file_type'] === PresetFileTypes::URL) {
            $downloaded_file = $this->download($preset['file'], $company_id);
            if ($downloaded_file) {
                $file = array($preset['preset_id'] => $downloaded_file);
            }
        } else {
            $downloaded_file = fn_filter_uploaded_data(self::UPLOADED_FILE_NAME);
            if ($downloaded_file) {
                $file = array($preset['preset_id'] => reset($downloaded_file));
            }
        }

        return $file;
    }

    /**
     * Removes file from a company private directory.
     *
     * @param string   $filename    File that must be removed
     * @param int|null $company_id  Owning company of the file
     */
    public function removeFile($filename, $company_id = null)
    {
        $path = $this->getPrivateFilePath($filename, $company_id);
        fn_rm($path);
    }

    /**
     * Corrects path to an imported file for a company.
     *
     * @param array $data Preset data
     *
     * @return array Preset data with file path corrected
     */
    public function correctFilePath(array $data)
    {
        if (
            !isset($data['file'])
            || !isset($data['company_id'])
            || !fn_string_not_empty($data['file'])
            || $this->company_id
        ) {
            return $data;
        }

        $data['file'] = preg_replace("!^{$this->company_id}/!", '', $data['file']);

        return $data;
    }

    /**
     * Gets path to private files directory.
     * Creates missing private files directory.
     *
     * @param int|null $company_id Company to get path for
     *
     * @return string Private files directory path
     */
    protected function getPrivateFilesPath($company_id = null)
    {
        $path = fn_get_files_dir_path($company_id);

        fn_mkdir($path);

        return $path;
    }

    /**
     * Gets path to public files directory.
     * Creates missing public files directory.
     *
     * @param int|null $company_id Company to get path for
     *
     * @return string Public files directory path
     */
    protected function getPublicFilesPath($company_id = null)
    {
        $path = fn_get_public_files_path($company_id);

        fn_mkdir($path);

        return $path;
    }

    /**
     * Provides corrected company ID for assorted checks.
     *
     * @param int|null $company_id Company ID to check
     *
     * @return int|null
     */
    protected function getCompanyId($company_id = null)
    {
        return $this->company_id ?: $company_id;
    }

    /**
     * @param string $url
     *
     * @return string|null
     */
    protected function getRemoteFileMimeType($url)
    {
        $url_scheme = parse_url($url, PHP_URL_SCHEME);
        if (!$url_scheme) {
            $url = sprintf('http://%s', $url);
        }

        $headers = get_headers($url, 1);
        $headers = array_change_key_case($headers);

        if (empty($headers['content-type'])) {
            return null;
        }

        if (is_array($headers['content-type'])) {
            $content_type = end($headers['content-type']);
        } else {
            $content_type = $headers['content-type'];
        }

        list($content_type) = explode(';', $content_type);

        return trim($content_type);
    }

    /**
     * @param string $mime_type
     *
     * @return string|null
     */
    protected function getFileExtensionByMimeType($mime_type)
    {
        $mime_to_ext = array_merge(fn_get_ext_mime_types('mime'), [
            'text/xml' => 'xml'
        ]);

        return isset($mime_to_ext[$mime_type]) ? $mime_to_ext[$mime_type] : null;
    }

    /**
     * Returns allowed pack exceptions.
     *
     * @return array<array-key, array<array-key, string>> Allowed exceptions
     */
    protected function getAllowedPackExceptions()
    {
        $allowed_ext = [];
        foreach (Registry::get('config.allowed_pack_exts') as $ext) {
            $allowed_ext[$ext][] = $ext;
        }
        return $allowed_ext;
    }

    /**
     * Returns a filepath for file that locate in company private directory.
     *
     * @param string   $filename    Filename that located in private directory
     * @param int|null $company_id  Owning company of the private directory
     *
     * @return string filepath
     */
    protected function getPrivateFilePath($filename, $company_id = null)
    {
        if ($company_id === null) {
            $company_id = $this->getCompanyId($company_id);
        }

        return $this->getPrivateFilesPath($company_id) . $filename;
    }

    /**
     * @param $filename
     * @param $source_path
     * @param $company_id
     *
     * @return bool
     */
    public function copyUpload($filename, $company_id, $source_path = null)
    {
        $info = fn_pathinfo($this->getPrivateFilePath($filename, 0));
        $source_path = isset($source_path) ? $source_path : fn_get_files_dir_path($company_id) . $filename;

        while (file_exists($info['dirname'] . '/' . $info['filename'] . '.' . $info['extension'])) {
            $info['filename'] = fn_strtolower(fn_generate_code($info['filename'], 8));
        }

        $new_filename = $info['filename'] . '.' . $info['extension'];
        $new_file_location = $this->getPrivateFilePath($new_filename, 0);

        return fn_copy($source_path, $new_file_location);
    }

    /**
     * Checks if the passed one extension is in the list of the allowed ones.
     *
     * @param string    $ext        Extension to check
     * @param bool|null $is_archive Downloading archives
     *
     * @return bool
     */
    public function checkAllowedExtension($ext, $is_archive = false)
    {
        $allowed_ext = $this->allowed_ext;
        if ($is_archive) {
            $allowed_ext = $this->getAllowedPackExceptions();
        }

        foreach ($allowed_ext as $allowed_extension) {
            if (in_array(strtolower($ext), $allowed_extension)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Handles preset file upload process.
     *
     * @param array<string, string|int|array<string, string>> $preset     Preset data
     * @param int|null                                        $company_id Company to download file for
     *
     * @psalm-suppress PossiblyInvalidArrayOffset
     *
     * @return array<int, array<string, string>> Upload info with preset ID as an array key and fileinfo as value
     */
    public function uploadArchiveImagesFile(array $preset, $company_id = null)
    {
        $preset = array_merge([
            'file_archive_images' => '',
            'type_archive_images' => PresetFileTypes::LOCAL,
            'preset_id'           => 0,
        ], $preset);

        $file = [];
        if (!isset($preset['file_archive_images'])) {
            return $file;
        }

        /** @psalm-suppress PossiblyInvalidIterator */
        foreach ($preset['file_archive_images'] as $num => $path) {
            if ($preset['type_archive_images'][$num] === PresetFileTypes::URL) {
                $downloaded_file = $this->download($path, $company_id, true);
                if ($downloaded_file) {
                    $file[] = $downloaded_file;
                } else {
                    fn_set_notification('E', __('error'), __('advanced_import.cant_load_file'));
                }
            } else {
                $downloaded_file = fn_filter_uploaded_data('archive_images', Registry::get('config.allowed_pack_exts'), true, false);
                if (!$downloaded_file) {
                    return $file;
                }

                foreach ($downloaded_file as $archive) {
                    if (in_array($archive, $file, true)) {
                        continue;
                    }

                    if (!in_array(fn_strtolower(fn_get_file_ext($archive['name'])), Registry::get('config.allowed_pack_exts'), true)) {
                        fn_set_notification('E', __('error'), __('advanced_import.cant_load_file'));
                        continue;
                    }

                    $file[] = $archive;
                }
            }
        }

        return $file;
    }
}
