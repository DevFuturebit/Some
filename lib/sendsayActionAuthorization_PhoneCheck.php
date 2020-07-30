<?php
namespace futurbit;


class sendsayActionAuthorization_PhoneCheck extends sendsayAction
{
    public $sub_send_params = Array(
        "action" => "issue.send",
        "letter" => array("from.name" => "lensmaster", "draft.id" => "423"),
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
        $this->sub_subscribe_params['addr_type']           = 'msisdn';
        $this->sub_subscribe_params['email']               = $data['phone']; 				//Емэйл пользователя
        $this->sub_subscribe_params['obj']['a848']['q100'] = $data['checkcode'];
        $this->sub_subscribe_params['obj']['a848']['q963'] = date("Y-m-d H:i");


        $exit_code=true;
        $counter = 0; // чтобы избежать зацикливания
        while ($exit_code===true && $counter < 10) {
            $exit_code = $this->sub_login();
            $counter++;
        }

        $res = $this->sub_api_execute($this->sub_subscribe_params);
        //$ans=json_decode($res, true);

        $ids = Array(
            "action" => "member.head.list",
            "addr_type" => "msisdn",
            "email" => $data['phone']);
        $res = $this->sub_api_execute($ids);
        $ans=json_decode($res, true);
        if (!empty($ans['list'])) {
            foreach ($ans['list'] as $key => $value) {
                if ( strpos($value['email'], '@') !== false) {
                    $this->sub_detach_params['email']            = $value['email'];
                    $res = $this->sub_api_execute($this->sub_detach_params);
                }
            }
        }

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
