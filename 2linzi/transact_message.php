<?php

namespace futurbit;

require_once('sendsay.php');
require_once('../lib/sendsayAction.php');

class sendsayTransactMessage extends sendsayAction {

    public $sub_send_params = Array(
        "action" => "issue.send",
        "letter" => array("draft.id" => "127"),
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
        $this->sub_subscribe_params['email']               = $data['user_email'];
        $this->sub_subscribe_params['obj']['a666']['q290'] = $data['user_id'];
        $this->sub_subscribe_params['obj']['a666']['q307'] = $data['order_id'];
        $this->sub_subscribe_params['obj']['a666']['q424'] = $data['order_status'];
        $this->sub_subscribe_params['obj']['a666']['q477'] = $data['order_sum'];
        $this->sub_subscribe_params['obj']['a666']['q793'] = $data['order_items'];
        $this->sub_subscribe_params['obj']['a666']['q825'] = $data['order_date'];
        $this->sub_subscribe_params['obj']['a666']['q226'] = date("Y-m-d H:i");

        $exit_code=true;
        $counter = 0; // чтобы избежать зацикливания
        while ($exit_code===true && $counter < 10) {
            $exit_code = $this->sub_login();
            $counter++;
        }

        $res = $this->sub_api_execute($this->sub_subscribe_params);
        $ans=json_decode($res, true);


        $this->sub_send_params['users.list']         = $data['user_email'];
        $res = $this->sub_api_execute($this->sub_send_params);
        $ans=json_decode($res, true);


        //echo "<pre>";
        //print_r($ans);
        //echo "</pre>";
        $order_items_result = array();
        $order_items = explode(",", $data['order_items']);
        foreach ($order_items as $order_item) {
            $temp = explode(":", $order_item);
            $order_items_result[ $temp[0] ] = array("count" => $temp[1]);
        }
        $this->sub_subscribe_params['datakey']             = array(array(
            "orders_history", "merge", array( $data['order_id'] => array(
                "order_sum" => $data['order_sum'],
                "order_datetime" => $data['order_date'],
                "order_items" => $order_items_result,
                "order_status" => $data['order_status'],
                "update_datetime" => date("Y-m-d H:i")
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

$action = new sendsayTransactMessage();
// получит ьемаил и инфу
$aaa = $action->action($_REQUEST);//
echo "true";
