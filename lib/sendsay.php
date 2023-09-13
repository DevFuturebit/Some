<?php

namespace futurbit;

class sendsay
{
    private $enableLog = true;
    public $logfilename = 'log.log';
    /* @var $logger \Monolog\Logger */
    public $logger;
    const LOG_PATH = '/var/www/clients/client1/web3/home/Futurebit1/export_logs/';

    private $sub_post_fields = Array(
        "apiversion" => 100,
        "json" => 1,
        "request.id" => 0
    );

    private $sub_auth_fields = Array(

        "action" => "login"

    , "login" => "log" //

    , "sublogin" => "sublog" //

    , "passwd" => "psw" //

    );

    public $sub_subscribe_params = Array(
        "action" => "member.set",
        "addr_type" => "email",
        "if_exists" => "overwrite",
        "newbie.confirm" => 0);

    public $sub_attach_params = Array(
        "action" => "member.head.attach",
        "addr_type" => "email",
        /*"head_addr_type" => "msisdn"*/);

    public $sub_detach_params = Array(
        "action" => "member.head.detach",
        //"addr_type" => "email",
    );

    private $sub_ch;
    private $sub_redirect;
    private $sub_session = '';
    private $sub_debug = false;
    private $sub_dontsend;
    private $sub_log;
    private $sub_js_request;
    private $sub_curl_url;

    /**
     * sendsay constructor.
     */
    public function __construct()
    {
//        $this->changeLogPath();
        $this->initMonolog();
    }

    public function initMonolog()
    {
        if (class_exists('\Monolog\Logger')) {
            $className = get_called_class();
            $this->logger = new \Monolog\Logger($className);
            $filename = static::LOG_PATH . $this->logfilename;
            $handler = new \Monolog\Handler\StreamHandler($filename, \Monolog\Logger::DEBUG);
            $formatter = new \Monolog\Formatter\LineFormatter(null, null, false, true);
            $handler->setFormatter($formatter);
            $this->logger->pushHandler($handler);
        }
    }

    public function add2log($msg)
    {
        if ($this->logger) {
            $this->logger->info($msg);
        }
    }

#--------------------------------------------------------------------------------
    public function sub_login()
    {
        $err = false;

        $this->sub_ch = curl_init();
        $response = $this->sub_api_execute($this->sub_auth_fields);

        //if ($debug) echo 'Auth: '.$response;
        $resp = json_decode($response);

        if (!empty($resp->REDIRECT)) {
            $this->sub_redirect .= $resp->REDIRECT;
            //echo 'Red: '.$redirect."<br />";
            $err = true;
        } else $this->sub_session = $resp->session;
        return $err;
    }

#--------------------------------------------------------------------------------
    public function sub_api_execute($request_fields)
    {
        $this->sub_curl_url = 'https://api.sendsay.ru/general/api/v100/json/lensmaster/';
        $request_fields['session'] = $this->sub_session;
        $postdata = $this->sub_generate_postdata($request_fields);

        curl_setopt_array($this->sub_ch, array(
            CURLOPT_URL => $this->sub_curl_url . $this->sub_redirect,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => $postdata
        ));
        //echo "POST:<br /><br />".$postdata."<br />";
        if (!$this->sub_dontsend) $response_str = curl_exec($this->sub_ch);
        if ($this->sub_debug) $this->sub_log .= "JSON response:\r\n" . $response_str . "\r\n\r\n";
        return $response_str;
    }

#--------------------------------------------------------------------------------
    private function sub_generate_postdata($request_fields)
    {
        $this->sub_post_fields['request'] = json_encode($request_fields);
        if ($this->sub_debug) $this->sub_log .= "JSON request:\r\n" . $this->sub_post_fields['request'] . "\r\n";
        $this->sub_js_request = $this->sub_post_fields['request'];
        $this->sub_post_fields['request.id'] = rand();
        $postdata = http_build_query($this->sub_post_fields);
        return $postdata;
    }

#--------------------------------------------------------------------------------
    public function sub_logout()
    {
        $request = Array(
            'action' => 'logout',
            'session' => $this->sub_session);
        curl_close($this->sub_ch);
        return $this->sub_log;
    }

#--------------------------------------------------------------------------------

    /*logging support*/
    public function changeLogPath()
    {
        return false;
    }

    public function enableLog()
    {
        return false;
    }

    public function disableLog()
    {
        return false;
    }

    public function resetLog()
    {
        return false;
    }

    /* add to log raw*/
    public function log($msg)
    {
        if ($this->enableLog) {
            $this->add2log($msg);
        }
    }

    public function logPrint($msg)
    {
        $this->log($msg);
    }

    public function logPrintLn($msg)
    {
        $this->log($msg . "\n");
    }

}
