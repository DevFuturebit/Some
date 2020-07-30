<?php


namespace futurbit;


class sendsayAction extends sendsay
{

    /**
     * Единый метод вызова action
     * @array $params
     * @return bool
     */
    function action($params){
        return true;
    }

    public function checkEmail($email){
        //это битриксовая функция проверки емайла,
        // но можно на свое тут заменить, аля filter_var("aaa@bbb.com", FILTER_VALIDATE_EMAIL)
        return check_email($email);
    }

    public function toSSLog($msg,$level=\Monolog\Logger::INFO){
        global $FB_site_logger;
        if($FB_site_logger instanceof \Monolog\Logger){
            $FB_site_logger->addRecord($level,$msg);
        }
    }

}