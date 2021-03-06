<?php

IncludeModuleLangFile(__FILE__);

class COrderAnmaslovPeshkariki{

    const MODULE_ID = 'anmaslov.peshkariki';

    const CASH_PAYED = 0;
    const CASH_CURIER = 1;

    public static function addOrder($id, $arFields)
    {
        $propMakeOrder = CUtilsPeshkariki::getConfig('PROPERTY_MAKE_ORDER', 'N');
        $propOrderStatus = CUtilsPeshkariki::getConfig('PROPERTY_ORDER_STATUS', 'F');

        if ($propMakeOrder == 'N' || $propOrderStatus != $arFields)
            return;

        $arOrder = CSaleOrder::GetById($id);

        if ($arOrder['DELIVERY_ID'] != 'anmaslov_peshkariki:courier')
            return;

        $arProp = self::getOrderProps($id);
        if ($arProp['city'] == false) {
            CUtilsPeshkariki::addLog(GetMessage('ANMASLOV_PESHKARIKI_NOT_CITY'), 'add_order', 'ERROR');
            return;
        }            

        $arRouteItems = self::getItems($id);
        
        $arPrepare = array(
            'ORDER_ID' => $id,
            'ORDER' => $arProp,
            'ITEMS' => $arRouteItems,
            'ORDER_DETAIL' => $arOrder,
        );

        $arData = self::prepareData($arPrepare);
        if (!$arData)
            return;

        CUtilsPeshkariki::addLog($arData, 'add_order', 'INFO');

        $pa = new PeshkarikiApi(
            CUtilsPeshkariki::getCurrentClient(),
            CUtilsPeshkariki::getConfig('PROPERTY_LOGIN'),
            CUtilsPeshkariki::getConfig('PROPERTY_PASSWORD')
        );

        $token = $pa->login();
        if ($token['SUCCESS'] == false)
            return;

        $order = $pa->addOrder($arData);
        CUtilsPeshkariki::addLog($order, 'add_new_order', 'INFO');

        if ($order['SUCCESS'] == true) {
            $pId = $order['DATA']['response'][$id]['id'];
            CSaleOrder::Update($id, 
                array("TRACKING_NUMBER" => $pId)
            );
        }
        
        return $price;
    }

    public static function cancelOrder($id, $cancel)
    {
        $propCancelOrder = CUtilsPeshkariki::getConfig('PROPERTY_CANCEL_ORDER', 'N');

        if ($propCancelOrder == 'N' || $cancel != 'Y')
            return;
        
        $arOrder = CSaleOrder::GetById($id);
        
        //Если пустой номер трека
        if ($arOrder['DELIVERY_ID'] != 'anmaslov_peshkariki:courier' || strlen($arOrder['TRACKING_NUMBER']) == 0) {
            CUtilsPeshkariki::addLog(GetMessage('ANMASLOV_PESHKARIKI_NOT_TRACK_NUMBER'), 'cancel_order', 'ERROR');
            return;
        }
        
        $pa = new PeshkarikiApi(
            CUtilsPeshkariki::getCurrentClient(),
            CUtilsPeshkariki::getConfig('PROPERTY_LOGIN'),
            CUtilsPeshkariki::getConfig('PROPERTY_PASSWORD')
        );
    
        $token = $pa->login();
        if ($token['SUCCESS'] == false)
            return;

        $order = $pa->cancelOrder($arOrder['TRACKING_NUMBER']);
        CUtilsPeshkariki::addLog($order, 'cancel_order', 'INFO');
    }

    public static function prepareData($arData)
    {
        if (!$cityKey = $arData['ORDER']['city']){
            CUtilsPeshkariki::addLog(GetMessage('ANMASLOV_PESHKARIKI_NOT_CITY'), 'prepareData', 'ERROR');
            return false;
        }

        unset($arData['ORDER']['city']);

        $arrTo = $arData['ORDER'];
        $arrTo['name'] = (strlen($arrTo['name'])>0) ? $arrTo['name'] : GetMessage('ANMASLOV_PESHKARIKI_NOT_NAME');
        $arrTo['time_from'] = date('Y-m-d', strtotime('+1 day')) . ' 09:00:00';
        $arrTo['time_to'] = date('Y-m-d', strtotime('+2 day')) . ' 18:00:00';
        $arrTo['items'] = $arData["ITEMS"];

        $arrFrom = array(
            'name' => CUtilsPeshkariki::getConfig("PROPERTY_NAME$cityKey"),
            'phone' => CUtilsPeshkariki::getConfig("PROPERTY_PHONE$cityKey"),
            'street' => CUtilsPeshkariki::getConfig("PROPERTY_STREET$cityKey"),
            'building' => CUtilsPeshkariki::getConfig("PROPERTY_BUILDING$cityKey"),
            'apartments' => CUtilsPeshkariki::getConfig("PROPERTY_APARTMENTS$cityKey"),
            'time_from' => date('Y-m-d', strtotime('+1 day')) . ' 09:00:00',
            'time_to' => date('Y-m-d', strtotime('+2 day')) . ' 18:00:00',
            'target' => CUtilsPeshkariki::getConfig("PROPERTY_TARGET$cityKey"),
            //'items' => array(),
        );

        if (strlen($arrFrom['name'].$arrFrom['phone'].$arrFrom['street'].$arrFrom['building']) == 0) {
            CUtilsPeshkariki::addLog(GetMessage('ANMASLOV_PESHKARIKI_BLANK_STRING'), 'prepareData', 'ERROR');
            return false;
        }

        $cash = self::getCashType($arData['ORDER_DETAIL']['PAY_SYSTEM_ID']); 

        $arOrder = array(
            'inner_id' => $arData['ORDER_ID'],
            'comment' => CUtilsPeshkariki::getConfig('PROPERTY_ORDER_COMMENT'),
            "calculate" => 0,
            'cash' => $cash, //0-товар предоплачен
            'clearing' => CUtilsPeshkariki::getConfig('PROPERTY_CLEARING', 0),
            'ewalletType' => 0,
            'city_id' => $cityKey,
            'order_type_id' => 1,
            'route' => array($arrFrom, $arrTo)
        );

        //если 1 - необходимо забрать оплату наличными
        if ($cash == self::CASH_PAYED) {
            $arOrder['ewalletType'] = CUtilsPeshkariki::getConfig('PROPERTY_CACH_RETURN_METHOD');
            $arOrder['ewallet'] = CUtilsPeshkariki::getConfig('PROPERTY_RETURN_CONTACTS');
        }

        return $arOrder;
    }

    /**
     * Брать деньги за товар
     * 0 - товар оплачен, денег не брать
     * 1 - товар не оплачен, брать деньги
     */
    public static function getCashType($paySystemId)
    {
        $cash = CUtilsPeshkariki::getConfig('PROPERTY_PAYMENT_METHOD', self::CASH_PAYED); //Значение по умолчанию
        
        $payVal = explode(',', CUtilsPeshkariki::getConfig('PROPERTY_PAYMENT_METHOD_PAYED', ''));
        if (in_array($paySystemId, $payVal))
            return self::CASH_PAYED; //товар полностью оплачен

        $payVal = explode(',', CUtilsPeshkariki::getConfig('PROPERTY_PAYMENT_METHOD_CURIER', ''));
        if (in_array($paySystemId, $payVal))
            return self::CASH_CURIER; //необходимо взять деньги за товар

        return $cash;
    }

    public static function getOrderProps($orderId)
    {
        $dbProp = CSaleOrderPropsValue::GetList(array(), array('ORDER_ID' => $orderId));

        $arProp = array();
        while ($arVals = $dbProp->GetNext()) {
            switch ($arVals['CODE']) {
                case 'FIO':
                    $arProp['name'] = CUtilsPeshkariki::toUtf($arVals['VALUE']);
                    break;
                case 'CONTACT_PERSON':
                    $arProp['name'] = CUtilsPeshkariki::toUtf($arVals['VALUE']);
                    break;
                case 'PHONE':
                    $arProp['phone'] = CUtilsPeshkariki::toUtf($arVals['VALUE']);
                    break;
                case 'LOCATION':
                    $location = CSaleLocation::GetByID($arVals['VALUE'], LANGUAGE_ID);
                    $arrCity = PeshkarikiApi::getCityList();
                    $cityKey = array_search($location['CITY_NAME'], $arrCity);
                    $arProp['city'] = $cityKey;
                    break;
                case 'CITY':
		    $location = CSaleLocation::GetByID($arVals['VALUE'], LANGUAGE_ID);
                    $arrCity = PeshkarikiApi::getCityList();
                    $cityKey = array_search($location['CITY_NAME'], $arrCity);
                    $arProp['city'] = $cityKey;
                    break;
                case 'ADDRESS':
                    $arProp['street'] = CUtilsPeshkariki::toUtf($arVals['VALUE']);
                    break;
            }
        }
        return $arProp;
    }

    public static function getItems($orderId)
    {
        $dbItems = CSaleBasket::GetList(
            array(),
            array("ORDER_ID" => $orderId), false, false,
            array("ID", "NAME", "PRICE", "WEIGHT", "QUANTITY")
        );

        $arRouteItems = array();
        $defaultWeight = CUtilsPeshkariki::getConfig("PROPERTY_WEIGHT", 10);
        while ($arItems = $dbItems->Fetch()) {
            $arRouteItems[] = array(
                'name' => CUtilsPeshkariki::toUtf($arItems['NAME']),
                'price' => round($arItems['PRICE']),
                'weight' => (intval($arItems['WEIGHT']) > 10 ? round($arItems['WEIGHT']) : $defaultWeight),
                'quant' => $arItems['QUANTITY'],
            );
        }
        return $arRouteItems;
    }
}
