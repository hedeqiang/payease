<?php

namespace Hedeqiang\Yizhifu\Traits;

trait Utils
{
    protected function addPKCS7Padding($string, $blocksize = 16)
    {
        $len = strlen($string);
        $pad = $blocksize - ($len % $blocksize);
        $string .= str_repeat(chr($pad), $pad);
        return $string;
    }

    /*
    * 去除空值的元素
    */
    protected function clearBlank($arr)
    {
        return (array_filter($arr, function ($var) {
            return ($var <> '');
        }));
    }

    /**
     * @param array $data
     * @return string
     */
    protected function buildJson(array $data)
    {
        $hmacSource = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                ksort($value);
                $value = array_filter($value);
                foreach ($value as $key2 => $value2) {
                    if (is_object($value2)) {
                        $value2 = array_filter((array)$value2);
                        ksort($value2);
                        foreach ($value2 as $oKey => $oValue) {
                            $oValue .= '#';
                            $hmacSource .= trim($oValue);
                        }
                    } elseif (is_array($value2)) {
                        ksort($value2);
                        foreach ($value2 as $key3 => $value3) {
                            if (is_object($value3)) {
                                $value3 = array_filter((array)$value3);
                                ksort($value3);
                                foreach ($value3 as $oKey => $oValue) {
                                    $oValue .= '#';
                                    $hmacSource .= trim($oValue);
                                }
                            } else {
                                $value3 .= '#';
                                $hmacSource .= trim($value3);
                            }
                        }
                    } else {
                        $value2 .= '#';
                        $hmacSource .= trim($value2);
                    }
                }
            } else {
                $value .= '#';
                $hmacSource .= trim($value);
            }
        }
        return $hmacSource;
    }

    protected function array_remove_empty(&$arr, $trim = true)
    {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                array_remove_empty($arr[$key]);
            } else {
                $value = trim($value);
                if ($value == '') {
                    unset($arr[$key]);
                } elseif ($trim) {
                    $arr[$key] = $value;
                }
            }
        }
    }

    protected function rsaPublicEncode($public_key,$rands){
        $encryptKey=file_get_contents($public_key);
        $pem = chunk_split(base64_encode($encryptKey),64,"\n");//转换为pem格式的公钥
        $public_key = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";
        $pu_key =  openssl_pkey_get_public($public_key);
        openssl_public_encrypt($rands,$encrypted,$pu_key);
        return base64_encode($encrypted);
    }

    /**
     * AES加密
     * @param string $rands
     * @param string $json_str
     * @return string
     */
    protected function aesEncrypt(string $rands, string $json_str)
    {
        $str = trim($json_str);
        $str = $this->addPKCS7Padding($str);
        $encrypt_str = openssl_encrypt($str, 'AES-128-ECB', $rands, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
        return base64_encode($encrypt_str);
    }

    /**
     * @param $encryptKey
     * @param $private_key
     * @param $password
     * @return mixed
     */
    protected function rsaPrivateDecode($encryptKey,$private_key,$password){
        $prikey=file_get_contents($private_key);
        $results= [];
        openssl_pkcs12_read($prikey,$results,$password);
        $private_key=$results['pkey'];
        $pi_key =  openssl_pkey_get_public($private_key);
        openssl_private_decrypt(base64_decode($encryptKey),$decrypted,$private_key);
        return $decrypted;
    }

    /**
     * AES解密
     * @param $data
     * @param $secret_key
     * @return string
     */
    protected function aesDecrypt($data, $secret_key)
    {
        $encrypt_str = openssl_decrypt($data, "AES-128-ECB", $secret_key);
        $encrypt_str = preg_replace('/[\x00-\x1F]/', '', $encrypt_str);
        return json_decode($encrypt_str, true);
    }

    /**
     * CFCA公钥验签
     * @param $data
     * @param $path
     * @param $hmac
     * @return false|int
     */
    protected function rsaPubilcSign($data,$path,$hmac){
        $public_key=file_get_contents($path);
        $pem1 = chunk_split(base64_encode($public_key),64,"\n");
        $pem1 = "-----BEGIN CERTIFICATE-----\n".$pem1."-----END CERTIFICATE-----\n";
        $pi_key =  openssl_pkey_get_public($pem1);
        return openssl_verify($data,base64_decode($hmac),$pem1,OPENSSL_ALGO_MD5);
    }

    /**
     * CFCA私钥签名
     * @param $data
     * @param $path
     * @param $password
     * @return string
     */
    function rsaPrivateSign($data,$path,$password){
        $pubKey = file_get_contents($path);
        $results=array();
        openssl_pkcs12_read($pubKey,$results,$password);
        $private_key=$results['pkey'];
        $pi_key =  openssl_pkey_get_private($private_key);//这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
        openssl_sign($data, $signature,$private_key,"md5");
        return base64_encode($signature);
    }


    /**
     * 生成16位随机数（AES秘钥）AES加密JSON数据串
     * @return string
     */
    protected function getRandomStr(): string
    {
        $str1 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        $randStr = str_shuffle($str1);//打乱字符串
        return substr($randStr, 0, 16);//16-bit random AES key
    }

}