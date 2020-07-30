<?php

//Отправка СМС клиенту Линзмастер при готовности заказа
namespace futurbit;

require_once('sendsay.php');
require_once('sendsayAction.php');

class sendsayActionOrder_ReadySms extends sendsayAction
{
    public $sub_send_params = Array(
        "action" => "issue.send",
        "letter" => array("from.name" => "lensmaster", "draft.id" => "503"),
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

        // пробуем оптравить
        $this->sub_subscribe_params['email']               = $data['phone']; 				//Телефон пользователя
        $this->sub_subscribe_params['addr_type']               = 'msisdn'; 				// тип идентификатора
        //$this->sub_subscribe_params['member.head.attach']  = $data['phone'];
        $this->sub_subscribe_params['obj']['a860']['q965'] = $data['phone']; 				// «телефон»
        //$this->sub_subscribe_params['obj']['a860']['q500'] = $data['order_id'];   //  номер заказа
        $this->sub_subscribe_params['obj']['a860']['q405'] = str_replace('\"', '"', $data['adress']);   //  адрес заказа
        $this->sub_subscribe_params['obj']['a860']['q308'] = $data['total'];  // сумма заказа
        $this->sub_subscribe_params['obj']['a860']['q971'] = date("Y-m-d H:i");  // дата импорта в сендсей

        $exit_code=true;
        $counter = 0; // чтобы избежать зацикливания
        while ($exit_code===true && $counter < 10) {
            $exit_code = $this->sub_login();
            $counter++;
        }

        $res = $this->sub_api_execute($this->sub_subscribe_params);
        //$ans=json_decode($res, true);


        $this->sub_send_params['users.list']         = $data['phone'];

        $res = $this->sub_api_execute($this->sub_send_params);
        $ans=json_decode($res, true);


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
$secret = 'CGqS1RkazyZW5Ow6baV0afU7xopmSDNB';
$headers = apache_request_headers();
if (isset($headers['X-KEY']) && $headers['X-KEY'] == $secret) {
    if (empty($_REQUEST['adress']) || !isset($_REQUEST['total'])) {
        echo "не указано обязательное поле";
    } else {
        $action = new sendsayActionOrder_ReadySms();
        $aaa = $action->action($_REQUEST);//

        echo "true";
    }
} else {
    die("access denied!");
}
