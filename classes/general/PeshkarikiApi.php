<?php

/**
 * Created by PhpStorm.
 * User: Maslov Alexey
 * Date: 21.08.17
 * Time: 10:44
 */
IncludeModuleLangFile(__FILE__);

class PeshkarikiApi
{
    const URL = 'https://api.peshkariki.ru/commonApi/';

    private $token = '';
    private $login;
    private $password;

    public function __construct($login, $password)
    {
        $this->login = $login;
        $this->password = $password;
        //$this->token = $token;
    }

    public function login()
    {
        $req['login'] = $this->login;
        $req['password'] = $this->password;

        $res = $this->query('login', $req);

        if($res['SUCCESS'] == true){
            $this->token = $res['DATA']['response']['token'];
            return $this->token;
        }else
            return false;
    }

    public function revokeToken()
    {
        $this->query('revokeToken');
    }

    public function checkStatus($orderId)
    {
        if(is_array($orderId))
            $req['order_id'] = $orderId;
    }

    public function changeStatus($arrOrderId)
    {
        //todo make method
    }

    public function addOrder($arrOrder, $calc)
    {
        $arrOrder['calculate'] = $calc;
        $req['orders'] = $arrOrder;

        $this->query('addOrder', $req);
    }

    public function cancelOrder($orderId)
    {
        $req['order_id'] = $orderId;
        $this->query('cancelOrder', $req);
    }

    public function orderDetails($orderId)
    {
        $req['order_id'] = $orderId;
        $this->query('orderDetails', $req);
    }

    public function checkBalance()
    {
        $this->query('checkBalance');
    }

    private function query($uri, $req = array())
    {
        if (!empty($req))
            $req = 'request=' . json_encode($req);

        $response = PeshkarikiCurl::request($uri, $req);
        return $response;
    }

    public static function getError($errorId)
    {
        $mess = GetMessage("ANMASLOV_PESHKARIKI_ERROR_MESSAGE_$errorId");
        return strlen($mess)>0 ? $mess: $errorId;
    }


    public static function getCityList()
    {
        $cityList = [1, 2, 3, 4, 5, 6];
        foreach ($cityList as $city)
        {
            $res[] = [
                'ID' => $city,
                'NAME' => GetMessage("ANMASLOV_PESHKARIKI_CITY_$city"),
            ];
        }
        return $res;
    }
}

class PeshkarikiCurl
{
    public static function request($uri, $data)
    {
        $result = array('SUCCESS' => false, 'DATA' => GetMessage('ANMASLOV_PESHKARIKI_SOME_ERROR'));

        try{
            $ch = curl_init();

            curl_setopt ($ch, CURLOPT_URL, PeshkarikiApi::URL . $uri);
            curl_setopt ($ch, CURLOPT_POST, true);
            curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 30);

            $res = curl_exec($ch);
            $result['DATA'] = json_decode($res, TRUE);

            if($result['DATA']['success'] == false){
                $result['DATA'] = PeshkarikiApi::getError($result['DATA']['code']);
            }else{
                $result['SUCCESS'] = true;
            }

        }catch (Exception $e){
            $result['DATA'] = GetMessage('ANMASLOV_PESHKARIKI_CONNECTION_ERROR');
        }

        return $result;
    }
}