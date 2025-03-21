<?php
    function pass_generator($length = 12){
    $chars = "qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM0123456789!@#$%^&*()-_=+";
    $password = "";

        for($i = 0;$i < $length;$i++){
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
    return $password;
    }
?>