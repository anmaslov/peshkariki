<?php

class CUtilsPeshkariki
{
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

    public function ASD_OnEventLogGetAuditTypes()
    {
        return array('PESHKARIKI_TYPE' => GetMessage('ANMASLOV_PESHKARIKI_OWN_TYPE'));
    }
}