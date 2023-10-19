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

namespace Tygh\Backend\Storage;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\S3UriParser;
use Tygh\Storage;
use Tygh\Registry;

class Amazon extends ABackend
{
    const LOCATION = 'remote';

    /**
     * @var S3Client
     */
    private $_s3;
    private $_buckets;

    /**
     * Copy file outside the storage
     *
     * @param  string $src  file path in storage
     * @param  string $dest path to local file
     * @return bool
     */
    public function export($src, $dest)
    {
        if (!fn_mkdir(dirname($dest))) {
            return false;
        }

        try {
            $object_result = $this->s3()->getObject([
                'Bucket' => $this->getOption('bucket'),
                'Key' => $this->prefix($src),
                'SaveAs' => $dest
            ]);
        } catch (AwsException $e) {
            fn_set_notification('E', __('error'), (string) $e->getMessage());
        }

        if (!empty($object_result)) {
            return true;
        }

        return false;
    }

    /**
     * Put file to storage
     *
     * @param  string $file   file path in storage
     * @param  array  $params uploaded data and options
     * @return array  file size and file name, boolean false otherwise
     */
    public function put($file, $params)
    {
        if (empty($params['overwrite'])) {
            $file = $this->generateName($file); // check if name is unique and generate new if not
        }
        $file = $this->prefix($file);

        $s3 = $this->s3(); // get object to initialize class and get access to contstants below

        $data = []; // params to put object

        $data['acl'] = 'public-read';

        if (!empty($params['compress'])) {

            $data['content_encoding'] = 'gzip';
            $data['cache_control'] = 'private';

            if (!empty($params['contents'])) {
                $params['contents'] = gzencode($params['contents']);
            }
        }

        // File can not be accessible via direct link
        if ($this->getOption('secured')) {
            $data['content_disposition'] = 'attachment';
            $data['acl'] = 'private';
        }

        $data['content_type'] = fn_get_file_type($file);

        if (!empty($params['contents'])) {
            $data['body'] = $params['contents'];
        } else {
            $data['fileUpload'] = $params['file'];
        }

        try {
            $put_object_result = $s3->putObject([
                'Bucket' => $this->getOption('bucket'),
                'Key' => $file,
                'SourceFile' => $params['file'],
                'ACL' => $data['acl'],
                'Body' => !empty($data['body']) ? $data['body'] : '',
                'CacheControl' => !empty($data['cache_control']) ? $data['cache_control'] : '',
                'ContentDisposition' => !empty($data['content_disposition']) ? $data['content_disposition'] : '',
                'ContentEncoding' => !empty($data['content_encoding']) ? $data['content_encoding'] : '',
                'ContentType' => $data['content_type']
            ]);
        } catch (AwsException $e) {
            fn_set_notification('E', __('error'), (string) $e->getMessage());
        }

        if (!empty($put_object_result)) {

            if (!empty($params['caching'])) {
                Registry::set('s3_' . $this->getOption('bucket') . '.' . md5($file), true);
            }

            if (!empty($params['file'])) {
                $filesize = filesize($params['file']);

                if (empty($params['keep_origins'])) {
                    fn_rm($params['file']);
                }
            } else {
                $filesize = strlen($params['contents']);
            }

            return array($filesize, str_replace($this->prefix(), '', $file));
        }

        return false;
    }

    /**
     * Put directory to storage
     *
     * @param  string  $dir    directory to get files from
     * @param  array   $params additional parameters
     * @return boolean true of success, false on fail
     */
    public function putDir($dir, $params = array())
    {
        $s3 = $this->s3(); // get object to initialize class and get access to contstants below

        $files = fn_get_dir_contents($dir, false, true, '', '', true);
        fn_set_progress('step_scale', sizeof($files));

        $data = []; // params to put object

        foreach ($files as $source_file) {
            fn_set_progress('echo', '.');

            $data['acl'] = 'public-read';

            // File can not be accessible via direct link
            if ($this->getOption('secured')) {
                $data['content_disposition'] = 'attachment';
                $data['acl'] = 'private';
            }

            $data['contentType'] = fn_get_file_type($source_file);
            $data['fileUpload'] = $dir . '/' . $source_file;

            try {
                $put_object_result = $s3->putObject([
                    'Bucket' => $this->getOption('bucket'),
                    'Key' => fn_basename($dir) . '/' . $source_file,
                    'SourceFile' => $data['fileUpload'],
                    'ACL' => $data['acl'],
                    'Body' => !empty($data['body']) ? $data['body'] : '',
                    'CacheControl' => !empty($data['cache_control']) ? $data['cache_control'] : '',
                    'ContentDisposition' => !empty($data['content_disposition']) ? $data['content_disposition'] : '',
                    'ContentEncoding' => !empty($data['content_encoding']) ? $data['content_encoding'] : '',
                    'ContentType' => $data['content_type']
                ]);
            } catch (AwsException $e) {
                fn_set_notification('E', __('error'), (string) $e->getMessage());
            }

        }

        return true;
    }

    /**
     * Get file URL
     *
     * @param string $file     File to get URL
     * @param string $protocol Protocol (http, https or short)
     * @param string $url      Storefront URL
     *
     * @return string file URL
     */
    public function getUrl($file = '', $protocol = '', $url = '')
    {
        if (strpos($file, '://') !== false) {
            return $file;
        }

        if ($protocol == 'http') {
            $prefix = 'http://';
        } elseif ($protocol == 'https') {
            $prefix = 'https://';
        } elseif ($protocol == 'short') {
            $prefix = '//';
        } else {
            $prefix = defined('HTTPS') ? 'https://' : 'http://';
        }

        $host = $this->getOption('host');
        if (empty($host)) {
            $host = $this->getOption('region');
        }
        $host .= '/' . $this->getOption('bucket');

        return $prefix . $host . '/' . $this->prefix($file);
    }

    /**
     * Gets absolute path to file
     *
     * @param  string $file file to get path
     * @return string absolute path
     */
    public function getAbsolutePath($file)
    {
        return $this->getUrl($file);
    }

    /**
     * Push file contents to browser, link to file is active for one hour
     *
     * @param  string $file     file to push
     * @param  string $filename file name to be displayed in download dialog, not supported
     * @return void
     */
    public function get($file, $filename = '')
    {
        header('Location: ' . $this->s3()->getObjectUrl($this->getOption('bucket'), $this->prefix($file)));
    }

    /**
     * Deletes file
     *
     * @param  string  $file file to delete
     * @return boolean true if deleted successfully, false - otherwise
     */
    public function delete($file)
    {
        $file = $this->prefix($file);

        try {
            $delete_result = $this->s3()->deleteObject([
                'Bucket' => $this->getOption('bucket'),
                'Key' => $file
            ]);
        } catch (AwsException $e) {
            fn_set_notification('E', __('error'), (string) $e->getMessage());
        }

        if (!empty($delete_result)) {
            $cache_name = 's3_' . $this->getOption('bucket');
            Registry::registerCache($cache_name, array(), Registry::cacheLevel('static'), true);
            Registry::del($cache_name . '.' . md5($file));

            return true;
        }

        return false;
    }

    /**
     * Deletes directory and all it files
     *
     * @param  string  $dir directory to delete
     * @return boolean true if deleted successfully
     */
    public function deleteDir($dir = '')
    {
        $dir = rtrim($this->prefix($dir), '/') . '/';

        try {
            $this->s3()->deleteMatchingObjects($this->getOption('bucket'), $dir);
        } catch (AwsException $e) {
            fn_set_notification('E', __('error'), (string) $e->getMessage());
        }

        return true;
    }

    /**
     * Deletes files using glob pattern
     *
     * @param  string  $pattern glob-compatible pattern
     * @return boolean true if deleted successfully
     */
    public function deleteByPattern($pattern)
    {
        $p = preg_quote($this->prefix($pattern), '/');
        $p = preg_quote($pattern, '/');
        $p = str_replace('\*', '[^\/]*', $p);
        $p = str_replace('\?', '.', $p);

        try {
            $this->s3()->deleteMatchingObjects($this->getOption('bucket'), null, sprintf('/%s/i', $p));
        } catch (AwsException $e) {
            fn_set_notification('E', __('error'), (string) $e->getMessage());
        }

        return true;
    }

    /**
     * Checks if file exists
     *
     * @param  string  $file     file to check
     * @param  string  $in_cache indicates that file existance should be checked in cache only (useful for non-local storages)
     * @return boolean true if exists, false - otherwise
     */
    public function isExist($file, $in_cache = false)
    {
        $file = $this->prefix($file);

        $cache_name = 's3_' . $this->getOption('bucket');
        Registry::registerCache($cache_name, array(), Registry::cacheLevel('static'), true);
        $is_exist = Registry::get($cache_name . '.' . md5($file));

        if ($in_cache == false && $is_exist == false && $is_exist = $this->s3()->doesObjectExist($this->getOption('bucket'), $file)) {
            Registry::set($cache_name . '.' . md5($file), true);
        }

        return $is_exist;
    }

    /**
     * Copy files inside storage (FIXME: now supports max 1000 items to copy)
     *
     * @param  string  $src  source file/directory
     * @param  string  $dest destination file/directory
     * @return boolean true if copied successfully, false - otherwise
     */
    public function copy($src, $dest)
    {
        $src = $this->prefix($src);
        $dest = $this->prefix($dest);

        try {
            $result_copy_objects = $this->s3()->copyObject([
                'Bucket' => $this->getOption('bucket'),
                'Key' => $dest,
                'CopySource' => $this->getOption('bucket') . '/' .$src
            ]);
        } catch (AwsException $e) {
            fn_set_notification('E', __('error'), (string) $e->getMessage());
        }

        if (!empty($result_copy_objects)) {
            return true;
        }

        return false;
    }

    /**
     * Lists files
     * @param  string $prefix path prefix
     * @return array  files list
     */
    public function getList($prefix = '')
    {
        $prefix = $this->prefix($prefix);

        try {
            $result_list_objects = $this->s3()->listObjects([
                'Bucket' => $this->getOption('bucket'),
                'Prefix' => $prefix
            ]);
        } catch (AwsException $e) {
            fn_set_notification('E', __('error'), (string) $e->getMessage());
        }

        $object_list = [];
        if (!empty($result_list_objects)) {
            foreach ($result_list_objects->toArray()['Contents'] as $key => $value) {
                $object_list[] = $value['Key'];
            }
        }

        if (!empty($object_list)) {
            $prefix_len = strlen($prefix);
            foreach ($object_list as $item_key => $item) {
                $object_list[$item_key] = substr_replace($item, '', 0, $prefix_len);
            }
        }

        return $object_list;
    }

    /**
     * Adds prefix to file path
     *
     * @param  string $file file
     * @return string prefixed file path
     */
    protected function prefix($file = '')
    {
        $path = parent::prefix($file);

        fn_set_hook('storage_prefix', $path, $this->type);

        return $path;
    }

    /**
     * Tests storage settings
     *
     * @param  array $settings settings list
     * @return mixed boolean true if settings are correct, error message (string) otherwise
     */
    public function testSettings($settings)
    {
        $old_options = $this->options;

        $this->options = fn_array_merge($this->options, $settings);
        $this->_s3 = null;

        $result = $this->s3();

        $this->_s3 = null;
        $this->options = $old_options;

        if (is_object($result)) {
            return true;
        }

        return false;
    }

    /**
     * Gets s3 object
     *
     * @return S3Client s3 object
     */
    public function s3()
    {
        if (empty($this->_s3)) {

            $credentials = new Credentials($this->getOption('key'), $this->getOption('secret'));

            $parse_uri = (new S3UriParser())->parse('http://' . $this->getOption('region'));

            $this->_s3 = new S3Client([
                'region' => $parse_uri['region'],
                'version' => 'latest',
                'credentials' => $credentials
            ]);

            $bucket_list = [];
            foreach ($this->_s3->listBuckets()->toArray()['Buckets'] as $key => $value) {
                $bucket_list[] = $value['Name'];
            }
            $this->_buckets = fn_array_combine($bucket_list, true);
        }

        $bucket = $this->getOption('bucket');

        if (empty($this->_buckets[$bucket])) {

            try {
                $create_result = $this->_s3->createBucket([
                    'Bucket' => $bucket,
                ]);
            } catch (AwsException $e) {
                fn_set_notification('E', __('error'), (string) $e->getMessage());
            }

            if (!empty($create_result)) {
                try {
                    $result_put_bucket_cors = $this->_s3->putBucketCors([
                        'Bucket' => $bucket, // REQUIRED
                        'CORSConfiguration' => [ // REQUIRED
                            'CORSRules' => [ // REQUIRED
                                [
                                    'AllowedMethods' => ['GET'], // REQUIRED
                                    'AllowedOrigins' => ['*'] // REQUIRED
                                ],
                            ],
                        ]
                    ]);
                } catch (AwsException $e) {
                    fn_set_notification('E', __('error'), (string) $e->getMessage());
                }

                if (!empty($result_put_bucket_cors)) {
                    $this->_buckets[$bucket] = true;
                }
            }
        }

        return $this->_s3;
    }
}
