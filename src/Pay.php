<?php

namespace Hedeqiang\Yizhifu;

use GuzzleHttp\Client;
use Hedeqiang\Yizhifu\Support\Config;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class Pay
{

    protected $config;

    protected $guzzleOptions = [];

    const ENDPOINT_TEMPLATE = 'https://apis.5upay.com/%s';


    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    protected function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    /**
     * 按照 键名 对关联数组进行升序排序：
     * @param $params
     * @return array
     */
    protected function getArr($params): array
    {
        $data = [];
        foreach ($params as $k => $var) {
            if (is_scalar($var) && $var !== '') {//如果给出的变量参数 var 是一个标量，is_scalar() 返回 TRUE，否则返回 FALSE。标量变量是指那些包含了 integer、float、string 或 boolean的变量，而 array、object 和 resource 则不是标量。
                $data[$k] = $var;
            } elseif (is_object($var)) {
                $data[$k] = array_filter((array)$var);
            } elseif (is_array($var)) {
                $data[$k] = array_filter($var);
            }
            if (empty($data[$k])) {
                unset($data[$k]);
            }
        }

        ksort($data);
        return $data;
    }

    protected function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request($path, $params)
    {
        $data = $this->getArr($params);

        $hmacSource = $this->buildJson($data);

        $sha1mac = sha1($hmacSource, true); //SHA1加密

        $pubKey = file_get_contents($this->config->get('privateKey'));//私钥签名
        $results = [];
        $worked = openssl_pkcs12_read($pubKey, $results, '123456');
        $rs = openssl_sign($sha1mac, $hmac, $results['pkey'], "md5");
        $hmac = base64_encode($hmac);

        $hmacarr = [];
        $hmacarr["hmac"] = $hmac;
        $arr_t = (array_merge($params, $hmacarr)); //合并数组

        $json_str = json_encode($arr_t, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  //将数组转成JSON

        /*
         * 生成16位随机数（AES秘钥）AES加密JSON数据串
         */
        $str1 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        $randStr = str_shuffle($str1);//打乱字符串
        $rands = substr($randStr, 0, 16);//16-bit random AES key

        $screct_key = $rands;
        $str = trim($json_str);
        $str = $this->addPKCS7Padding($str);
        $encrypt_str = openssl_encrypt($str, 'AES-128-ECB', $screct_key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
        $data = base64_encode($encrypt_str);


        $verifyKey4Server = file_get_contents($this->config->get('publicKey'));  //公钥加密AES
        $pem = chunk_split(base64_encode($verifyKey4Server), 64, "\n");//转换为pem格式的公钥
        $public_key = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
        $pu_key = openssl_pkey_get_public($public_key);//这个函数可用来判断公钥是否是可用的
        openssl_public_encrypt($rands, $encryptKey, $pu_key);//公钥加密
        $encryptKey = base64_encode($encryptKey);

        $response = $this->getHttpClient()->post($this->buildEndpoint($path), [
            'headers' => [
                'Content-Type' => 'application/vnd.5upay-v3.0+json',
                'encryptKey'   => $encryptKey,
                'merchantId'   => $this->config->get('merchantId'),
                'requestId'    => time() . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT),
            ],
            'body'    => $data
        ]);

        $contents = json_decode($response->getBody()->getContents(), true);
        $responsedata = [
            "data"       => $contents['data'],
            "encryptKey" => $response->getHeader('encryptKey')[0],
//            "merchantId" => $response->getHeader('merchantId')[0]
        ];


        $encryptKey = $responsedata['encryptKey'];
        $pubKey = file_get_contents('client.pfx');
        $results = [];
        $worked = openssl_pkcs12_read($pubKey, $results, '123456');
        $private_key = $results['pkey'];
        $pi_key = openssl_pkey_get_private($private_key);//这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
        openssl_private_decrypt(base64_decode($encryptKey), $decrypted, $pi_key);//私钥解密

        $responsedatadata = $responsedata['data'];


        $screct_key = $decrypted;
        $encrypt_str = openssl_decrypt($responsedatadata, "AES-128-ECB", $screct_key);
        $encrypt_str = preg_replace('/[\x00-\x1F]/', '', $encrypt_str);
        $encrypt_str = json_decode($encrypt_str, true);

        return $encrypt_str;
    }


    public function handleNotify()
    {
        $request = $this->getCallbackParams();
        $raw_post_data = $request->getBody()->getContents();
        $responsedata = json_decode($raw_post_data, true);
        $post = $responsedata;
        $post['encryptKey'] = $request->getHeader('encryptKey')[0];
        $post['merchantId'] = $request->getHeader('merchantId')[0];

        $encryptKey = $post['encryptKey'];
        //return $encryptKey;

        $pubKey = file_get_contents($this->config->get('privateKey'));
        $results = array();
        $worked = openssl_pkcs12_read($pubKey, $results, '123456');
        $private_key = $results['pkey'];
        $pi_key = openssl_pkey_get_private($private_key);//这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
        openssl_private_decrypt(base64_decode($encryptKey), $decrypted, $pi_key);//私钥解密

        $responsedatadata = $responsedata['data'];
        $screct_key = $decrypted;
        $encrypt_str = openssl_decrypt($responsedatadata, "AES-128-ECB", $screct_key);
        $encrypt_str = preg_replace('/[\x00-\x1F]/', '', $encrypt_str);
        $encrypt_str = json_decode($encrypt_str, true);

        $encrypt_str = $this->clearBlank($encrypt_str);
        $hmac = $encrypt_str['hmac'];
        unset($encrypt_str['hmac']); //去除数组的一个hmac元素
        ksort($encrypt_str);

        $hmacSource = $this->buildJson($encrypt_str);
        $sha1mac = sha1($hmacSource, true);

        $verifyKeyPath = $this->config->get('publicKey');
        $verifyKey4Server = file_get_contents($verifyKeyPath);
        $pem = chunk_split(base64_encode($verifyKey4Server), 64, "\n");//转换为pem格式的公钥
        $pem = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
        $keyid = openssl_pkey_get_public($pem);

        $verify = openssl_verify($sha1mac, base64_decode($hmac), $keyid, OPENSSL_ALGO_MD5);

        if ($verify === 1) {
            return $encrypt_str;
        } else {
            return "Fail";
        }
    }

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


    /**
     * @param array|ServerRequestInterface|null $contents
     */
    protected function getCallbackParams($contents = null): ServerRequestInterface
    {
        if (is_array($contents) && isset($contents['body']) && isset($contents['headers'])) {
            return new ServerRequest('POST', 'http://localhost', $contents['headers'], $contents['body']);
        }

        if (is_array($contents)) {
            return new ServerRequest('POST', 'http://localhost', [], json_encode($contents));
        }

        if ($contents instanceof ServerRequestInterface) {
            return $contents;
        }

        return ServerRequest::fromGlobals();
    }

    /**
     *
     * @param string $path
     * @return string
     */
    protected function buildEndpoint(string $path): string
    {
        return sprintf(self::ENDPOINT_TEMPLATE, $path);
    }
}
