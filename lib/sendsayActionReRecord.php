<?php

namespace futurbit;

class sendsayActionReRecord extends sendsayAction
{

    public $sub_send_params = Array(
        "action" => "issue.send",
        "letter" => array("draft.id" => "484"),
        "group" => "personal",
        "mute" => "1",
        "sendwhen" => "now");

    function action($params)
    {
        return $this->subscribeAction($params);
    }

    public function subscribeAction($data)
    {
        $answer = array();

        // пробуем оптравить
        $this->sub_subscribe_params['email'] = $data['salonmail'];                //Емэйл салона
        $this->sub_subscribe_params['obj']['a346']['q290'] = $data['record_id'];                // «record_id»
        $this->sub_subscribe_params['obj']['a346']['q754'] = $data['client_phone'];  // client_phone
        $temp0 = explode(' ', $data['record_datetime']);
        $temp = explode('.', $temp0[0]);
        $this->sub_subscribe_params['obj']['a346']['q804'] = implode('-', array_reverse($temp)) . " " . $temp0[1];                // «Y-m-d H:i:s»
        $this->sub_subscribe_params['obj']['a346']['q976'] = date("Y-m-d H:i");  // дата и время исполнения скрипта

        $exit_code = true;
        $counter = 0; // чтобы избежать зацикливания
        while ($exit_code === true && $counter < 10) {
            $exit_code = $this->sub_login();
            $counter++;
        }

        $res = $this->sub_api_execute($this->sub_subscribe_params);
        $ans = json_decode($res, true);

        if($this->logger){
            $this->logger->info('Request params ' . urldecode($_SERVER['QUERY_STRING']));
            $this->logger->info('Sendsay params ' . print_r($this->sub_subscribe_params, true) );
            $this->logger->info('Sendsay response ' . print_r($ans, true) );
        }

        $this->sub_send_params['users.list'] = $data['salonmail'];

        $res = $this->sub_api_execute($this->sub_send_params);
        $ans = json_decode($res, true);


        $this->sub_logout();
        //echo $res."<br />";
        $ans = json_decode($res, true);
        //print_r($ans);
        $answer['status'] = "успешно :-)"; // все хорошо
        if (isset($ans['errors'])) $answer['status'] = "неудачно :'(";
        return $answer['status'];
    }

}