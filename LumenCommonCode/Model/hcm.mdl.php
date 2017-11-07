<?php

// !!! Only used for call APIs from current hcmchi agent system

if (!defined('__CORE_DIR')) {
    exit('Access Denied');
}

class APIExcp extends \Exception
{
    public function __construct($msg)
    {
        K::M('tools/tool')->log(
            $msg.PHP_EOL
            .'=> Exception Trace: '.json_encode($this->gettrace()),
            $this->getFile(),
            $this->getLine()
        );

        exit;
    }
}

class Mdl_Api_Hcm
{
    protected $apiEnv = null;

    public function __construct($system, $apiEnv = null)
    {
        $apiEnv = ($apiEnv ?: (
            (
                defined('__CFG::HCM_API_ENV')
                && is_string(__CFG::HCM_API_ENV)
                && __CFG::HCM_API_ENV
            ) ? __CFG::HCM_API_ENV : false
        ));

        if (! $apiEnv) {
            throw new APIExcp('Missing api env.');
        }

        $ak = (
            defined('__CFG::HCM_API_AK')
            && is_string(__CFG::HCM_API_AK)
            && __CFG::HCM_API_AK
        ) ? __CFG::HCM_API_AK : false;

        if (! $ak) {
            throw new APIExcp("Missing access token.");
        }

        $uriArr = parse_url($apiEnv);
        if (!isset($uriArr['host']) || !is_string($uriArr['host'])) {
            throw new APIExcp("Missing or illegal api host.");
        }

        $scheme = isset($uriArr['scheme'])
        ? $uriArr['scheme'].'://' : 'http://';
        $this->apiEnv = $scheme.$uriArr['host'];
        $this->ak = $ak;
    }

    public function buildUri($api)
    {
        $api = '/'.$api;
        $api = implode('/', array_filter(explode('/', $api)));

        return $this->apiEnv.'/'.$api;
    }

    protected function getSecretToken()
    {
        $sk = (
            defined('__CFG::HCM_API_SK')
            && is_string(__CFG::HCM_API_SK)
            && __CFG::HCM_API_SK
        ) ? __CFG::HCM_API_SK : false;

        if (!$sk) {
            throw new APIExcp("Missing secret token.");
        }

        return $sk;
    }

    public function launch(
        $api,
        $type    = 'GET',
        $headers = [],
        $params  = []
    ) {
        $ch = curl_init();

        $apiType = ('GET' === strtoupper($type)) ? 'READ' :'WRITE';
        $sk = ('WRITE' === $apiType)
        ? ['HCM-API-SK: '.$this->getSecretToken()]
        : [];

        $_headers = [
            'Content-Type: application/json; Charset: UTF-8',
            'HCM-API-TYPE: '.$apiType,
            'HCM-API-AK: '.$this->ak,
        ];

        $_headers = array_merge($_headers, $headers, $sk);

        $setOpt = [
            CURLOPT_URL             =>  $this->bulidUri($api),
            CURLOPT_HTTPHEADER      =>  $_headers,
            CURLOPT_RETURNTRANSFER  =>  true;
        ];

        if ('POST' == $type) {
            $setOpt[CURLOPT_POST] = true;

            $params = is_array($params)
            ? json_encode($params) : (string) $params;

            $setOpt[CURLOPT_POSTFIELDS] = $params;
        }

        curl_setopt_array($ch, $setOpt);

        $res    = curl_exec($ch);
        $errNo  = curl_errno($ch);
        $errMsg = curl_error($ch);

        curl_close($ch);

        K::M('tools/tool')->log(
            'The result of calling json api `'.$uri.'`: '
            .$res,
            __FILE__,
            __LINE__,
            'api_call'
        );

        return [
            'err' => $errNo,
            'msg' => $errMsg,
            'res' => json_decode($res, true),
        ];
    }
}