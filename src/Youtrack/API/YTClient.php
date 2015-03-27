<?php namespace Youtrack\API;


class YTClient {
    /**
     * YouTrack Site url
     *
     * @var string
     */
    protected $url;

    private static $api = '/rest';
    private static $signInPageUrl = '/user/login';
    /**
     * YouTrack Username
     *
     * @var string
     */
    protected $username;
    /**
     * JSESSIONID cookie
     *
     * @var mixed
     */
    private $jsessionid;

    /**
     * Default cURL Options
     *
     * @var array
     */
    private static $curlOptions = array(
        CURLOPT_SSL_VERIFYPEER => false,
//        CURLOPT_SSLVERSION => 4,
//        CURLOPT_SSL_CIPHER_LIST => 'ECDHE-RSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-RSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:AES256-GCM-SHA384:AES128-GCM-SHA256:AES256-SHA256:AES256-SHA:AES128-SHA256:AES128-SHA:DES-CBC3-SHA',
        CURLOPT_RETURNTRANSFER => true
    );

    public $issues;
    private $format;

    /**
     * Class constructor
     *
     * @param string $url
     * @param mixed $username
     *
     * @throws \Exception
     */
    public function __construct($url, $username, $format = 'xml')
    {
        if (!function_exists('curl_init')) {
            throw new \Exception('CURL module not available! This client requires CURL. See http://php.net/manual/en/book.curl.php');
        }
        $this->url = trim($url, '/'); //remove trailing slashes
        $this->username = $username;
        $this->format = $format;
        $this->issues = new YTIssues($this);
    }

    /**
     * SPO Set Auth method and authenticate accordingly
     *
     * @param $method
     * @param $value
     */
    public function setAuth($method, $value)
    {
        switch ($method) {
            case 'password':
                $this->signIn($value);
                break;
        }
    }

    /**
     * SPO sign-in
     *
     * @param mixed $password
     *
     * @throws \Exception
     */
    public function signIn($password)
    {
        $ch = curl_init();
        $formdata = array(
            "login" => $this->username,
            "password" => $password
        );
        $postData = http_build_query($formdata);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $url = $this->url.static::$api.static::$signInPageUrl;
        curl_setopt_array($ch, static::$curlOptions);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $result = curl_exec($ch);
        if ($result === false) {
            throw new \Exception(curl_error($ch));
        }
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($result, 0, $header_size);
        $body = substr($result, $header_size);
        curl_close($ch);

        if($body === '<login>ok</login>'){
            $this->saveAuthCookie($header);
        }else{
            throw new \Exception($result);
        }
    }

    /**
     * Send a request to the Youtrack REST API
     *
     * @param $path
     * @param $verb
     * @param null $data
     * @param array $headers
     *
     * @return array
     * @throws \Exception
     */
    public function apiRequest($path, $verb, $data = null, $headers = []){
        $ch = curl_init();
        $headers['Cookie'] = 'JSESSIONID=' . $this->jsessionid;
        if($this->format == 'json'){
            $headers['Accept'] = 'application/json';
        }
        switch($verb) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                if($data){
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    $headers['Content-length'] = strlen($data);
                }else{
                    $headers['Content-length'] = 0;
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
//                $headers['X-Http-Method'] = 'PUT';
                if($data){
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    $headers['Content-length'] = strlen($data);
                }else{
                    $headers['Content-length'] = 0;
                }
                break;
            default:
                // nothing
                break;
        }

        $curl_header = [];
        foreach ($headers as $header => $value) {
            $curl_header[] = $header.': '.$value;
        }

        $url = $this->url.static::$api.$path;

        curl_setopt_array($ch, static::$curlOptions);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_header);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $result = curl_exec($ch);
        if ($result === false) {
            throw new \Exception(curl_error($ch));
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $result];
    }

    /**
     * Save the Youtrack auth cookie
     *
     * @param mixed $header
     */
    private function saveAuthCookie($header)
    {
        $cookies = $this->cookie_parse($header);
        $this->jsessionid = $cookies['JSESSIONID'];
    }

    /**
     * Parse cookies
     *
     * @param mixed $header
     *
     * @return mixed
     */
    private function cookie_parse($header)
    {
        $headerLines = explode("\r\n", $header);
        $cookies = array();
        foreach ($headerLines as $line) {
            if (preg_match('/^Set-Cookie: /i', $line)) {
                $line = preg_replace('/^Set-Cookie: /i', '', trim($line));
                $csplit = explode(';', $line);
                $cinfo = explode('=', $csplit[0], 2);
                $cookies[$cinfo[0]] = $cinfo[1];
            }
        }

        return $cookies;
    }
}