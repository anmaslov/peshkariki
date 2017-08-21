<?php

/**
 * Created by PhpStorm.
 * User: Maslov Alexey
 * Date: 21.08.17
 * Time: 10:44
 */
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
        //todo create request;
        $res = $this->query('login', $req);

        if($res['success'] == true){
            $this->token = $res['response']['token'];
            return $this->token;
        }
        else
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
            $req = json_encode($req);

        $response = PeshkarikiCurl::request($uri, $req);
        if ($response['success'] == false) {
            $this->getError($response);
            return false;
        }else{
            return $response;
        }
    }

    private function getError($data)
    {
        //todo show error code and text error
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
        $ch = curl_init();

        curl_setopt ($ch, CURLOPT_URL, Peshkariki::URL . $uri);
        curl_setopt ($ch, CURLOPT_POST, true);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 30);

        //send request
        $res = curl_exec($ch);

        if($res === FALSE){
            die(curl_error($ch));
        }

        $resData = json_decode($res, TRUE);

        return $resData;
    }
}