<?php

namespace AmoClient;

/**
 * Class HttpClient
 * @package AmoClient
 */
class HttpClient
{
    private $basicAuth = [];

    private $headers = [];

    private $opts = [];

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    public function addHeader(string $headerName, string $headerValue)
    {
        $this->headers[] = \sprintf('%s: %s', $headerName, $headerValue);

        return $this;
    }

    public function addBasicAuth(string $user, string $password)
    {
        $this->basicAuth = [$user, $password];

        return $this;
    }

    private function applyBasicAuth($curl)
    {
        if (!empty($this->basicAuth)) {
            curl_setopt($curl, CURLOPT_USERPWD, \vsprintf('%s:%s', $this->basicAuth));
        }
    }

    /**
     * @param $url
     * @return resource
     */
    private function buildRequest($url)
    {
        $request = curl_init($url);
        curl_setopt($request, CURLOPT_URL, $url);
        curl_setopt($request, CURLOPT_HTTPHEADER, $this->headers);
        $this->applyBasicAuth($request);
        curl_setopt($request, CURLOPT_TIMEOUT, 30);
        curl_setopt($request, CURLOPT_HEADER,false);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);

        return $request;
    }

    /**
     * @param $option
     * @param $value
     * @return mixed
     */
    public function addOpt($option, $value)
    {
        $this->opts[] = ['option' => $option, 'value' => $value];

        return $this;
    }

    public function cleanOpts()
    {
        $this->opts = [];
    }

    /**
     * @param $request
     */
    protected function applyOpts($request)
    {
        foreach ($this->opts as $opt) {
            curl_setopt($request, $opt['option'], $opt['value']);
        }
    }

    protected function prepareUrl(string $url, array $params = []): string
    {
        $qStr = '';

        if (!empty($params)) {
            $qStr = \http_build_query($params);
        }

        return $url.($qStr ? ('?'.$qStr) : '');
    }

    /**
     * @param string $url
     * @param array $params
     * @param bool $assoc
     * @return array|bool|mixed|object
     * @throws \Exception
     */
    public function sendGet(string $url, array $params = [], bool $assoc = true)
    {
        try {
            return $this->execute(
                $this->buildRequest($this->prepareUrl($url, $params)),
                $assoc
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $url
     * @param array $body
     * @param bool $assoc
     * @return array|mixed|object|bool
     * @throws \Exception
     */
    public function sendPost(string $url, array $body =[], bool $assoc = true)
    {
        try {
            $request = $this->buildRequest($url);
            \curl_setopt($request, CURLOPT_POST, 1);

            if (!empty($body)) {
                $jsonBody = \json_encode($body);
                \curl_setopt($request, CURLOPT_POSTFIELDS, $jsonBody);
            }

            return $this->execute($request, $assoc);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $request
     * @param bool $assoc
     * @return mixed
     * @throws \Exception
     */
    private function execute($request, bool $assoc)
    {
        $response = \curl_exec($request);
        $error = \curl_error($request);

        \curl_close($request);
        if (false === $response) {
            throw new \Exception("cURL error " . $error);
        }

        return \json_decode($response, $assoc);
    }
}