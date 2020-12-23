<?php


namespace futurbit;

require_once("functions.php");

class sendsayActionDelete extends sendsayAction {

    public $sub_send_params = Array(
        "action" => "issue.send",
        "letter" => array("draft.id" => "290"),
        "group" => "personal",
        "mute" => "1",
        "sendwhen" => "now");

    public $sub_detach_params = Array(
        "action" => "member.head.detach",
    );

    public $sub_sms_send_params = Array(
        "action" => "issue.send",
        "letter" => array("from.name" => "lensmaster", "draft.id" => "287"),
        "group" => "personal",
        "mute" => "1",
        "sendwhen" => "now");

    function action($params)
    {
        return $this->subscribeAction($params);
    }

    public function subscribeAction($data)
    {
        $answer     = array();

        // пробуем оптравить
        $this->sub_subscribe_params['email']               = $data['email'];
        //$this->sub_subscribe_params['cellphone']           = $data['phone'];
        $this->sub_subscribe_params['obj']['a378']['q854'] = $data['record_id']; // Рекорд_айди
        $this->sub_subscribe_params['obj']['a378']['q852'] = date("Y-m-d H:i"); // Дата отмены заявки (дата вызова скрипта)
        $temp0 = explode(' ', $data['record_date']);
        $temp = explode('.', $temp0[0]);
        $this->sub_subscribe_params['obj']['a378']['q959'] = implode('-', array_reverse($temp))." ".$temp0[1];
        $this->sub_subscribe_params['obj']['a378']['q374'] = $data['salon'];
        $this->sub_subscribe_params['obj']['a378']['q957'] = $data['salon_adres'];

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
        // удаляем уже пирвязанные телефоны от емаила
        $ids = Array(
            "action" => "member.head.list",
            "addr_type" => "email",
            "email" => $data['email']);
        $res = $this->sub_api_execute($ids);
        $ans=json_decode($res, true);
        if (!empty($ans['list'])) {
            foreach ($ans['list'] as $key => $value) {
                if ($value['email'] != $data['email']) {
                    $this->sub_detach_params['email']            = $value['email'];
                    $res = $this->sub_api_execute($this->sub_detach_params);
                }
            }
        }

        $res = $this->sub_api_execute($this->sub_subscribe_params);
        $ans=json_decode($res, true);
        
        $this->sub_send_params['users.list']         = $data['email'];
        $res = $this->sub_api_execute($this->sub_send_params);
        $ans=json_decode($res, true);

        /* времено отключаем на время тестирования передача СМС через Devino
        $this->sub_sms_send_params['email']         = $data['phone'];
        $res = $this->sub_api_execute($this->sub_sms_send_params);
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
            'Data' => 'Запись на проверку зрения номер '.$data['record_id'].' на '.$this->sub_subscribe_params['obj']['a378']['q959'].' отменена',
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
                "sms_scenario" => "Уведомление об отмене записи на диагностику зрения",
                "sms_text" => 'Запись на проверку зрения номер '.$data['record_id'].' на '.$this->sub_subscribe_params['obj']['a378']['q959'].' отменена',
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

        return $ans;

    }
}
