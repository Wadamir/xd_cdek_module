<?php

/**
 * Main CDEK integration client.
 *
 * This class handles authentication data, endpoint selection,
 * component loading, request execution, and response parsing.
 */
class cdek_integrator
{
    /** Production API base URL. */
    protected $base_url = 'https://integration.cdek.ru/';

    /** API URL used for AJAX requests. */
    protected $ajax_url = 'https://api.cdek.ru/';

    /** Account credentials and shipment date context. */
    protected $account;
    protected $secure_password;
    protected $date;

    /** Logger instance. */
    public $logger;

    /** Internal metadata. */
    private $version = "1.0";
    private static $ext_dir;
    protected $method = '';

    /** Runtime diagnostics for API interaction. */
    public $error;
    public $curl_url;
    public $curl_data;
    public $curl_success;

    const TEST_ACCOUNT = 'wqGwiQx0gg8mLtiEKsUinjVSICCjtTEP';
    const TEST_SECURE_PASSWORD = 'RmAmgvSgSl1yirlz9QupbzOJVqhCxcP5';

    const BASE_TEST_URL = 'https://integration.edu.cdek.ru/';
    const AJAX_TEST_URL = 'https://api.edu.cdek.ru/';


    public function __construct($account = '', $secure_password = '', $date = '')
    {
        if (!empty($account) && !empty($secure_password)) {
            $this->setAuth($account, $secure_password);
        }

        if ($this->isTestingApiKeys($account, $secure_password)) {
            $this->base_url = self::BASE_TEST_URL;
            $this->ajax_url = self::AJAX_TEST_URL;
        }

        if (!$date) {
            $default_timezone = date_default_timezone_get();
            date_default_timezone_set('UTC');
            $date = date('Y-m-d', time() + 10800);
            date_default_timezone_set($default_timezone);
        }

        $this->setDate($date);
        $this->init();
    }


    /**
     * Sets planned shipment date.
     *
     * @param string $date For example: 2014-06-25.
     */
    public function setDate($date)
    {
        $this->date = $date;
    }


    /**
     * Sets account credentials.
     *
     * @param string $account Login.
     * @param string $secure_password Secret key.
     */
    public function setAuth($account, $secure_password)
    {
        $this->account = $account;
        $this->secure_password = $secure_password;
    }


    protected function setLogger($filename)
    {
        $this->logger = new cdek_logger($filename);
    }


    /**
     * Builds secure signature from date and secret.
     *
     * @return string
     */
    protected function getSecure()
    {
        return md5($this->date . '&' . $this->secure_password);
    }


    /**
     * Loads and instantiates a component by class name.
     *
     * @param string $component
     * @return mixed|null
     */
    public function loadComponent($component)
    {
        if (!class_exists($component)) {
            return null;
        }

        return new $component($this->account, $this->secure_password, $this->date);
    }


    /**
     * Sends component data to CDEK API and returns parsed response.
     *
     * @param exchange $component
     * @return mixed
     */
    public function sendData(exchange $component)
    {
        $action = $this->ajax_url . $component->getMethod();
        $parser = ($component instanceof exchange_parser) ? $component->getParser() : new parser_json();

        $response = $this->getURL($action, $parser, $component->getData());
        $this->error = array();

        return ($component instanceof exchange_preparer)
            ? $component->prepareResponse($response, $this->error)
            : $response;
    }


    /**
     * Executes HTTP request and parses the response via parser strategy.
     *
     * @param string $url
     * @param response_parser $parser
     * @param array $data
     * @return mixed
     */
    protected function getURL($url, response_parser $parser, $data = array())
    {
        $info = $this->loadComponent('info');
        $auth_data = $info->getAuthToken();

        $ch = curl_init();
        $this->curl_url = $url;
        $this->curl_data = $data;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Authorization: Bearer ' . (!empty($auth_data['access_token']) ? $auth_data['access_token'] : ""),
                'Content-Type: application/json'
            )
        );

        if (isset($data['delete']) && $data['delete'] == true) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            unset($data['delete']);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $out = curl_exec($ch);
        curl_close($ch);

        $this->curl_success = $out;
        $parser->setData($out);

        return $parser->getData();
    }


    public function getMethod()
    {
        return $this->method;
    }


    /**
     * Initializes autoloader and logger.
     */
    private function init()
    {
        spl_autoload_register(array($this, 'autoloader'));
        spl_autoload_extensions('.php');
        self::$ext_dir = dirname(__FILE__);
        $this->setLogger('cdek_integrator.log');
    }


    /**
     * Checks if provided credentials are test credentials.
     *
     * @param string $account
     * @param string $secure
     * @return bool
     */
    private function isTestingApiKeys($account, $secure)
    {
        return $account == self::TEST_ACCOUNT && $secure == self::TEST_SECURE_PASSWORD;
    }


    public function getTestAccount()
    {
        return self::TEST_ACCOUNT;
    }


    public function getTestSecure()
    {
        return self::TEST_SECURE_PASSWORD;
    }


    /**
     * Autoload classes and interfaces from cdek_integrator folders.
     *
     * @param string $class_name
     * @return void
     */
    static public function autoloader($class_name)
    {
        if (class_exists($class_name)) {
            return;
        }

        $folders = array(
            DIR_SYSTEM . 'library/cdek_integrator/',
            DIR_SYSTEM . 'library/cdek_integrator/components/'
        );

        foreach ($folders as $folder) {
            foreach (array('class', 'interface') as $type) {
                $file_name = $folder . $type . '.' . $class_name . '.php';

                if (file_exists($file_name)) {
                    require_once $file_name;
                    return;
                }
            }
        }
    }
}
