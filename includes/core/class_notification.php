<?php

class Notification {

    // TEST

    // your code here ...

    public static function notification_info($data = []) {
        // vars
        $items = [];
        $limit = 50;
        $next_offset = 0;
        $offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
        $viewed = isset($data['viewed']) ? trim(strip_tags($data['viewed'])) : '';
        // where
        $where = [];
        $where[] = "user_id='".Session::$user_id."'";
        if ($offset) $where[] = "notification_id<'".$offset."'";
        if ($viewed == 'unread') $where[] = "viewed='0'";
        if ($viewed == 'read') $where[] = "viewed='1'";
        $where = implode(" AND ", $where);
        // info
        $q = DB::query("SELECT notification_id, user_id, title, description, viewed, created FROM user_notifications WHERE ".$where." ORDER BY notification_id DESC LIMIT ".$limit.";") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $next_offset = $row['notification_id'];
            $items[] = [
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'viewed' => (bool) $row['viewed'],
                    'created' => date("Y-m-d\TH:i:s\Z ", $row['created']),
            ];
        }
        // output
        return ['items' => $items, 'next_offset' => (int) $next_offset];
    }

    public static function notifications_read() {
        // query
        DB::query("UPDATE users SET count_notifications='0' WHERE user_id='".Session::$user_id."' LIMIT 1;") or die (DB::error());
        DB::query("UPDATE user_notifications SET viewed='1' WHERE user_id='".Session::$user_id."';") or die (DB::error());
        // output
        return ['message' => 'notifications read'];
    }

}

