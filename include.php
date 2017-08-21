<?php

$module_id = 'anmaslov.peshkariki';

CModule::AddAutoloadClasses(
    $module_id,
    array(
        "PeshkarikiAPI" => "classes/general/PeshkarikiApi.php",
        "CDeliveryAnmaslovPeshkariki" => "classes/general/PeshkarikiDelivery.php"
    )
);
