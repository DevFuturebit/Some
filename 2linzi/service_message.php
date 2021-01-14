<?php

namespace futurbit;

require_once('sendsay.php');
require_once('../lib/sendsayAction.php');

class sendsayServiceMessage extends sendsayAction {

    public $sub_send_params = Array(
        "action" => "issue.send",
        "label" => "servicemail",
        "letter" => array("draft.id" => "128", "from.name" => "Интернет-магазин 2Линзы", "from.email" => "info@2linzi.ru"),
        "group" => "personal",
        "mute" => "1",
        "sendwhen" => "now",
        "campaign.id" => "1",
        "extra" => array(
            "Key1" => ""
        )
    );


    function action($params)
    {
        return $this->subscribeAction($params);
    }

    public function subscribeAction($data)
    {
        $answer     = array();
        // пробуем оптравить
        $this->sub_subscribe_params['email']               = $data['user_email'];
        $this->sub_subscribe_params['obj']['a177']['q30'] = $data['title'];
        $this->sub_subscribe_params['obj']['a177']['q331'] = date("Y-m-d H:i:s");

        $exit_code=true;
        $counter = 0; // чтобы избежать зацикливания
        while ($exit_code===true && $counter < 10) {
            $exit_code = $this->sub_login();
            $counter++;
        }

        $res = $this->sub_api_execute($this->sub_subscribe_params);
        $ans=json_decode($res, true);


        $this->sub_send_params['email']         = $data['user_email'];
        $this->sub_send_params['extra']['Key1']        = $data['body'];
        $res = $this->sub_api_execute($this->sub_send_params);
        $ans=json_decode($res, true);


        //echo "<pre>";
        //print_r($ans);
        //echo "</pre>";
        $this->sub_subscribe_params['datakey']             = array(array(
            "service_email", "merge", array( date("Y-m-d H:i:s") => array(
                "message_title" => $data['title'],
                "message_body" => stripslashes($data['body'])
            )
            )
        ));
        unset($this->sub_subscribe_params['obj']);
        unset($this->sub_subscribe_params['if_exists']);
        $res = $this->sub_api_execute($this->sub_subscribe_params);
        //$ans=json_decode($res, true);
        //print_r($ans);

        $this->sub_logout();
        $ans=json_decode($res, true);
        return $ans;

    }
}

$action = new sendsayServiceMessage();
// получит ьемаил и инфу
$aaa = $action->action($_REQUEST);//
echo "true";