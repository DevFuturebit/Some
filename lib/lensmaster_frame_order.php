<?php

//письмо с подтверждением заказа медицинских оправ
namespace futurbit;

require_once('sendsay.php');
require_once('sendsayAction.php');

class sendsayActionFrameOrder extends sendsayAction
{
    public $logfilename = 'm_frame_order.log';

    public $sub_send_params = Array(
        "action" => "issue.send",
        "letter" => array("draft.id" => "547"),
        "group" => "personal",
        "mute" => "1",
        "sendwhen" => "now");

    function action($params)
    {
        return $this->subscribeAction($params);
    }

    public function subscribeAction($data)
    {
        $this->logPrintLn("Старт скрипта: ".date("Y-m-d H:i:s"));
        $results = print_r($data, true);
        $this->logPrintLn("Входные параметры: ".$results);

        $answer     = array();

        // пробуем отправить
        $this->sub_subscribe_params['email']               = $data['email']; 				//email пользователя
        $this->sub_subscribe_params['obj']['-group']['pl79796']='1';
        //$this->sub_subscribe_params['obj']['a860']['q971'] = date("Y-m-d H:i");  // дата импорта в сендсей

        $exit_code=true;
        $counter = 0; // чтобы избежать зацикливания
        while ($exit_code===true && $counter < 10) {
            $exit_code = $this->sub_login();
            $counter++;
        }

        $res = $this->sub_api_execute($this->sub_subscribe_params);

        $this->sub_subscribe_params['datakey']             = array(array(
            "order_confirmed", "set", $data['json']
        ));
        unset($this->sub_subscribe_params['obj']);
        unset($this->sub_subscribe_params['if_exists']);
        $res = $this->sub_api_execute($this->sub_subscribe_params);
        //$ans=json_decode($res, true);
        //print_r($ans);
        $this->sub_send_params['users.list'] = $data['email'];

        $res = $this->sub_api_execute($this->sub_send_params);
        $ans = json_decode($res, true);

        // получим текст емаил
        $get_template_request = Array(
            "action" => "issue.draft.preview",
            "id" => "547",
            "email" => trim($data['email']));
        $res = $this->sub_api_execute($get_template_request);
        $ans2=json_decode($res, true);
        if (!empty($ans2['letter']['message']['html'])) {
            unset($this->sub_send_params['users.list']);
            unset($this->sub_send_params['letter']);
            $this->sub_send_params['email'] = "internetshop@lensmaster.ru"; //
            $this->sub_send_params['letter'] = array(
                "from.email" => "info-ishop@lensmaster.ru",
                "subject" => "Клиент: ".$data['email'].". Заказ № ".$data['json']['_id']."  ".(!empty($data['json']['order_status']) ? $data['json']['order_status'] : ""),
                "from.name" => "Линзмастер",
                "message" => array(
                    "html" => $ans2['letter']['message']['html']
                ));//
            $res = $this->sub_api_execute($this->sub_send_params);
            $ans = json_decode($res, true);
        }

        $this->sub_logout();
        //echo $res."<br />";
        $ans=json_decode($res, true);
        //print_r($ans);
        $answer['status'] = "успешно :-)"; // все хорошо
        if (isset($ans['errors'])) $answer['status'] = "неудачно :'(";
        $this->logPrintLn("Запись в сендсей прошла ".$answer['status']);
        $this->logPrintLn("скрипт завершен: ".date("Y-m-d H:i:s"));
        $this->logPrintLn("--------------------------------------------------------");
        return $answer['status'];
    }

}

if( !function_exists('apache_request_headers') ) {
    function apache_request_headers() {
        $arh = array();
        $rx_http = '/\AHTTP_/';
        foreach($_SERVER as $key => $val) {
            if( preg_match($rx_http, $key) ) {
                $arh_key = preg_replace($rx_http, '', $key);
                $rx_matches = array();
                // do some nasty string manipulations to restore the original letter case
                // this should work in most cases
                $rx_matches = explode('_', $arh_key);
                if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                    foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                    $arh_key = implode('-', $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }
        return( $arh );
    }
}
$secret = 'hjkahs9sd7s89dsdjlsjd89sdu';
$headers = array_change_key_case(apache_request_headers(), CASE_LOWER);
$postData = file_get_contents('php://input');
$xml = simplexml_load_string($postData);
$json = json_encode($xml);
$array = json_decode($json,TRUE);
//echo "<pre>";
//print_r($array);
//echo "</pre>";die();
foreach ($array as $k => $attr) {
    if ($k == '@attributes') {
        foreach ($attr as $k2 => $v2) {
            $array["_".$k2] = $v2;
        }
        unset($array[$k]);
    }
    if ($k == 'items') {
        $array[$k] = $array[$k]['item'];
        if (isset($array[$k]['@attributes'])) {
            $array[$k] = array($array[$k]);
        }
        foreach ($array[$k] as $k2 => $v2) {
            foreach ($v2 as $k3 => $v3) {
                if ($k3 == '@attributes') {
                    foreach ($v3 as $k4 => $v4) {
                        $array[$k][$k2]["_".$k4] = $v4;
                    }
                    unset($array[$k][$k2][$k3]);
                }
                if ($k3 == 'options') {
                    $array[$k][$k2][$k3] = $array[$k][$k2][$k3]['option'];
                    if (isset($array[$k][$k2][$k3]['name'])) {
                        $array[$k][$k2][$k3] = array($array[$k][$k2][$k3]);
                    }
                    foreach ($array[$k][$k2][$k3] as $k4 => $v4) {
                        foreach ($v4 as $k5 => $v5) {
                            if ($k5 == '@attributes') {
                                foreach ($v5 as $k6 => $v6) {
                                    $array[$k][$k2][$k3][$k4]["_".$k6] = $v6;
                                }
                                unset($array[$k][$k2][$k3][$k4][$k5]);
                            }
                        }
                    }
                }
            }

        }
    }
}
$json = str_replace('\/','/', json_encode($array, JSON_UNESCAPED_UNICODE));
if (isset($headers['x-key']) && $headers['x-key'] == $secret) {
    if (empty($headers['email'])) {
        echo "не указано обязательное поле";
    } else {
        $action = new sendsayActionFrameOrder();
        $aaa = $action->action(array('email' => $headers['email'], 'json' => $array));//

        echo "true";
    }
} else {
    die("access denied!");
}
