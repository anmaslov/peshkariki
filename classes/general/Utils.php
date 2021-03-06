<?php

IncludeModuleLangFile(__FILE__);

class CUtilsPeshkariki
{
    const MODULE_ID = "anmaslov.peshkariki";

    public static function addLog($data, $object = 'anmaslov.peshkariki', $severity = 'DEBUG')
    {
        $isLog = COption::GetOptionString(self::MODULE_ID, 'PROPERTY_MAKE_LOG', 'N');

        if ($isLog == 'Y'){

            if (is_array($data))
                $data = serialize($data);

            CEventLog::Add(array(
                "SEVERITY" => $severity,
                "AUDIT_TYPE_ID" => "PESHKARIKI_TYPE",
                "MODULE_ID" => self::MODULE_ID,
                "ITEM_ID" => $object,
                "DESCRIPTION" => $data,
            ));
        }
    }

    public static function toUtf($str)
    {
        if (defined('BX_UTF')) {
            return $str;
        }else{
            return mb_convert_encoding($str, 'utf-8', 'windows-1251');
        }
    }

    public static function getConfig($configStr, $default = '')
    {
        return self::toUtf(COption::GetOptionString(self::MODULE_ID, $configStr, $default));
    }

    public static function getCurrentClient()
    {
        $client = COption::GetOptionString(self::MODULE_ID, 'PROPERTY_CLIENT', 'BITRIX');
        if ($client == 'CURL')
            return new CurlHttpClient();
        else
            return new BitrixHttpClient();
    }

    public function ASD_OnEventLogGetAuditTypes()
    {
        return array('PESHKARIKI_TYPE' => GetMessage('ANMASLOV_PESHKARIKI_OWN_TYPE'));
    }
}