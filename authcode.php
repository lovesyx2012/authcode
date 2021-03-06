<?php

class AuthCode {

    public static function encode($str, $key) {
        return self::_auth_code($str, 'ENCODE', $key, 0);
    }

    public static function decode($str, $key) {
        return self::_auth_code($str, 'DECODE', $key, 0);
    }

    public static function _auth_code($string, $operation = 'DECODE', $key = '', $expiry = 3600) {
        /***
         * 随机密钥长度 取值 0-32;
         * 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
         * 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
         * 当此值为 0 时，则不产生随机密钥
         */
        $ckey_length = 4;
        
        $key = md5($key);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if($operation == 'DECODE') {
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc.base64_encode($result);
        }
    }

}

$source_string = "My name is Hu Ang, I'm a programmer.";
$secret_key = 'fr1e54b8t4n4m47';
echo 'Source String: ' . $source_string . "\n";
$encoded_string = AuthCode::encode($source_string, $secret_key);
echo 'After Encode : ' . $encoded_string . "\n";
$decoded_string = AuthCode::decode($encoded_string, $secret_key);
echo 'After Decode : ' . $decoded_string . "\n";
echo "----------------------------------------------\n";
$python_encoded_string = "88fcnCU6Wb+6LPREpYrhB3NcKS3OU+V8FqQ4uUklvZR170HWlyBLtPKEtP9Ui/qZp1ZqEhF9f5k6XBDixsVgEKk=";
echo 'Decode string encoded via python: ' . AuthCode::decode($python_encoded_string, $secret_key);