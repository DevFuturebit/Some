<?php
namespace futurbit;


class sendsayActionOrderSms extends sendsayAction
{
    public $sub_send_params = Array(
        "action" => "issue.send",
        "letter" => array("from.name" => "lensmaster", "draft.id" => "116"),
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
        $this->sub_subscribe_params['email']               = $data['email']; 				//Емэйл пользователя

        //$this->sub_subscribe_params['member.head.attach']  = $data['phone'];
        $this->sub_subscribe_params['obj']['a860']['q965'] = $data['phone']; 				// «телефон»
        $this->sub_subscribe_params['obj']['a860']['q500'] = $data['order_id'];   //  номер заказа
        $this->sub_subscribe_params['obj']['a860']['q575'] = $data['total'];  // сумма заказа
        $this->sub_subscribe_params['obj']['a860']['q502'] = date("Y-m-d H:i");  // дата импорта в сендсей
        $this->sub_subscribe_params['head_attach'] = array(array(
            "head" => $data['phone'],
            "head_add_type" => "msisdn",
            "newbie.confirm" => 0,
            "head_rule" => array (
                "multi" => "transplant",
                "single"=> "transplant",
                "newbie"=> "attach"
            )
        ));

        $exit_code=true;
        $counter = 0; // чтобы избежать зацикливания
        while ($exit_code===true && $counter < 10) {
            $exit_code = $this->sub_login();
            $counter++;
        }

        $res = $this->sub_api_execute($this->sub_subscribe_params);
        //$ans=json_decode($res, true);

        //новые детачи
        $ids = Array(
            "action" => "member.head.list",
            "addr_type" => "email",
            "email" => $data['email']);
        $res = $this->sub_api_execute($ids);
        $ans=json_decode($res, true);

        if (!empty($ans['list'])) {
            foreach ($ans['list'] as $key => $value) {
                if (!in_array($value['email'], array($data['email'], $data['phone']))) {
                    $this->sub_detach_params['email']            = $value['email'];
                    $res = $this->sub_api_execute($this->sub_detach_params);
                }
            }
        }
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
            'DestinationAddress' => $data['phone'],
            'SourceAddress' => 'Lensmaster',
            'Data' => 'Ваш заказ '.$data['order_id'].' принят, сумма '.$data['total'].'р. Ожидайте звонка менеджера.',
            'Validity' => '0',
        )));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        //echo $server_output.'<br>';
        curl_close ($ch);
        $ans=json_decode($res, true);

        $server_output = json_decode($server_output, true);
        $this->sub_subscribe_params['datakey']             = array(array(
            "sms_sent", "merge", array( date("Y-m-d H:i:s") => array(
                "sms_scenario" => "Подтверждение приема заказа",
                "sms_text" => 'Ваш заказ '.$data['order_id'].' принят, сумма '.$data['total'].'р. Ожидайте звонка менеджера.',
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
        //print_r($ans);
        $answer['status'] = "успешно :-)"; // все хорошо
        if (isset($ans['errors'])) $answer['status'] = "неудачно :'(";
        $this->logPrintLn("Запись в сендсей прошла ".$answer['status']);
        $this->logPrintLn("скрипт завершен: ".date("Y-m-d H:i:s"));
        $this->logPrintLn("--------------------------------------------------------");
        return $answer['status'];
    }

}