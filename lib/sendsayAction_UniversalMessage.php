<?php

namespace futurbit;


class sendsayAction_UniversalMessage extends sendsayAction
{
    public $sub_send_params = Array(
        "action" => "issue.send",
        "letter" => array("from.name" => "lensmaster", "draft.id" => "505"),
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


        $message_len = mb_strlen($data['message'], 'UTF-8');
        if ($message_len <= 70 ) {
            $sms_count = 1;
        } else {
            $sms_count = ceil ($message_len / 67) ;
        }

        $exit_code=true;
        $answer     = array();
        $counter = 0; // чтобы избежать зацикливания
        while ($exit_code===true && $counter < 10) {
            $exit_code = $this->sub_login();
            $counter++;
        }


        if (!empty($data['email']) && !empty($data['phone'])) { // Если передана пара контактов (телефон и емэйл)
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

            $ids = Array(
                "action" => "member.head.list",
                "addr_type" => "msisdn",
                "email" => $data['phone']);
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
            // пробуем оптравить
            $this->sub_subscribe_params['email']               = $data['email']; 				//Емэйл пользователя
            $this->sub_subscribe_params['obj']['a70']['q762'] = str_replace('\"', '"', $data['message']);				// текстовое сообщение, которое мы передаем (в сендсей поставил ограничение на 2000 знаков, но можем и еще сократить)
            $this->sub_subscribe_params['obj']['a70']['q145'] = str_replace('\"', '"', $data['title']);   //  тема сообщения (не более 500 знаков)
            $this->sub_subscribe_params['obj']['a70']['q906'] = $sms_count;  // кол-во СМС под сообщение
            $this->sub_subscribe_params['obj']['a70']['q83'] = date("Y-m-d H:i");  // дата импорта в сендсей
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

            $res = $this->sub_api_execute($this->sub_subscribe_params);
            //$ans=json_decode($res, true);
        } elseif (!empty($data['email']) || !empty($data['phone'])) { // Если передан только один контакт (телефон или емэйл)
            $email = false;
            if (!empty($data['email'])) {
                $email = true;
                $ids = Array(
                    "action" => "member.head.list",
                    "addr_type" => "email",
                    "email" => $data['email']);
                $res = $this->sub_api_execute($ids);
                $ans=json_decode($res, true);
                if (!empty($ans['list'])) {
                    foreach ($ans['list'] as $key => $value) {
                        if ( strpos($value['email'], '@') === false) {
                            $data['phone']           = $value['email'];
                            break;
                        }
                    }
                    if (!isset($data['phone'])) {
                        $data['phone'] = '';
                    }
                    foreach ($ans['list'] as $key => $value) {
                        if (!in_array($value['email'], array($data['email'], $data['phone']))) {
                            $this->sub_detach_params['email']            = $value['email'];
                            $res = $this->sub_api_execute($this->sub_detach_params);
                        }
                    }
                    if ($data['phone'] == '') {
                        unset($data['phone']);
                    }                    
                }
            } else {
                $ids = Array(
                    "action" => "member.head.list",
                    "addr_type" => "msisdn",
                    "email" => $data['phone']);
                $res = $this->sub_api_execute($ids);
                $ans=json_decode($res, true);
                if (!empty($ans['list'])) {
                    foreach ($ans['list'] as $key => $value) {
                        if ( strpos($value['email'], '@') !== false) {
                            $data['email']            = $value['email'];
                            break;
                        }
                    }
                    if (!isset($data['email'])) {
                        $data['email'] = '';
                    }
                    foreach ($ans['list'] as $key => $value) {
                        if (!in_array($value['email'], array($data['email'], $data['phone']))) {
                            $this->sub_detach_params['email']            = $value['email'];
                            $res = $this->sub_api_execute($this->sub_detach_params);
                        }
                    }
                    if ($data['email'] == '') {
                        unset($data['email']);
                    }                    
                }
            }
            // пробуем оптравить
            if ($email) {
                $this->sub_subscribe_params['email']               = $data['email']; 				//Емэйл пользователя
            } else { // телефон
                $this->sub_subscribe_params['email']               = $data['phone']; 				//Телефон пользователя
                $this->sub_subscribe_params['addr_type']               = 'msisdn'; 				// тип идентификатора
            }
            $this->sub_subscribe_params['obj']['a70']['q762'] = str_replace('\"', '"', $data['message']);				// текстовое сообщение, которое мы передаем (в сендсей поставил ограничение на 2000 знаков, но можем и еще сократить)
            $this->sub_subscribe_params['obj']['a70']['q145'] = str_replace('\"', '"', $data['title']);   //  тема сообщения (не более 500 знаков)
            $this->sub_subscribe_params['obj']['a70']['q906'] = $sms_count;  // кол-во СМС под сообщение
            $this->sub_subscribe_params['obj']['a70']['q83'] = date("Y-m-d H:i");  // дата импорта в сендсей

            $res = $this->sub_api_execute($this->sub_subscribe_params);
            //$ans=json_decode($res, true);
        }  else { // ничего нет
            $this->logPrintLn("Отсутсвует и email и телефон");
            echo "Отсутсвует и email и телефон";
            die();
        }




        if (!empty($data['email'])) {
            $this->sub_send_params['users.list'] = $data['email'];
            $this->sub_send_params['letter']     = array("draft.id" => "506");
            $res = $this->sub_api_execute($this->sub_send_params);
            $ans=json_decode($res, true);
        } elseif (!empty($data['phone']) && $sms_count <= 3)  {
            $this->sub_send_params['users.list']         = $data['phone'];
            $res = $this->sub_api_execute($this->sub_send_params);
            $ans=json_decode($res, true);
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
