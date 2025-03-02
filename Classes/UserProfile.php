<?php
    class UserProfile {
        public static function update($userId, $data) {
            $user = User::find($userId);
            if ($user) {
                $user->name = $data['name'] ?? $user->name;
                $user->email = $data['email'] ?? $user->email;
                if (isset($data['password'])) {
                    $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
                }
                $user->save();
            }
        }
    }
