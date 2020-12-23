<?php
// $_SERVER["DOCUMENT_ROOT"] = dirname(__FILE__)."/../../../../";
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

ignore_user_abort(true);
set_time_limit(0);
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

class sendsayaddsubscriber
{
    private $sub_post_fields = Array(
        "apiversion"=>100,
        "json"=>1,
        "request.id"=>0
    );

    private $sub_auth_fields = Array(

        "action" => "login"

    ,"login"  => "lensmaster"

    ,"sublogin" => "lensmaster"

    ,"passwd" => "boo5Kul"

    );

    private $sub_subscribe_params = Array(
        "action" => "member.set",
        "addr_type" => "msisdn",
        "if_exists" => "overwrite",
        "newbie.confirm" => 0);

    public $sub_send_params = Array(
        "action" => "issue.send",
        "letter" => array("from.name" => "lensmaster", "draft.id" => "537"),
        "group" => "personal",
        "mute" => "1",
        "sendwhen" => "now");

    private $sub_ch;
    private $sub_redirect;
    private $sub_session = '';
    private $sub_debug = false;
    private $sub_dontsend;
    private $sub_log;
    private $sub_js_request;
    private $sub_curl_url;
    public	$file = null;

#--------------------------------------------------------------------------------
    private function sub_login() {
        $err = false;

        $this->sub_ch = curl_init();
        $response = $this->sub_api_execute ($this->sub_auth_fields);

        //if ($debug) echo 'Auth: '.$response;
        $resp = json_decode($response);

        if (!empty($resp->REDIRECT)) {
            $this->sub_redirect.=$resp->REDIRECT;
            //echo 'Red: '.$redirect."<br />";
            $err = true;
        } else $this->sub_session = $resp->session;
        return $err;
    }
#--------------------------------------------------------------------------------
    private function sub_api_execute ($request_fields) {
        $this->sub_curl_url = 'https://api.sendsay.ru/general/api/v100/json/lensmaster/';
        $request_fields['session'] = $this->sub_session;
        $postdata = $this->sub_generate_postdata($request_fields);

        curl_setopt_array($this->sub_ch, array(
            CURLOPT_URL => $this->sub_curl_url.$this->sub_redirect,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_POSTFIELDS => $postdata
        ));
        //echo "POST:<br /><br />".$postdata."<br />";
        if (!$this->sub_dontsend) $response_str=curl_exec($this->sub_ch);
        if ($this->sub_debug) $this->sub_log.="JSON response:\r\n".$response_str."\r\n\r\n";
        return $response_str;
    }
#--------------------------------------------------------------------------------
    private function sub_generate_postdata($request_fields) {
        $this->sub_post_fields['request']=json_encode($request_fields);
        if ($this->sub_debug) $this->sub_log.= "JSON request:\r\n".$this->sub_post_fields['request']."\r\n";
        $this->sub_js_request=$this->sub_post_fields['request'];
        $this->sub_post_fields['request.id']=rand();
        $postdata=http_build_query($this->sub_post_fields);
        return $postdata;
    }
#--------------------------------------------------------------------------------
    private function sub_logout() {
        $request=Array(
            'action'=>'logout',
            'session'=>$this->sub_session);
        curl_close ($this->sub_ch);
        return $this->sub_log;
    }

#--------------------------------------------------------------------------------
    public function subscribeAction(&$data)
    {

        // пробуем оптравить
        $this->sub_subscribe_params['email']               = trim($data['phone']); 				//Телефон пользователя
        $this->sub_subscribe_params['addr_type']               = 'msisdn'; 				// тип идентификатора
        $this->sub_subscribe_params['obj']['a719']['q839'] = trim($data['smsbody']); 				// текст сообщения для СМС
        $this->sub_subscribe_params['obj']['a719']['q864'] = date("Y-m-d H:i");   //  датувремя вызова


        $exit_code=true;
        $counter = 0; // чтобы избежать зацикливания
        while ($exit_code===true && $counter < 10) {
            $exit_code = $this->sub_login();
            $counter++;
        }

        $res = $this->sub_api_execute($this->sub_subscribe_params);
        //$ans=json_decode($res, true);

        /* времено отключаем на время тестирования передача СМС через Devino
        $this->sub_send_params['users.list']         = $data['phone'];

        $res = $this->sub_api_execute($this->sub_send_params);
        */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://integrationapi.net/rest/v2/Sms/Send");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS,      http_build_query(array(
            'Login' => 'Lensmaster',
            'Password' => 'Bitfuture2020',
            'DestinationAddress' => trim($data['phone']),
            'SourceAddress' => 'Lensmaster',
            'Data' => trim($data['smsbody']),
            'Validity' => '0',
        )));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        //echo $server_output.'<br>';
        curl_close ($ch);
        $ans=json_decode($res, true);

      // echo "<pre>";
//print_r($ans);
//echo "</pre>";
//die();
        $server_output = json_decode($server_output, true);
        $this->sub_subscribe_params['datakey']             = array(array(
            "sms_sent", "merge", array( date("Y-m-d H:i:s") => array(
                "sms_scenario" => "СМС при недозвоне из колл-центра",
                "sms_text" => trim($data['smsbody']),
                "sms_provider" => "devino",
                "sms_id" => (isset($server_output['Desc']) ? $server_output['Desc'] : $server_output)
            )
            )
        ));
        unset($this->sub_subscribe_params['obj']);
        unset($this->sub_subscribe_params['if_exists']);
        $res = $this->sub_api_execute($this->sub_subscribe_params);
        //$ans=json_decode($res, true);
        //print_r($ans);

        $this->sub_logout();
        //echo $res."<br />";
        $ans=json_decode($res, true);


        $answer['status'] = 0;
        return $answer['status'];
    }
}

if(!$USER->IsAdmin()){echo 'error!'; exit;}

$test = new sendsayaddsubscriber;


//echo "<pre>";
//print_r($resultarray);
//echo "</pre>";
//die();

$ansver = $test->subscribeAction($_GET);

// var_dump($ansver);


echo "done!";
?>
