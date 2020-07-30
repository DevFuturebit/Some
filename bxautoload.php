<?php
// Автозагрузка классов

$futurbitClassDir = __DIR__.'/lib/';

$futurbitClassMap=[
    '\futurbit\sendsay' => $futurbitClassDir.'sendsay.php',
    '\futurbit\sendsayAction' => $futurbitClassDir.'sendsayAction.php',
    '\futurbit\sendsayActionSubscribe' => $futurbitClassDir.'sendsayActionSubscribe.php',
    '\futurbit\sendsayActionOrderSms' => $futurbitClassDir.'sendsayActionOrderSms.php',
    '\futurbit\sendsayActionRecord' => $futurbitClassDir.'sendsayActionRecord.php',
    '\futurbit\sendsayActionDelete' => $futurbitClassDir.'sendsayActionDelete.php',
    '\futurbit\sendsayActionRecord_PhoneCheck' => $futurbitClassDir.'sendsayActionRecord_PhoneCheck.php',
    '\futurbit\sendsayActionAuthorization_PhoneCheck' => $futurbitClassDir.'sendsayActionAuthorization_PhoneCheck.php',
    '\futurbit\sendsayActionRegistration_PhoneCheck' => $futurbitClassDir.'sendsayActionRegistration_PhoneCheck.php',
    '\futurbit\sendsayActionOrderEmail' => $futurbitClassDir.'sendsayActionOrderEmail.php'
];

// привет от битрикса, в автозагрузку классы добавляет, но корректно не подключает
// нет пока времени разбираться в чем дело, пока напрямую подключаем

//\CModule::AddAutoloadClasses(null,$futurbitClassMap);

foreach ($futurbitClassMap as $classfile){
    include_once $classfile;
}

define('FB_SS_LOGFILE',$_SERVER["DOCUMENT_ROOT"] . '/upload/logs/external/futurbit/logN.txt');
if(!defined('FB_LOGFILE_DIR')) {
    define('FB_LOGFILE_DIR', $_SERVER["DOCUMENT_ROOT"] . '/upload/logs/external/futurbit/');
}

//логирование со стороны сайта
if(class_exists('\Monolog\Logger')) {
    global $FB_site_logger;
    $FB_site_logger = new \Monolog\Logger('FB_SS_logger');
    $handler = new \Monolog\Handler\StreamHandler(FB_SS_LOGFILE,\Monolog\Logger::DEBUG );
    $formatter = new \Monolog\Formatter\LineFormatter(null, null, false, true);
    $handler->setFormatter($formatter);
    $FB_site_logger->pushHandler($handler);
}