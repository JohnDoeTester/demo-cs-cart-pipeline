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

namespace Tygh\ElFinder;

use elFinderVolumeDriver;
use Exception;
use Tygh\Common\OperationResult;

class Core extends \elFinder
{
    public function __construct($opts)
    {
        parent::__construct($opts);

        // "mount" volumes
        foreach ($opts['roots'] as $i => $o) {
            if (!empty($o['driver']) && strpos($o['driver'], '\\') !== false) {
                $class = $o['driver'];
            } else {
                $class = 'elFinderVolume' . (isset($o['driver']) ? $o['driver'] : '');
            }

            if (class_exists($class)) {
                /* @var elFinderVolumeDriver $volume */
                $volume = new $class();

                try {
                    if ($this->maxArcFilesSize && (empty($o['maxArcFilesSize']) || $this->maxArcFilesSize < $o['maxArcFilesSize'])) {
                        $o['maxArcFilesSize'] = $this->maxArcFilesSize;
                    }
                    // pass session handler
                    $volume->setSession($this->session);
                    if (!$this->default) {
                        $volume->setNeedOnline(true);
                    }
                    if ($volume->mount($o)) {
                        // unique volume id (ends on "_") - used as prefix to files hash
                        $id = $volume->id();

                        $this->volumes[$id] = $volume;
                        if ((!$this->default || $volume->root() !== $volume->defaultPath()) && $volume->isReadable()) {
                            $this->default = $volume;
                        }
                    } else {
                        if (!empty($o['_isNetVolume'])) {
                            $this->removeNetVolume($i, $volume);
                        }
                        $this->mountErrors[] = 'Driver "' . $class . '" : ' . implode(' ', $volume->error());
                    }
                } catch (Exception $e) {
                    if (!empty($o['_isNetVolume'])) {
                        $this->removeNetVolume($i, $volume);
                    }
                    $this->mountErrors[] = 'Driver "' . $class . '" : ' . $e->getMessage();
                }
            } else {
                $this->mountErrors[] = 'Driver "' . $class . '" does not exists';
            }
        }

        // if at least one redable volume - ii desu >_<
        $this->loaded = !empty($this->default);
    }

    /**
     * Checks whether file with the specified extension can be created, renamed or uploaded in the volume.
     *
     * @param \Tygh\ElFinder\Volume $volume   Target file volume
     * @param string                $filename Filename
     *
     * @return \Tygh\Common\OperationResult
     */
    protected function tyghIsFileExtensionAllowed($volume, $filename)
    {
        $result = new OperationResult(true);

        $file_extension = fn_strtolower(fn_get_file_ext($filename));

        $forbidden_extensions = array_intersect(
            $volume->getMimeTable(),
            $volume->tyghGetDeniedMimeTypes()
        );

        if (isset($forbidden_extensions[$file_extension])) {
            $result->setSuccess(false);
            $result->addError(
                0,
                strip_tags(
                    __('text_forbidden_file_extension', array('[ext]' => $file_extension))
                )
            );
        }

        return $result;
    }

    /**
     * Checks whether the file extension is allowed and then renames the file.
     *
     * @see \elFinder::rename()
     *
     * @param array $args Renamed file
     *
     * @return array Operation result
     **/
    protected function rename($args)
    {
        /** @var \Tygh\ElFinder\Volume $volume */
        $volume = $this->getVolume($args['target']);

        $ext_check = $this->tyghIsFileExtensionAllowed($volume, $args['name']);

        if ($ext_check->isSuccess()) {
            return parent::rename($args);
        } else {
            return array('error' => $this->error($ext_check->getFirstError()));
        }
    }

    /**
     * Checks whether the file extension is allowed and then creates the file.
     *
     * @see \elFinder::mkfile()
     *
     * @param array $args Created file
     *
     * @return array Operation result
     **/
    protected function mkfile($args)
    {
        /** @var \Tygh\ElFinder\Volume $volume */
        $volume = $this->getVolume($args['target']);

        $ext_check = $this->tyghIsFileExtensionAllowed($volume, $args['name']);

        if ($ext_check->isSuccess()) {
            return parent::mkfile($args);
        } else {
            return array('error' => $this->error($ext_check->getFirstError()));
        }
    }
}
