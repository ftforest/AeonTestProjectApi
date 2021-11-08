<?php

class User {

    // GENERAL

    public static function user_info($data) {
        // vars
        $user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
        $phone = isset($data['phone']) ? preg_replace('~[^\d]+~', '', $data['phone']) : 0;
        // where
        if ($user_id) $where = "user_id='".$user_id."'";
        else if ($phone) $where = "phone='".$phone."'";
        else return [];
        // info
        $q = DB::query("SELECT user_id, phone, first_name, last_name, middle_name, email, gender_id, count_notifications FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'middle_name' => $row['middle_name'],
                'gender_id' => (int) $row['gender_id'],
                'email' => $row['email'],
                'phone' => (int) $row['phone'],
                'phone_str' => phone_formatting($row['phone']),
                'count_notifications' => (int) $row['count_notifications']
            ];
        } else {
            return [
                'id' => 0,
                'first_name' => '',
                'last_name' => '',
                'middle_name' => '',
                'gender_id' => 0,
                'email' => '',
                'phone' => '',
                'phone_str' => '',
                'count_notifications' => 0
            ];
        }
    }

    public static function user_get_or_create($phone) {
        // validate
        $user = User::user_info(['phone' => $phone]);
        $user_id = $user['id'];
        // create
        if (!$user_id) {
            DB::query("INSERT INTO users (status_access, phone, created) VALUES ('3', '".$phone."', '".Session::$ts."');") or die (DB::error());
            $user_id = DB::insert_id();
        }
        // output
        return $user_id;
    }

    // TEST

    public static function owner_info() {
        // your code here ...
    }

    public static function user_update($data = []) {
        // your code here ...
        // vars
        $user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
        $phone = isset($data['phone']) ? preg_replace('~[^\d]+~', '', $data['phone']) : 0;
        $count_fields = 0;
        $query_update_fields = '';
        $user_update_iterator = 0;
        $user_notifications_fields = [];
        $user_notifications_fields_iterator = 0;
        $user_notifications_fields_string = '';
        $user_notifications_fields_value = '';
        // checking for existence fields in request
        if (isset($data['phone_new'])) {
            $count_fields++;
        };
        if (isset($data['first_name'])) {
            $count_fields++;
        }
        if (isset($data['last_name'])) {
            $count_fields++;
        }
        if (isset($data['middle_name'])) {
            $count_fields++;
        }
        if (isset($data['email'])) {
            $count_fields++;
        }
        if ($count_fields == 0) {
            return [
                'error' => 'there is no data for updating in the request'
            ];
        } else if ($count_fields == 5) {
            // var update
            // - check phone
            if (phone_check(preg_replace('~[^\d]+~', '', $data['phone_new']))) {
                $user_update['phone_new'] = isset($data['phone_new']) ? preg_replace('~[^\d]+~', '', $data['phone_new']) : $data['phone'];
            } else {
                return [
                    'validate' => 'the digits in the phone number should be 11'
                ];
            }
            // var update
            $user_update['first_name'] = isset($data['first_name']) ? trim(strip_tags($data['first_name'])) : '';
            $user_update['last_name'] = isset($data['last_name']) ? trim(strip_tags($data['last_name'])) : '';
            $user_update['middle_name'] = isset($data['middle_name']) ? trim(strip_tags($data['middle_name'])) : '';
            $user_update['email'] = isset($data['email']) ? mb_strtolower(trim(strip_tags($data['email']))) : '';
            // checking for non-empty
            if ($user_update['first_name'] == '') {
                return [
                    'validate' => 'first_name: the field should not be empty'
                ];
            }
            if ($user_update['last_name'] == '') {
                return [
                    'validate' => 'last_name: the field should not be empty'
                ];
            }
            if ($user_update['phone_new'] == '') {
                return [
                    'validate' => 'phone_new: the field should not be empty'
                ];
            }
            // where
            if ($user_id) $where = "user_id='".$user_id."'";
            else if ($phone) $where = "phone='".$phone."'";
            else return [];
            // Set fields foreach string Query
            foreach ($user_update as $key => $value) {
                $user_update_iterator++;
                if ($key == 'phone_new') $query_update_fields .= "phone={$value}, ";
                else if (count($user_update) == $user_update_iterator) $query_update_fields .= "{$key}='{$value}' ";
                else $query_update_fields .= "{$key}='{$value}', ";
            }
            // + 1 notification
            DB::query("UPDATE users SET count_notifications=count_notifications+1 WHERE ".$where." LIMIT 1;") or die (DB::error());
            // update
            DB::query("UPDATE users SET ".$query_update_fields." WHERE ".$where." LIMIT 1;") or die (DB::error());
            // select user
            $q = DB::query("SELECT user_id, phone, first_name, last_name, middle_name, email, gender_id, count_notifications FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
            if ($row = DB::fetch_row($q)) {
                // notification fields
                $user_notifications_fields = [
                    'user_id' => $row['user_id'],
                    'title' => 'title '.$row['first_name']." ".$row['last_name'],
                    'description' => 'phone:'.$row['phone'],
                    'viewed' => 0,
                    'created' => time(),
                ];
                // Set fields foreach string Query
                foreach ($user_notifications_fields as $key => $value) {
                    $user_notifications_fields_iterator++;
                    if (count($user_notifications_fields) == $user_notifications_fields_iterator) {
                        $user_notifications_fields_string .= "{$key}";
                        $user_notifications_fields_value .= "'{$value}'";
                    } else {
                        $user_notifications_fields_string .= "{$key},";
                        $user_notifications_fields_value .= "'{$value}',";
                    }

                }
                // add notification
                $q = DB::query("INSERT INTO user_notifications (".$user_notifications_fields_string.") VALUES (".$user_notifications_fields_value.");") or die (DB::error());
                DB::fetch_row($q);
                return [
                    'id' => (int) $row['user_id'],
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'middle_name' => $row['middle_name'],
                    'gender_id' => (int) $row['gender_id'],
                    'email' => $row['email'],
                    'phone' => (int) $row['phone'],
                    'phone_str' => phone_formatting($row['phone']),
                    'count_notifications' => (int) $row['count_notifications']
                ];
            } else {
                return [
                    'id' => 0,
                    'first_name' => '',
                    'last_name' => '',
                    'middle_name' => '',
                    'gender_id' => 0,
                    'email' => '',
                    'phone' => '',
                    'phone_str' => '',
                    'count_notifications' => 0
                ];
            }
        } else {
            // do not update - fields count < 5
            return [
                'validate' => 'not field: phone_new or first_name or last_name or middle_name or email'
            ];
        }
    }
}
