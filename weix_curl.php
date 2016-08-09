<?php

class WeixCurl
{
    public static function curl($url, $method = 'GET', $params = array(), $headers = array(), &$httpcode = null, &$httpinfo = null)
    {/*{{{*/
        $ret = array();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, 'Buit-in WEB API');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, TRUE);
                if (! empty($params)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (! empty($params)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                }
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            default:
                curl_setopt($ch, CURLOPT_POST, FALSE);
                if (! empty($params)) {
                    $url = $url . "?" . http_build_query($params);
                }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        $body = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpinfo = curl_getinfo($ch);

        $ret = [
            'body' => $body,
            'httpcode' => $httpcode,
            'httpinfo' => $httpinfo,
        ];

        curl_close($ch);

        return $ret;
    }/*}}}*/

}
