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


namespace Tygh\Web;

use Traversable;
use Tygh\Application;
use Tygh\Tools\SecurityHelper;
use Tygh\Backend\Session\ABackend;

/**
 * Class Session is a component used to handle accessing session data and saving it to persistent storage.
 *
 * @package Tygh\Web
 *
 * phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
 * phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
 * phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 */
class Session implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var \Tygh\Application
     */
    protected $app;

    /**
     * @var bool Whether to start session at the component initialization time. Defaults to false.
     */
    public $start_on_init = false;

    /**
     * @var bool Whether to start session at the first read access to session data. Defaults to false.
     */
    public $start_on_read = false;

    /**
     * @var bool Whether to start session at the first write access to session data. Defaults to false.
     */
    public $start_on_write = false;

    /**
     * @var string Classname of session storage driver.
     */
    public $storage_class = '\Tygh\Backend\Session\Database';

    /**
     * @var string|null Prefix added to the session name.
     */
    protected $session_name_prefix;

    /**
     * @var string|null Suffix added to the session name.
     */
    protected $session_name_suffix;

    /**
     * @var string|null Suffix added to the session ID.
     */
    protected $session_id_suffix;

    /**
     * @var ABackend Instance of session data storage driver.
     */
    protected $storage_driver;

    /**
     * @var string
     *
     * @see http://php.net/manual/en/session.configuration.php#ini.session.cookie-path
     */
    public $cookie_path = '/';

    /**
     * @var string
     *
     * @see http://php.net/manual/en/session.configuration.php#ini.session.cookie-domain
     */
    public $cookie_domain = '';

    /**
     * @var int
     *
     * @see http://php.net/manual/en/session.configuration.php#ini.session.cookie-lifetime
     */
    public $cookie_lifetime = 0;

    /**
     * @var bool
     *
     * @see http://php.net/manual/en/session.configuration.php#ini.session.cookie-secure
     */
    public $cookie_secure = false;

    /**
     * @var bool
     *
     * @see http://php.net/manual/en/session.configuration.php#ini.session.cookie-httponly
     */
    public $cookie_httponly = true;

    /**
     * @var string
     *
     * @see https://www.php.net/manual/en/session.configuration.php#ini.session.cookie-samesite
     */
    public $cookie_samesite = 'lax';

    /**
     * @var int
     *
     * @see http://php.net/manual/en/session.configuration.php#ini.session.gc-probability
     */
    public $gc_probability = 1;

    /**
     * @var int
     *
     * @see http://php.net/manual/en/session.configuration.php#ini.session.gc-divisor
     */
    public $gc_divisor = 10;

    /**
     * @var string
     *
     * @see http://php.net/manual/en/session.configuration.php#ini.session.cache-limiter
     */
    public $cache_limiter = 'nocache';

    /**
     * @var int
     *
     * @see http://php.net/manual/en/session.configuration.php#ini.session.cache-expire
     */
    public $cache_expire = 180;

    /**
     * @var int Minimum period for GC execution in minutes. GC will be executed only when period elapsed.
     */
    private $gc_periond = 10;

    /**
     * @var array<callable>
     */
    public $gc_handlers = [];

    /**
     * @var array<callable>
     */
    public $writability_handlers = [];

    /**
     * @var bool
     */
    private $settings_are_save_required = false;

    /**
     * Session constructor.
     *
     * @param \Tygh\Application $application Application IoC container instance.
     */
    public function __construct(Application $application)
    {
        $this->app = $application;

        // Define the superglobal, because if session will not start,
        // third-party add-ons that use $_SESSION directly will catch PHP warnings
        $_SESSION = [];

        register_shutdown_function([$this, 'shutdown']);
    }

    /**
     * Initializes the component.
     * Starts the session if {{$start_on_init}} parameter is enabled.
     *
     * @return void
     */
    public function init()
    {
        if (!$this->start_on_init) {
            return;
        }

        $this->start();
    }

    /**
     * @return null|string Prefix added to the session name
     */
    public function getSessionNamePrefix()
    {
        return $this->session_name_prefix;
    }

    /**
     * @param null|string $session_name_prefix Prefix added to the session name
     */
    public function setSessionNamePrefix($session_name_prefix)
    {
        $this->session_name_prefix = $session_name_prefix;
    }

    /**
     * @return null|string Suffix added to the session name
     */
    public function getSessionNameSuffix()
    {
        return $this->session_name_suffix;
    }

    /**
     * @param null|string $session_name_suffix Suffix added to the session name
     */
    public function setSessionNameSuffix($session_name_suffix)
    {
        $this->session_name_suffix = $session_name_suffix;
    }

    /**
     * @return null|string
     */
    public function getSessionIDSuffix()
    {
        return $this->session_id_suffix;
    }

    /**
     * @param null|string $session_id_suffix Session suffix
     */
    public function setSessionIDSuffix($session_id_suffix)
    {
        $this->session_id_suffix = $session_id_suffix;
    }

    /**
     * @return bool Whether the session is active.
     */
    public function isStarted()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Returns session name. A wrapper for session_name().
     *
     * @return string Session name
     */
    public function getName()
    {
        return session_name();
    }

    /**
     * Sets new session name. A wrapper for session_name().
     *
     * @param string $name New session name
     */
    public function setName($name)
    {
        /**
         * Actions before setting session name
         *
         * @param string $account_type Current account type
         * @param string $sess_postfix Session postfix
         */
        fn_set_hook('session_set_name_before', $name, $this->session_name_suffix);

        session_name($this->session_name_prefix . $name . $this->session_name_suffix);
    }

    /**
     * Sets new session ID. A wrapper for session_id().
     *
     * @param string $id New session ID
     *
     * @return string Returns session_id() call result
     */
    public function setID($id)
    {
        return session_id($id);
    }

    /**
     * Gets the session ID. A wrapper for session_id().
     *
     * @return string Current session ID
     */
    public function getID()
    {
        return session_id();
    }

    /**
     * Runtime shutdown handler. Used to close session.
     */
    public function shutdown()
    {
        // We don't need to register shutdown function if it is an AJAX request,
        // because the AJAX request session manipulations are done in ob_handler.
        // AJAX ob_handlers are lauched AFTER session_close so all session changes by AJAX
        // will be unsaved, so we call session_write_close() directly in our AJAX ob_handler.
        if (defined('AJAX_REQUEST') || !$this->isStarted()) {
            return;
        }

        session_write_close();
    }

    /**
     * @return bool Whether HTTP request contains the session ID.
     */
    public function requestHasSessionID()
    {
        return $this->requestGetSessionID() !== null;
    }

    /**
     * @return string|null Session identifier contained in HTTP request, null if request doesn't contain session ID.
     */
    public function requestGetSessionID()
    {
        return isset($_REQUEST[$this->getName()])
            ? $_REQUEST[$this->getName()]
            : (
                isset($_COOKIE[$this->getName()])
                    ? $_COOKIE[$this->getName()]
                    : null
            );
    }

    /**
     * Sets up session parameters, handlers, storage driver and starts session.
     * If HTTP request doesn't contain session ID, and ID has not been passed as argument, it will be generated.
     *
     * @param string $session_id Session identifier
     *
     * @return bool Whether session has started
     */
    public function start($session_id = null)
    {
        if ($this->isStarted()) {
            return false;
        }

        if ($session_id) {
            $id = $session_id;
        } elseif ($this->requestHasSessionID()) {
            $id = $this->requestGetSessionID();
        } else {
            $id = $this->addSuffixToSessionID(
                $this->generateSessionID()
            );
        }

        $this->setID($id);
        $this->setSessionParams();
        $this->initDataStorageDriver();
        if ($this->storage_driver instanceof ABackend) {
            $this->registerHandlers();
        }

        session_start();

        if ($this->isStarted() && empty($_SESSION) && $this->storage_driver instanceof ABackend) {
            $_SESSION['settings'] = $this->getStoredSessionData($this->getID());
            // For updating the expiry time
            $this->markSettingsAsRequiredToSave();
        }

        // Validate session
        if ($this->needValidate()) {
            $validator_data = self::getValidatorData();

            if (!isset($_SESSION['_validator_data'])) {
                $_SESSION['_validator_data'] = $validator_data;
            } elseif ($_SESSION['_validator_data'] !== $validator_data) {
                session_regenerate_id(true);
                $_SESSION = [];
            }
        }

        if (!empty($request['check_session'])) {
            die(!empty($_SESSION) ? 'OK' : '');
        }

        return $this->isStarted();
    }

    /**
     * Checks whether session needs to be validated
     *
     * @return bool True if session should be validated, false - otherwise
     */
    protected function needValidate()
    {
        return (defined('SESS_VALIDATE_IP') || defined('SESS_VALIDATE_UA')) && !defined('FORCE_SESSION_START');
    }

    /**
     * Get session validation data
     *
     * @return array Validation data
     */
    public static function getValidatorData()
    {
        $data = [];

        if (defined('SESS_VALIDATE_IP')) {
            $ip = fn_get_ip();
            $data['ip'] = $ip['host'];
        }

        if (defined('SESS_VALIDATE_UA')) {
            $data['ua'] = md5($_SERVER['HTTP_USER_AGENT']);
        }

        return $data;
    }

    /**
     * Sets session-related INI-parameters.
     *
     * @return void
     */
    protected function setSessionParams()
    {
        ini_set('session.cookie_lifetime', $this->cookie_lifetime);
        ini_set('session.cookie_domain', $this->cookie_domain);
        ini_set('session.cookie_path', $this->cookie_path);
        ini_set('session.gc_probability', $this->gc_probability);
        ini_set('session.gc_divisor', $this->gc_divisor);

        session_cache_limiter($this->cache_limiter);
        session_cache_expire($this->cache_expire);

        if (PHP_VERSION_ID < 70300) {
            session_set_cookie_params(
                $this->cookie_lifetime,
                $this->cookie_path . '; samesite=' . $this->cookie_samesite,
                $this->cookie_domain,
                $this->cookie_secure,
                $this->cookie_httponly
            );
        } else {
            /** @psalm-suppress InvalidArgument */
            session_set_cookie_params([
                'lifetime' => $this->cookie_lifetime,
                'path'     => $this->cookie_path,
                'domain'   => $this->cookie_domain,
                'secure'   => $this->cookie_secure,
                'httponly' => $this->cookie_httponly,
                'samesite' => $this->cookie_samesite
            ]);
        }
    }

    /**
     * Registers internal methods as a handlers for session data saving mechanism.
     *
     * @return void
     */
    protected function registerHandlers()
    {
        /** @psalm-suppress InvalidScalarArgument */
        session_set_save_handler(
            [$this, 'open'],
            [$this, 'close'],
            [$this, 'read'],
            [$this, 'write'],
            [$this, 'destroy'],
            [$this, 'gc']
        );
    }

    /**
     * Creates an instance of session data storage driver.
     */
    protected function initDataStorageDriver()
    {
        $this->storage_driver = $this->app['session.storage'];
    }

    /**
     * @return \Tygh\Backend\Session\ABackend|null Used session data storage driver or null if session isn't started yet.
     */
    public function getStorageDriver()
    {
        return $this->storage_driver;
    }

    /**
     * Regenerates session ID, preserving session data.
     *
     * @return string New session ID
     */
    public function regenerateID()
    {
        if (!$this->isStarted()) {
            $this->start();
        }

        $old_id = $this->getID();
        $new_id = $this->addSuffixToSessionID($this->generateSessionID());

        session_write_close();

        $this->storage_driver->regenerate($old_id, $new_id);

        /**
         * Actions after regenerate session id
         *
         * @param string $old_id Old session Id
         * @param string $new_id New session Id
         */
        fn_set_hook('session_regenerate_id', $old_id, $new_id);

        $this->setID($new_id);

        session_start();

        $cookie_params = session_get_cookie_params();

        // This code was added for backward compatibility, see @18493.
        // Previously the "www" part was removed from the domain name in the session cookie, and now it's not.
        // This code removes the session cookie of the base domain.
        // That solves a problem with authorization when a browser sends more than one session cookie.
        if (strpos($cookie_params['domain'], '.www') === 0) {
            $domain = preg_replace('/^\.www[0-9]*\./i', '', $cookie_params['domain']);
            setcookie($this->getName(), false, -1, $cookie_params['path'], '.' . $domain, $cookie_params['secure'], $cookie_params['httponly']);
        }

        // Update linked data
        db_query('UPDATE ?:stored_sessions SET session_id = ?s WHERE session_id = ?s', $new_id, $old_id);
        db_query('UPDATE ?:user_session_products SET session_id = ?s WHERE session_id = ?s', $new_id, $old_id);

        return $new_id;
    }

    /**
     * Adds suffix to session ID.
     *
     * @param string $session_id Session ID
     *
     * @return string Session ID with suffix.
     */
    public function addSuffixToSessionID($session_id)
    {
        fn_set_hook('sid', $session_id);

        return $session_id . $this->session_id_suffix;
    }

    /**
     * Re-creates session from scratch or using given session ID.
     *
     * @param string $new_session_id Session ID to use. Defaults to null, meaning new ID will be generated.
     *
     * @return string new session ID
     */
    public function resetID($new_session_id = null)
    {
        if ($new_session_id === $this->getID()) {
            return $new_session_id;
        }

        session_destroy();

        if (empty($new_session_id)) {
            $new_session_id = $this->addSuffixToSessionID($this->generateSessionID());
        }

        $this->setID($new_session_id);

        $this->registerHandlers();

        session_start();

        return $this->getID();
    }

    /**
     * Generates session ID key.
     *
     * @return string New session ID
     */
    protected function generateSessionID()
    {
        return SecurityHelper::generateRandomString();
    }

    /**
     * Method is called on read access to session data.
     *
     * @return void
     */
    protected function onRead()
    {
        if ($this->start_on_read && !$this->isStarted()) {
            $this->start();
        }

        $this->onAccess();
    }

    /**
     * Method is called on write access to session data.
     *
     * @return void
     */
    protected function onWrite()
    {
        if ($this->start_on_write && !$this->isStarted()) {
            $this->start();
        }

        $this->onAccess();
    }

    /**
     * Method is called on read or write access to session data.
     *
     * @return void
     */
    protected function onAccess()
    {
        if (isset($_SESSION)) {
            return;
        }

        // Workaround for third-party code that is accessing Tygh::$app['session']
        // superglobal variable when session haven't started.
        $_SESSION = [];
    }

    /**
     * Session start handler.
     *
     * @param string $save_path Path for session storage
     * @param string $sess_name Session name
     *
     * @return bool Always true
     */
    public function open($save_path, $sess_name)
    {
        return true;
    }

    /**
     * Session close handler.
     *
     * @return bool Always true
     */
    public function close()
    {
        return true;
    }

    /**
     * Session data reading from persistent storage handler.
     *
     * @param string $session_id Session ID
     *
     * @return string Session encoded data
     */
    public function read($session_id)
    {
        $data = $this->storage_driver->read($session_id);
        return $data !== false ? $data : '';
    }

    /**
     * Session data writing to persistent storage handler.
     *
     * @param string $session_id   Session ID
     * @param string $session_data Session data
     *
     * @return bool True if saved, false otherwise
     */
    public function write($session_id, $session_data)
    {
        foreach ($this->writability_handlers as $handler) {
            if (!is_callable($handler)) {
                continue;
            }

            if (!$handler($session_id, $session_data)) {
                return true;
            }
        }

        if ($this->settings_are_save_required && isset($_SESSION['settings'])) {
            $this->saveStoredSessionData($session_id, $_SESSION['settings']);
        }

        return $this->storage_driver->write($session_id, [
            'expiry' => TIME + SESSION_ALIVE_TIME,
            'data'   => $session_data
        ]);
    }

    /**
     * Session data destroy handler.
     *
     * @param string $session_id Session ID
     *
     * @return bool True if destroyed, false otherwise
     */
    public function destroy($session_id)
    {
        return $this->storage_driver->delete($session_id);
    }

    /**
     * Session data garbage collector handler.
     *
     * @param int $max_lifetime Max session data lifetime
     *
     * @return bool Always true
     */
    public function gc($max_lifetime)
    {
        $last_execution_time = (int) fn_get_storage_data('last_session_gc_execution_time', strtotime(sprintf('-%d minutes', $this->gc_periond)));
        $current_time = time();
        $gc_period_in_seconds = $current_time - $last_execution_time;

        if ($current_time < strtotime(sprintf('+%d minutes', $this->gc_periond), $last_execution_time)) {
            return true;
        }

        fn_set_storage_data('last_session_gc_execution_time', (string) $current_time);

        $this->storage_driver->gc($max_lifetime);

        // Cleanup stored sessions
        db_query('DELETE FROM ?:stored_sessions WHERE expiry < ?i', TIME);

        foreach ($this->gc_handlers as $handler) {
            if (!is_callable($handler)) {
                continue;
            }

            $handler($gc_period_in_seconds, $last_execution_time);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        $this->onRead();

        return sizeof($_SESSION);
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): Traversable
    {
        $this->onRead();

        return new \ArrayIterator($_SESSION);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value): void
    {
        $this->onWrite();

        if ($offset === null) {
            $_SESSION[] = $value;
        } else {
            $_SESSION[$offset] = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset): bool
    {
        $this->onRead();

        return isset($_SESSION[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset): void
    {
        $this->onWrite();

        unset($_SESSION[$offset]);
    }

    /**
     * @inheritdoc
     */
    #[\ReturnTypeWillChange]
    public function &offsetGet($offset)
    {
        $this->onRead();

        return $_SESSION[$offset];
    }

    /**
     * Allows to fetch session data as an array.
     *
     * @return array Session data
     */
    public function toArray()
    {
        $this->onRead();

        return $_SESSION;
    }

    /**
     * Allows to set session data from an array
     *
     * @param array $data Session data to set
     *
     * @return void
     */
    public function fromArray(array $data)
    {
        $this->onWrite();

        $_SESSION = $data;
    }

    /**
     * Marks the session settings data as required to save
     *
     * @return void
     */
    public function markSettingsAsRequiredToSave()
    {
        $this->settings_are_save_required = true;
    }

    /**
     * Gets session stored data
     *
     * @param string $session_id Session ID
     *
     * @return array
     */
    private function getStoredSessionData($session_id)
    {
        $data = db_get_field('SELECT data FROM ?:stored_sessions WHERE session_id = ?s', $session_id);

        // Backward compatibility
        if (strpos($data, 'settings|') === 0) {
            $data = substr($data, strlen('settings|'));
        }

        $data = (array) @unserialize($data);

        return $data;
    }

    /**
     * Saves session stored data
     *
     * @param string $session_id Session ID
     * @param array  $data       Data
     *
     * @return void
     */
    private function saveStoredSessionData($session_id, array $data)
    {
        db_replace_into('stored_sessions', [
            'session_id' => $session_id,
            'data'       => serialize($data),
            'expiry'     => TIME + SESSIONS_STORAGE_ALIVE_TIME,
        ]);
    }

    /**
     * Saves session data to persistent storage.
     *
     * @param string       $session_id   Session ID
     * @param string|array $session_data Data to save
     *
     * @return bool True if saved, false otherwise
     */
    public function save($session_id, $session_data)
    {
        $current_session_id = null;
        $current_session = [];

        if ($this->isStarted()) {
            $current_session_id = $this->getID();
            $current_session = $_SESSION;
            $this->shutdown();
        }

        $this->start($session_id);
        $_SESSION = $session_data;

        $this->shutdown();

        if ($current_session_id) {
            $this->start($current_session_id);
            $_SESSION = $current_session;
        }

        return true;
    }

    /**
     * Session data serializer.
     *
     * @param array $data Data to serialize
     *
     * @return string Serialized data
     *
     * @deprecated Since 4.13
     */
    public function encode(array $data)
    {
        $raw = '';
        $keys = array_keys($data);

        foreach ($keys as $key) {
            $value = $data[$key];

            $raw .= $key . '|' . serialize($value);
        }

        return $raw;
    }

    /**
     * Session data unserializer.
     *
     * @param string $string Serialized data
     *
     * @return array Unserialized data
     *
     * @deprecated Since 4.13
     */
    public function decode($string)
    {
        if (!$this->isStarted()) {
            $this->start();
        }

        $current_session = [];

        // Using $_SESSION = array() might cause segfault
        foreach ($_SESSION as $key => $value) {
            $current_session[$key] = $value;
            unset($_SESSION[$key]);
        }

        session_decode($string);

        $data = $_SESSION;
        $_SESSION = $current_session;

        return $data;
    }
}
