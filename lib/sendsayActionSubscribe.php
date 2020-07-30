<?php

namespace futurbit;

class sendsayActionSubscribe extends sendsayAction
{
    function action($params)
    {
        if($this->checkEmail($params['email'])){
            $this->subscribeAction($params['email']);
        }
    }

    public function subscribeAction($email)
    {
        $answer     = array();

        // пробуем оптравить
        $this->sub_subscribe_params['email']               = $email;//- емейл подписчика
        $this->sub_subscribe_params['obj']['a931']['q742'] = 'subscribe form';
        $this->sub_subscribe_params['obj']['a931']['q332'] = date('Y-m-d H:i');

        $exit_code=true;
        $counter = 0; // чтобы избежать зацикливания
        while ($exit_code===true && $counter < 10) {
            $exit_code = $this->sub_login();
            $counter++;
        }

        $res = $this->sub_api_execute($this->sub_subscribe_params);
        $this->sub_logout();
        //echo $res."<br />";
        $ans=json_decode($res, true);

        //print_r($ans);
        $answer['status'] = 0; // все хорошо
        if (isset($ans['errors'])) $answer['status'] = 1;

        return $answer['status'];
    }

}