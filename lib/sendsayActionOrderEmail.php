<?php

namespace futurbit;

class sendsayActionOrderEmail extends sendsayAction
{

    public $logfilename = 'm_confirm_email.log';

    public $sub_send_params = Array(
        "action" => "issue.send",
        "letter" => array("draft.id" => "383"),
        "group" => "personal",
        "mute" => "1",
        "sendwhen" => "now");

    function action($params)
    {
        $this->toSSLog('sendsayActionOrderEmail :' . print_r(var_export($params, true), true));
        return $this->subscribeAction($params);
    }

    public function subscribeAction($data)
    {
        $answer = array();

        // пробуем оптравить
        $this->sub_subscribe_params['email'] = $data['email'];                //Емэйл пользователя

        $this->sub_subscribe_params['obj']['a641']['q725'] = $data['buyer_id'];                // «buyer_id»
        $this->sub_subscribe_params['obj']['a641']['q144'] = $data['buyer_name'];   //  buyer_name
        $this->sub_subscribe_params['obj']['a641']['q521'] = $data['buyer_surname'];  // buyer_surname
        $this->sub_subscribe_params['obj']['a641']['q159'] = $data['order_id'];  // order_id
        $this->sub_subscribe_params['obj']['a641']['q232'] = $data['external_order_id'];  // external order_id for clients
        $temp0 = explode(' ', $data['order_date']);
        $temp = explode('.', $temp0[0]);
        $this->sub_subscribe_params['obj']['a641']['q117'] = implode('-', array_reverse($temp)) . " " . $temp0[1];                // «Y-m-d H:i:s»
        $this->sub_subscribe_params['obj']['a641']['q254'] = $data['order_status'];   //  order_status
        $this->sub_subscribe_params['obj']['a641']['q335'] = $data['order_sum'];  // order_sum
        $this->sub_subscribe_params['obj']['a641']['q238'] = $data['order_goods'];  // order_goods
        $this->sub_subscribe_params['obj']['a641']['q827'] = $data['delivery_price'];  // delivery_price
        $this->sub_subscribe_params['obj']['a641']['q770'] = date("Y-m-d H:i");  // дата и время отправки письма

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
        /*
        if (extension_loaded('ssh2')) {
            $local_file = 'confirm_email.log';
            $server_file = '/var/www/clients/client1/web3/home/Futurebit1/export_logs/confirm_email.log';
            $connection = ssh2_connect('82.202.225.103', 55567);
            ssh2_auth_password($connection, 'Futurebit1', 'dnui!ZppFGXC8');

            ssh2_scp_recv($connection, $server_file, $local_file);

            $date_to_clear = '***** ' . date('Y-m-d', strtotime('-5 days'));
            $log_content = '
***** ' . date("Y-m-d H:i:s", strtotime('-0 days')) . ' request params ' . urldecode($_SERVER['QUERY_STRING']) . '
';
            $log_content .= date("Y-m-d H:i:s") . ' sendsay params ' . print_r($this->sub_subscribe_params, true) . '
';
            $log_content .= date("Y-m-d H:i:s") . ' sendsay response ' . print_r($ans, true) . '
		
';
            $file_data = $log_content;
            $file_data .= file_get_contents($local_file);
            if (($x_pos = strpos($file_data, $date_to_clear)) !== FALSE) {
                $file_data = substr($file_data, 0, $x_pos);
            }

            file_put_contents($local_file, $file_data);
            ssh2_scp_send($connection, $local_file, $server_file, 0777);
            if (file_exists($local_file)) unlink($local_file);
        } else {
            echo "not loaded ssh2 extension, so we can record log file<br/>";
        }
*/


        $this->sub_send_params['users.list'] = $data['email'];

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