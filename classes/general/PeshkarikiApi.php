<?php

use \Bitrix\Main\Web\HttpClient;

IncludeModuleLangFile(__FILE__);

class PeshkarikiApi
{
    const URL = 'https://api.peshkariki.ru/commonApi/';
    const CALCULATE = 1;

    private $token = '';
    private $login;
    private $password;
    private $error;

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

        if($res['SUCCESS'] == true) {
            $this->token = $res['DATA']['response']['token'];
            $res['DATA'] = $this->token;
        }

        return $res;
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

    //$calc = 1 - only price
    public function addOrder($arrOrder, $calc = 0)
    {
        $arrOrder['calculate'] = $calc;
        $req['orders'] = array($arrOrder);
        $req['token'] = $this->token;

        $res = $this->query('addOrder', $req);

        if($res['SUCCESS'] == true){
            if ($calc)
                $res['DATA'] = $res['DATA']['response']['delivery_price'];
        }

        return $res;
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
            $request = /*'request=' .*/ json_encode($req);

        $response = PeshkarikiHttpClient::request($uri, $request);
        return $response;
    }

    public static function getErrorMsg($errorId)
    {
        $mess = GetMessage("ANMASLOV_PESHKARIKI_ERROR_MESSAGE_$errorId");
        return strlen($mess)>0 ? $mess: $errorId;
    }


    public static function getCityList()
    {
        $cityList = [1, 2, 3, 4, 5, 6];
        foreach ($cityList as $city)
        {
            $res[$city] = GetMessage("ANMASLOV_PESHKARIKI_CITY_$city");
        }
        return $res;
    }
}

class PeshkarikiHttpClient
{
    public static function request($uri, $data)
    {
        $result = array('SUCCESS' => false, 'DATA' => GetMessage('ANMASLOV_PESHKARIKI_SOME_ERROR'));
        $httpClient = new HttpClient();
        $httpClient->setHeader('Content-Type', 'application/json', true);

        try{
            $res = $httpClient->post(PeshkarikiApi::URL . $uri, $data);

            if ($res->getError){
                $result['DATA'] = GetMessage('ANMASLOV_PESHKARIKI_CONNECTION_ERROR');
                return $result;
            }

            $result['DATA'] = json_decode($res, TRUE);

            if($result['DATA']['success'] == false){
                $result['DATA'] = PeshkarikiApi::getErrorMsg($result['DATA']['code']);
            }else{
                $result['SUCCESS'] = true;
            }

        }catch (Exception $e){
            $result['DATA'] = GetMessage('ANMASLOV_PESHKARIKI_CONNECTION_ERROR');
        }

        return $result;
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

            //https://stackoverflow.com/questions/28858351/php-ssl-certificate-error-unable-to-get-local-issuer-certificate
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            $res = curl_exec($ch);

            if($res == false){
                $result['DATA'] = curl_error($ch);
                return $result;
            }

            $result['DATA'] = json_decode($res, TRUE);

            if($result['DATA']['success'] == false){
                $result['DATA'] = PeshkarikiApi::getErrorMsg($result['DATA']['code']);
            }else{
                $result['SUCCESS'] = true;
            }

        }catch (Exception $e){
            $result['DATA'] = GetMessage('ANMASLOV_PESHKARIKI_CONNECTION_ERROR');
        }

        return $result;
    }
}