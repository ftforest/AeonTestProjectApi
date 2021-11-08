<?php

class Notification {

    // TEST

    // your code here ...

    public static function notification_info($data = []) {
        // vars
        $user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
        $viewed = isset($data['viewed']) ? trim(strip_tags($data['viewed'])) : '';
        $row_all_final = [];
        // where
        if ($user_id) {
            $where = "user_id='" . $user_id . "'";
            if ($viewed == 'unread') $where .= " AND viewed='0'";
            else if ($viewed == 'read') $where .= " AND viewed='1'";
            else if ($viewed == 'all') $where .= "";
        }
        else return [];
        // info
        $q = DB::query("SELECT notification_id, user_id, title, description, viewed, created FROM user_notifications WHERE ".$where." ;") or die (DB::error());
        if ($row_all = DB::fetch_all($q)) {
            foreach ($row_all as $row) {
                $row_all_final[] = [
//                    'notification_id' => (int) $row['notification_id'],
//                    'user_id' => (int) $row['user_id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'viewed' => $row['viewed'],
//                    'created' => (int) $row['created'],
                    'created_str' => date("d-m-Y ", (int) $row['created']),
                ];
            }
            return $row_all_final;

        } else {
            return [
                'message' => 'empty'
            ];
        }
    }

    public static function notifications_read($data = []) {
        // vars
        $user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
        // where
        if ($user_id) $where = "user_id='" . $user_id . "'";
        else return [];
        $q = DB::query("UPDATE user_notifications SET viewed= 1  WHERE ".$where." ;") or die (DB::error());
        return [
            'message' => "Notifications all read User_id= ".$user_id
        ];
    }
}

