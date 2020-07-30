# Lensmaster
Копия обработчиков для Lensmaster/2Linzi, выпускавшихся на продакшн через Medianet

Папка lib - реализация классов
bxautoload.php - автозагрузка классов, на 15.02.18 есть косяк у битрикса, пока напрямую подключаем.

sendsaysendform.php
sendsaysendform.php
-это точки входа в ваш функционал
эти файлы подключаються инклюдами через скрипты расположены в /local/ajax/

sendsayOrderSms.php
sendsaysendform.php

соотсветсвенно

getSiteControlScript.php - этот скрипт также пронесен через инклюд

sendsay.php - класс для сендсея
sendsayAction.php - класс прослойка для action, реализация общих функции

реализации
sendsayActionOrderSms.php
sendsayActionSubscribe.php

sendsay.php


добавил пару функции для логирования.
дефолтный путь для логов
/upload/logs/external/ от корня
ваш лог пишеться в https://www.lensmaster.ru/upload/logs/external/futurbit/log.txt

по логике ничего не трогал
менял обьявления переменых и функции с private на public
заменил вывод в лог на новые функции


