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

        $q = DB::query("SELECT user_id, phone, first_name, last_name, middle_name, email, gender_id, count_notifications FROM users WHERE 'user_id' = ".Session::$user_id." LIMIT 1;") or die (DB::error());
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

    public static function user_update($data = []) {
        // vars
        $email = isset($data['email']) ? mb_convert_case(trim($data['email']), MB_CASE_LOWER, 'UTF-8') : '';
        $first_name = isset($data['first_name']) ? $data['first_name'] : '';
        $last_name = isset($data['last_name']) ? $data['last_name'] : '';
        $middle_name = isset($data['middle_name']) ? $data['middle_name'] : '';
        $phone = isset($data['phone']) ? preg_replace('~[^\d]+~', '', $data['phone']) : 0;

        // checking for existence fields in request
        // set
        $set = [];
        if (isset($data['phone'])){
            if (phone_check(preg_replace('~[^\d]+~', '', $phone))) {
                $phone = isset($data['phone']) ? preg_replace('~[^\d]+~', '', $data['phone']) : $data['phone'];
                $set[] = "phone='".$phone."'";
            } else return [ 'error' => 'Parameter phone should 11 numbers' ];
        } else return [ 'error' => 'Parameter phone is required'];
        if (isset($data['email'])) $set[] = "email='".mb_strtolower($email)."'";
        if (isset($data['first_name'])) $set[] = "first_name='".$first_name."'"; else return [ 'error' => 'Parameter first_name is required'];
        if (isset($data['last_name'])) $set[] = "last_name='".$last_name."'"; else return [ 'error' => 'Parameter last_name is required'];
        if (isset($data['middle_name'])) $set[] = "middle_name='".$middle_name."'";
        if ($set) $set = implode(', ', $set);
        else return error_response(1006, 'No parameters were passed to update.');

        // update
        DB::query("UPDATE users SET ".$set.", count_notifications=count_notifications+1 WHERE 'user_id' =".Session::$user_id." LIMIT 1;") or die (DB::error());
        // add user_notifications
        DB::query("INSERT INTO user_notifications (user_id, title, description, viewed, created ) VALUES ('".Session::$user_id."','title ".$first_name." ".$last_name."','".$phone."','".(0)."','".Session::$ts."');") or die (DB::error());
        // output
        return self::owner_info();
    }
}
