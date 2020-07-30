<?php
$sendsayAction = new futurbit\sendsayAction();
$ansver = $sendsayAction->action(
    array(
        'email'=>trim($_REQUEST['email'])
    )
);
if ($ansver) echo "error"; else echo 'good';?>
