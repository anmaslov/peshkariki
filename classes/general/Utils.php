<?php

class CUtilsPeshkariki
{
    const MODULE_ID = "anmaslov.peshkariki";

    public static function addLog($data, $object = 'anmaslov.peshkariki', $severity = 'DEBUG')
    {
        $isLog = COption::GetOptionString(COrderAnmaslovPeshkariki::MODULE_ID, 'PROPERTY_MAKE_LOG', 'N');

        if ($isLog == 'Y'){

            if (is_array($data))
                $data = serialize($data);

            CEventLog::Add(array(
                "SEVERITY" => $severity,
                "AUDIT_TYPE_ID" => "PESHKARIKI_TYPE",
                "MODULE_ID" => COrderAnmaslovPeshkariki::MODULE_ID,
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

    public static function getConfig($configStr)
    {
        return self::toUtf(COption::GetOptionString(self::MODULE_ID, $configStr));
    }

    public function ASD_OnEventLogGetAuditTypes()
    {
        return array('PESHKARIKI_TYPE' => GetMessage('ANMASLOV_PESHKARIKI_OWN_TYPE'));
    }
}