<?php
namespace futurbit;

require_once('./lib/sendsay.php');
require_once('./lib/sendsayAction.php');

class sendsay_diagnostika_reminder extends sendsayAction
{
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

        $exit_code=true;
        $counter = 0; // чтобы избежать зацикливания
        while ($exit_code===true && $counter < 10) {
            $exit_code = $this->sub_login();
            $counter++;
        }

        $ids = Array(
            "action" => "member.head.list",
            "addr_type" => "email",
            "email" => trim($data['email']));
        $res = $this->sub_api_execute($ids);
        $ans=json_decode($res, true);
        //echo "<pre>";
        //print_r($ans);
        //echo "</pre>";

        if (!empty($ans['list'])) {
            foreach ($ans['list'] as $key => $value) {
                if ( strpos($value['email'], '@') === false) {
                    // получим текст смс
                    $get_template_request = Array(
                        "action" => "issue.draft.preview",
                        "id" => "252",
                        "email" => trim($value['email']));
                    $res = $this->sub_api_execute($get_template_request);
                    $ans2=json_decode($res, true);
                    // отправим смс через Devino
                    if (!empty($ans2['letter']['message']['sms'])) {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL,"https://integrationapi.net/rest/v2/Sms/Send");
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_setopt($ch, CURLOPT_POSTFIELDS,      http_build_query(array(
                            'Login' => 'Lensmaster',
                            'Password' => 'Bitfuture2020',
                            'DestinationAddress' => trim($value['email']),
                            'SourceAddress' => 'Lensmaster',
                            'Data' => $ans2['letter']['message']['sms'],
                            'Validity' => '0',
                        )));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $server_output = curl_exec($ch);
                        //echo $server_output.'<br>';
                        curl_close ($ch);

                        $server_output = json_decode($server_output, true);

                        // пробуем оптравить
                        $this->sub_subscribe_params['datakey']             = array(array(
                            "sms_sent", "merge", array( date("Y-m-d H:i:s") => array(
                                "sms_scenario" => "Напоминание о записи на диагностику зрения",
                                "sms_text" => $ans2['letter']['message']['sms'],
                                "sms_provider" => "devino",
                                "sms_id" => (isset($server_output['Desc']) ? $server_output['Desc'] : $server_output)
                            )
                            )
                        ));
                        $this->sub_subscribe_params['addr_type']           = 'msisdn';
                        $this->sub_subscribe_params['email']               = trim($value['email']); 				//телефон пользователя
                        if (isset($this->sub_subscribe_params['if_exists'])) {
                            unset($this->sub_subscribe_params['if_exists']);
                        }
                        $res = $this->sub_api_execute($this->sub_subscribe_params);
                    }
                    //echo "<pre>";
                    //print_r($ans2);
                    //echo "</pre>";

                }
            }
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

$test = new sendsay_diagnostika_reminder;


//echo "<pre>";
//print_r($resultarray);
//echo "</pre>";
//die();

$ansver = $test->action($_GET);

// var_dump($ansver);


echo "done!";