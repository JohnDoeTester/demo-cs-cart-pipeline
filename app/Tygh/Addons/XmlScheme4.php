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

namespace Tygh\Addons;

use Tygh\Exceptions\DeveloperException;

class XmlScheme4 extends XmlScheme3
{
    /**
     * @var \Tygh\Addons\InstallerInterface|false|null
     */
    protected $custom_installer_instance;

    /**
     * @inheritdoc
     */
    public function loadAddon()
    {
        if ($addon_class = $this->getBootstrapClass()) {
            /** @var \Tygh\Addons\Loader $loader */
            $loader = $this->app['addon_loader'];
            $loader->bootstrap($this);
        } else {
            parent::loadAddon();
        }
    }

    /**
     * @return string FQCN of add-on bootstrap class
     */
    public function getBootstrapClass()
    {
        return (string) $this->_xml->bootstrap;
    }

    public function callCustomFunctions($action)
    {
        if ($installer = $this->getCustomInstallerInstance()) {
            $allowed_methods = [
                'onBeforeInstall',
                'onInstall',
                'onUninstall',
            ];

            if ($installer instanceof InstallerWithDemoInterface) {
                $allowed_methods[] = 'onDemo';
            }

            $action_method_name = 'on' . fn_camelize($action);

            if (in_array($action_method_name, $allowed_methods)) {
                call_user_func([$installer, $action_method_name]);
            }

            return true;
        } else {
            return parent::callCustomFunctions($action);
        }
    }

    /**
     * Initialises instance of add-on custom installer, specified at add-on XML scheme.
     *
     * @return false|\Tygh\Addons\InstallerInterface
     * @throws \Tygh\Exceptions\DeveloperException
     */
    protected function getCustomInstallerInstance()
    {
        if ($this->custom_installer_instance === null) {
            if (!isset($this->_xml->installer)) {
                return $this->custom_installer_instance = false;
            }

            $installer_fqcn = (string) $this->_xml->installer;

            if (!class_exists($installer_fqcn)) {
                throw new DeveloperException(sprintf('Add-on installer class "%s" cannot be found.', $installer_fqcn));
            }

            if (!is_subclass_of($installer_fqcn, InstallerInterface::class, true)) {
                throw new DeveloperException(sprintf(
                    'Add-on installer class "%s" must implement the "%s" interface.',
                    $installer_fqcn,
                    InstallerInterface::class
                ));
            }

            $this->custom_installer_instance = $installer_fqcn::factory($this->app);

            return $this->custom_installer_instance;
        }

        return $this->custom_installer_instance;
    }

    /**
     * @inheritdoc
     */
    public function getPsr4AutoloadEntries()
    {
        $autoload = [];

        foreach($this->_xml->xpath('//autoload/psr4') as $psr4) {
            $autoload[(string) $psr4['prefix']] = $this->getAddonDir() . (string) $psr4;
        }

        return $autoload;
    }
}
