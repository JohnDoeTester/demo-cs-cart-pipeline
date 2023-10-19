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

namespace Tygh\Addons\VendorPrivileges;

class Privileges
{
    /** @var array $admin_schema Admin permission schema */
    private $admin_schema;

    /** @var array $vendor_schema Admin permission schema */
    private $vendor_schema;

    /**
     * Privileges constructor.
     *
     * @param array $admin_schema
     * @param array $vendor_schema
     */
    public function __construct(array $admin_schema, array $vendor_schema)
    {
        $this->admin_schema = $admin_schema;
        $this->vendor_schema = $vendor_schema;
    }

    /**
     * Extracts privileges that available for user of vendor type from provided permission schemas
     *
     * @return array
     */
    public function getVendorPrivileges()
    {
        $permissions = array();
        $admin_flat_permissions = $this->flattenPermissionSchema($this->admin_schema);

        foreach ($this->vendor_schema['controllers'] as $controller => $controller_data) {
            $controller_permissions = $allowed_permissions = $forbidden_permissions = array();

            if (isset($controller_data['permissions']) && $controller_data['permissions'] !== false) {

                if (isset($admin_flat_permissions["{$controller}_all"])) {
                    $controller_permissions = $admin_flat_permissions["{$controller}_all"];
                }
            }

            if (isset($controller_data['modes'])) {

                foreach ($controller_data['modes'] as $mode => $mode_data) {
                    if (
                        (isset($mode_data['permissions']) || isset($mode_data['condition']))
                        && (isset($admin_flat_permissions["{$controller}_{$mode}"]) || isset($admin_flat_permissions["{$controller}_default_permission"]))
                    ) {
                        $admin_permissions = isset($admin_flat_permissions["{$controller}_{$mode}"]) ? $admin_flat_permissions["{$controller}_{$mode}"] : $admin_flat_permissions["{$controller}_default_permission"];
                        $admin_permissions = $this->normalizePermissions($admin_permissions);

                        $current_permissions = (isset($mode_data['condition']) || isset($mode_data['param_permissions'])) ? true : $mode_data['permissions'];
                        $current_permissions = $this->normalizePermissions($current_permissions);

                        if ($current_permissions['GET'] === false) {
                            $forbidden_permissions[] = $admin_permissions['GET'];
                        } else {
                            $allowed_permissions[] = $admin_permissions['GET'];
                        }

                        if ($current_permissions['POST'] === false) {
                            $forbidden_permissions[] = $admin_permissions['POST'];
                        } else {
                            $allowed_permissions[] = $admin_permissions['POST'];
                        }
                    } elseif (isset($mode_data['param_permissions'])) {

                        foreach ($mode_data['param_permissions'] as $param_name => $param_modes) {

                            foreach ($param_modes as $param_mode => $param_mode_data) {
                                $full_param_key = "{$controller}_{$mode}_param_permission_{$param_name}_{$param_mode}";

                                if (isset($admin_flat_permissions[$full_param_key])) {

                                    if ($param_mode_data === true
                                        || (isset($param_mode_data['permission']) && $param_mode_data['permission'] === true)
                                        || isset($param_mode_data['condition'])
                                    ) {
                                        $allowed_permissions[] = $admin_flat_permissions[$full_param_key];
                                    } else {
                                        $forbidden_permissions[] = $admin_flat_permissions[$full_param_key];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $controller_permissions = array_merge($controller_permissions, $allowed_permissions);
            $forbidden_permissions = array_diff(array_unique($forbidden_permissions), $allowed_permissions);

            $permissions = array_merge($permissions, array_diff($controller_permissions, $forbidden_permissions));
        }

        return array_values(array_unique($permissions));
    }

    /**
     * Converts multilevel permission schema into one level array
     * ["{$controller}_all" => []] - array of all available permissions for the controller
     * ["{$controller}_{$mode}" => [] or ''] - array (or a single string) of all available permissions for the controller + mode
     * ["{$controller}_default_permission"] - array (or a single string) of default controller permissions
     *
     * @param array $schema Permissions schema
     *
     * @return array
     */
    protected function flattenPermissionSchema(array $schema)
    {
        $flat_permissions = array();

        foreach ($schema as $controller => $controller_data) {
            $all_permissions_key = "{$controller}_all";
            $flat_permissions[$all_permissions_key] = array();

            if (isset($controller_data['permissions'])) {
                $flat_permissions["{$controller}_default_permission"] = $controller_data['permissions'];

                if (is_array($controller_data['permissions'])) {
                    $flat_permissions[$all_permissions_key] = array_merge($flat_permissions[$all_permissions_key], array_values($controller_data['permissions']));
                } else {
                    $flat_permissions[$all_permissions_key][] = $controller_data['permissions'];
                }
            }

            if (isset($controller_data['modes'])) {

                foreach ($controller_data['modes'] as $mode => $mode_data) {
                    if (isset($mode_data['permissions'])) {
                        $flat_permissions["{$controller}_{$mode}"] = $mode_data['permissions'];

                        if (is_array($mode_data['permissions'])) {
                            $flat_permissions[$all_permissions_key] = array_merge($flat_permissions[$all_permissions_key], array_values($mode_data['permissions']));
                        } else {
                            $flat_permissions[$all_permissions_key][] = $mode_data['permissions'];
                        }
                    }

                    if (isset($mode_data['param_permissions'])) {

                        foreach ($mode_data['param_permissions'] as $param_name => $param_modes) {

                            foreach ($param_modes as $param_mode => $param_mode_permission) {
                                $flat_permissions[$all_permissions_key][] = $param_mode_permission;
                                $flat_permissions["{$controller}_{$mode}_param_permission_{$param_name}_{$param_mode}"] = $param_mode_permission;
                            }
                        }
                    }
                }
            }

            $flat_permissions[$all_permissions_key] = array_unique($flat_permissions["{$controller}_all"]);
        }

        return $flat_permissions;
    }

    /**
     * Converts permission entry into proper permission array
     *
     * @param array|string|bool $permissions User permission
     *
     * @return array
     */
    protected function normalizePermissions($permissions)
    {
        // array_key_exists check is required for PHP 5.3 issue when `isset($some_string_inside['GET'])` returns true
        $normalized = array(
            'GET'  => is_array($permissions) && array_key_exists('GET', $permissions) ? $permissions['GET'] : $permissions,
            'POST' => is_array($permissions) && array_key_exists('POST', $permissions) ? $permissions['POST'] : false,
        );

        return $normalized;
    }
}
