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

namespace Tygh;

use Tygh\Api\AEntity;
use Tygh\Api\FormatManager;
use Tygh\Api\Request;
use Tygh\Api\Response;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;

class Api
{
    const CURRENT_VERSION = '2.1';

    /**
     * Key of resource name in _REQUEST
     *
     * @const REST_PATH_PARAM_NAME
     */
    const DEFAULT_REQUEST_FORMAT = 'text/plain';

    /**
     * Key of resource name in _REQUEST
     *
     * @const REST_PATH_PARAM_NAME
     */
    const DEFAULT_RESPONSE_FORMAT = 'application/json';

    /**
     * Key of resource name in _REQUEST
     *
     * @const REST_PATH_PARAM_NAME
     */
    const REST_RESOURCE_PARAM_NAME = '_d';

    /**
     * Length of API keys
     *
     * @const API_KEY_LENGTH
     */
    const API_KEY_LENGTH = 32;

    /**
     * Auth data
     * (user => 'user name', api_key => 'API KEY')
     *
     * @var array $auth
     */
    protected $auth = array();

    /**
     * Current area
     *
     * @var array $area
     */
    protected $area = null;

    /**
     * Current request data
     *
     * @var Request
     */
    protected $request = null;

    /**
     * @var array
     */
    protected $user_data = array();

    protected $called_version = '2.1';

    protected $fake_entities = array(
        'version' => array(
            'index' => 'getVersion',
        ),
    );

    /**
     * Map of the fallback versions.
     * If the resource is not found in the required version,
     * then an attempt will be made to find the resource in the fallback version.
     * @var array
     */
    protected $versions_fallback = array(
        '2.0' => '2.1',
        '4.0' => '2.1',
    );

    /**
     * Creates API instance
     *
     * @param  array $formats
     */
    public function __construct($formats = array('json', 'text', 'form'))
    {
        FormatManager::initiate($formats);
        $this->request = new Request();

        if (!$this->protocolValidator()) {
            $response = new Response(Response::STATUS_FORBIDDEN, 'The API is only accessible over HTTPS');
            $response->send();
        }

        $this->defineArea();
    }

    /**
     * Handles request.
     * Method gets request from entities and send it
     *
     * @param null|Request $request Request object if empty will be created and filled from current HTTP request automatically
     */
    public function handleRequest($request = null)
    {
        if ($request instanceof Request) {
            $this->request = $request;
        }

        $authorized = $this->authenticate();

        /**
         * Rewrite default API behavior
         *
         * @param object $this       Api instance
         * @param bool   $authorized Authorization flag
         */
        fn_set_hook('api_handle_request', $this, $authorized);

        if ($authorized || Registry::get('config.tweaks.api_allow_customer')) {

            $content_type = $this->request->getContentType();
            $accept_type = $this->request->getAcceptType();
            $method = $this->request->getMethod();

            if ($method == "OPTIONS") {
                $response = new Response(Response::STATUS_OK);
            } elseif (($method == "PUT" || $method == "POST") && !FormatManager::instance()->isMimeTypeSupported($content_type)) {
                $response = new Response(Response::STATUS_UNSUPPORTED_MEDIA_TYPE);
            } elseif (($method == "GET" || $method == "HEAD") && !FormatManager::instance()->isMimeTypeSupported($accept_type)) {
                $response = new Response(Response::STATUS_METHOD_NOT_ACCEPTABLE);
            } elseif ($this->request->getError()) {
                $response = new Response(Response::STATUS_BAD_REQUEST, $this->request->getError(), $accept_type);
            } else {
                $controller_result = $this->getResponse($this->request->getResource());

                if (is_a($controller_result, '\\Tygh\\Api\\Response')) {
                    $response = $controller_result;
                } else {
                    $response = new Response(Response::STATUS_INTERNAL_SERVER_ERROR);
                }
            }
        } else {
            $response = new Response(Response::STATUS_UNAUTHORIZED);
        }

        $response->send();
    }

    public function protocolValidator()
    {
        if (!defined('HTTPS') && Registry::get('config.tweaks.api_https_only')) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get user data by request user and api key.
     *
     * @return array
     */
    public function getUserData()
    {
        fn_set_hook('api_get_user_data_pre', $this, $this->user_data);

        if (!$this->user_data) {
            $auth = $this->request->getAuthData();

            /**
             * Executes right after obtaining user authentication data from API request headers.
             * Allows to modify data that is used to identify the user who accesses API.
             *
             * @param \Tygh\Api $this API instance
             * @param string[]  $auth Authetication data from request headers
             */
            fn_set_hook('api_get_user_data', $this, $auth);

            if ($auth) {
                $this->user_data = fn_get_api_user(
                    isset($auth['user'])    ? $auth['user']    : null,
                    isset($auth['api_key']) ? $auth['api_key'] : null,
                    isset($auth['token'])   ? $auth['token']   : null
                );

                // Disabled users can't use the API
                if (!$this->user_data || $this->user_data['status'] != 'A') {
                    $response = new Response(Response::STATUS_UNAUTHORIZED);
                    $response->send();
                }

                $this->user_data['is_token_auth'] = isset($auth['token']);
            }
        }

        return $this->user_data;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Define current area depends on user type.
     */
    protected function defineArea()
    {
        $user_data = $this->getUserData();

        $area = SiteArea::STOREFRONT;
        // when authenticating a user with auth token, force area to customer
        if ($user_data && !$user_data['is_token_auth']) {
            if (UserTypes::isAdmin($user_data['user_type'])) {
                $area = SiteArea::ADMIN_PANEL;
                fn_define('ACCOUNT_TYPE', 'admin');
            } elseif (UserTypes::isVendor($user_data['user_type'])) {
                $area = SiteArea::ADMIN_PANEL;
                fn_define('ACCOUNT_TYPE', 'vendor');
            }
        }

        $this->area = $area;
        fn_define('AREA', $area);
    }

    /**
     * Tries to authenticate user
     *
     * @return bool True on success, false otherwise
     */
    protected function authenticate()
    {
        $user_data = $this->getUserData();

        $auth = fn_fill_auth($user_data);
        $auth['is_token_auth'] = !empty($user_data['is_token_auth']);

        $this->auth = Tygh::$app['session']['auth'] = $auth;
        $area = $this->area;

        /** @psalm-var string $area */
        if (
            !empty($this->auth['usergroup_ids'])
            && SiteArea::isAdmin($area)
            && !UserTypes::isVendor($this->auth['user_type'])
        ) {
            fn_define('RESTRICTED_ADMIN', true);
        }

        // Return value must be bool
        return !empty($this->auth['user_id']);
    }

    /**
     * Return response
     *
     * @param  string   $resource REST resource name (products/1, users, etc.)
     * @return Response Response
     */
    protected function getResponse($resource)
    {
        $response = null;

        if ($resource) {
            $entity_properties = $this->getEntityFromPath($resource);
            $response = $this->getResponseFromEntity($entity_properties);
        }

        return ($response != null) ? $response : new Response(Response::STATUS_NOT_FOUND);
    }

    /**
     * Creates entity object of resource, runs it method and return response
     *
     * @param  array    $entity_properties Properties of entity
     * @param  string   $parent_name       Parent entity name
     * @param  array    $parent_data       Parent entity data
     *
     * @return Response Response or null
     */
    protected function getResponseFromEntity($entity_properties, $parent_name = null, $parent_data = null)
    {
        $response = null;

        $entity = $this->getObjectByEntity($entity_properties);

        /**
         * Fake entity can't have parent
         */
        if ($entity !== null || (isset($this->fake_entities[$entity_properties['name']]) && !$parent_data)) {

            if (!empty($parent_data['data'])) {
                $entity->setParentName($parent_name);
                $entity->setParentData($parent_data['data']);
            }

            if (!empty($entity_properties['id']) && !$entity->isValidIdentifier($entity_properties['id'])) {
                $response = null;

            } elseif (!empty($entity_properties['child_entity'])) {

                $parent_result = array('status' => Response::STATUS_FORBIDDEN);

                if ($this->checkAccess($entity, 'index')) {
                    $parent_result = $entity->index($entity_properties['id']);
                }

                if (Response::isSuccessStatus($parent_result['status'])) {
                    $name = $entity_properties['name'];
                    $entity_properties = $this->getEntityFromPath($entity_properties['child_entity']);

                    $response = $this->getResponseFromEntity($entity_properties, $name, $parent_result);
                } else {
                    $response = new Response($parent_result['status']);
                }
            } else {
                $response = $this->exec($entity, $entity_properties);
            }
        } else {
            $response = new Response(Response::STATUS_NOT_FOUND, __('object_not_found', array('[object]' => __('entity') . ' ' . $entity_properties['name'])), $this->request->getAcceptType());
        }

        return $response;
    }

    /**
    * Executes entity method
    *
    * @param  \Tygh\Api\AEntity   $entity            Entity object
    * @param  array    $entity_properties Properties of entity
    * @return Response Response
    */
    protected function exec($entity, $entity_properties)
    {
        $response = null;

        $accept_type = $this->request->getAcceptType();
        $http_method = $this->request->getMethod();
        $method_name = $this->getMethodName($http_method);

        $request_data = $this->request->getData();

        if ($this->request->getError()) {
            $response = new Response(Response::STATUS_BAD_REQUEST, $this->request->getError(), $accept_type);
        } elseif (!$method_name) {
            $response = new Response(Response::STATUS_METHOD_NOT_ALLOWED);
        } elseif (isset($this->fake_entities[$entity_properties['name']])) {
            $fake_entity = $this->fake_entities[$entity_properties['name']];
            if (is_array($fake_entity) && !empty($fake_entity[$method_name]) && method_exists($this, $fake_entity[$method_name])) {
                $result = $this->{$fake_entity[$method_name]}();
                $response = new Response($result['status'], $result['data']);
            } elseif (is_string($fake_entity) && method_exists($this, $fake_entity)) {
                $result = $this->$fake_entity();
                $response = new Response($result['status'], $result['data']);
            } else {
                $response = new Response(Response::STATUS_FORBIDDEN);
            }
        } elseif (!$this->checkAccess($entity, $method_name)) {
            $response = new Response(Response::STATUS_FORBIDDEN);
        } else {
            $reflection_method = new \ReflectionMethod($entity, $method_name);
            $accepted_params = $reflection_method->getParameters();
            $call_params = array();

            if (fn_allowed_for('ULTIMATE')) {
                if ($http_method == 'POST' || $http_method == 'PUT') {
                    fn_ult_parse_api_request($entity_properties['name'], $request_data);
                }
            }

            foreach ($accepted_params as $param) {
                $param_name = $param->getName();

                if ($param_name == 'id') {
                    $call_params[] = !empty($entity_properties['id']) ? $entity_properties['id'] : '';

                    if (empty($entity_properties['id']) && !$param->isOptional()) {
                        $response = new Response(Response::STATUS_METHOD_NOT_ALLOWED, __('api_need_id'), $accept_type);
                    }
                }

                if ($param_name == 'params') {
                    $call_params[] = $request_data;

                    if (empty($request_data) && !$param->isOptional()) {
                        $response = new Response(Response::STATUS_METHOD_NOT_ALLOWED, __('api_need_params'), $accept_type);
                    }
                }
            }

            if ($http_method != 'POST' || empty($entity_properties['id'])) {
                if ($response == null) {
                    $controller_result = $reflection_method->invokeArgs($entity, $call_params);

                    if (!empty($controller_result['status'])) {
                        $data = isset($controller_result['data']) ? $controller_result['data'] : array();
                        $response = new Response($controller_result['status'], $data, $accept_type);

                    } else {
                        $response = new Response(Response::STATUS_INTERNAL_SERVER_ERROR);
                    }
                }
            } else {
                $response = new Response(Response::STATUS_METHOD_NOT_ALLOWED, __('api_not_need_id'), $accept_type);
            }
        }

        return $response;
    }

    /**
     * Checks that current authetificated user can access to $entity and it $method_name
     *
     * @param  \Tygh\Api\AEntity $entity      Entity instance
     * @param  string            $method_name Entity method name
     * @return bool              True on success, false otherwise
     */
    protected function checkAccess($entity, $method_name)
    {
        $can_access = false;

        if ($entity instanceof AEntity && method_exists($entity, $method_name)) {
            $can_access = $entity->isAccessable($method_name);
        }

        fn_set_hook('api_check_access', $this, $entity, $method_name, $can_access);

        return $can_access;
    }

    /**
     * Returns entity method name by request method name
     *
     * @param  string $http_method_name (GET|POST|PUT|DELETE)
     * @return string method name
     */
    public function getMethodName($http_method_name)
    {
        $method = '';

        if ($http_method_name == 'GET') {
            $method = 'index';
        } elseif ($http_method_name == 'POST') {
            $method = 'create';
        } elseif ($http_method_name == 'PUT') {
            $method = 'update';
        } elseif ($http_method_name == 'DELETE') {
            $method = 'delete';
        }

        return $method;
    }

    /**
     * Converts list of ReflectionParameter objects to array with params name
     *
     * @param  array $reflection_params List of ReflectionParameter obejcts
     * @return array List of params names
     */
    protected function reflectionParamsToArray($reflection_params)
    {
        $params = array();

        foreach ($reflection_params as $param) {
            $params[] = $param->getName();
        }

        return $params;
    }

    /**
     * Explodes entity properties from resource name
     *
     * @param  string $resource_name REST resource name
     * @return array  Entity properties data
     */
    public function getEntityFromPath($resource_name)
    {
        $result = array(
            "name" => "",
            "id" => "",
        );

        if (!preg_match("/\/{2,}/", $resource_name)) {
            $resource_name = preg_replace("/\/$/", "", $resource_name);
            $resource_name = explode("/", $resource_name);

            if (!empty($resource_name[0]) && is_numeric($resource_name[0])) {
                $this->called_version = array_shift($resource_name);
            }

            if (!empty($resource_name[0])) {
                $result['name'] = array_shift($resource_name);

                if (!empty($resource_name[0])) {
                    $result['id'] = array_shift($resource_name);
                }

                if (!empty($resource_name[0])) {
                    //$result['child_entity'] = $this->getEntityFromPath(implode("/", $resource_name));
                    $result['child_entity'] = implode("/", $resource_name);

                }
            }
        }

        return $result;
    }

    /**
     * Returns instance of Entity class by entity properties
     *
     * @param  array             $entity_properties Entity properties data @see Api::getEntityFromPath
     *
     * @return \Tygh\Api\AEntity|null Returns an instance of the entity class on success, otherwise null.
     */
    protected function getObjectByEntity($entity_properties)
    {
        $class_name = $this->getEntityClass($entity_properties['name'], $this->called_version);

        return $class_name === false ? null : new $class_name($this->auth, $this->area);
    }

    /**
     * Generates new API key
     *
     * @return string API key
     */
    public static function generateKey()
    {
        $length = Api::API_KEY_LENGTH;
        $key = "";

        for ($i = 1; $i <= $length; $i++) {
            $chr = rand(0, 1) ? (chr(rand(65, 90))) : (chr(rand(48, 57)));

            if (rand(0, 1)) {
                $chr = strtolower($chr);
            }

            $key .= $chr;
        }

        return $key;
    }

    /**
     * Set version of API
     *
     * @param string $version version of api
     */
    public function setVersion($version)
    {
        $this->called_version = $version;
    }

    /**
     * Gets response data for Api version request
     *
     * @return array
     */
    protected function getVersion()
    {
        return array(
            'status' => Response::STATUS_OK,
            'data' => array('Version' => $this->called_version),
        );
    }

    /**
     * Gets a fully qualified class name for entity.
     *
     * @param string $entity_name   Entity name
     * @param string $version       Required version
     *
     * @return string|false Returns a fully qualified class name on success, otherwise false.
     */
    protected function getEntityClass($entity_name, $version)
    {
        $version_namespace = '';

        if ($version !== self::CURRENT_VERSION) {
            $version_namespace = 'v' . str_replace('.', '', $version) . '\\';
        }

        $result = '\\Tygh\\Api\\Entities\\' . $version_namespace . fn_camelize($entity_name);

        if (!class_exists($result) && isset($this->versions_fallback[$version])) {
            $result = $this->getEntityClass($entity_name, $this->versions_fallback[$version]);
        }

        return $result && class_exists($result) ? $result : false;
    }
}
