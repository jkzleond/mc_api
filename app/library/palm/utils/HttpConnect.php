<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-3-24
 * Time: 下午2:59
 */

namespace Palm\Utils;

class HttpConnect {

    protected $_response_header = null;
    protected $_response_body = null;
    protected $_status = null;
    protected $_response = null;

    public function get($url, $header=array(), $cookie='')
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER  => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_COOKIE => $cookie
        ));
        $this->_response = curl_exec($ch);
        $this->_make_header_body();
        curl_close($ch);
        return $this;
    }

    public function post($url, $data='', $header=array(), $cookie='')
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER  => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_COOKIE => $cookie
        ));
        $this->_response = curl_exec($ch);
        $this->_make_header_body();
        curl_close($ch);
        return $this;
    }

    public function getRawResponse()
    {
        return $this->_response;
    }

    public function getResponseHeader()
    {
        return $this->_response_header;
    }

    public function getResponseBody()
    {
        return $this->_response_body;
    }

    public function getResponseStatus()
    {
        return $this->_status;
    }

    protected function _make_header_body()
    {
        if(!$this->_response) return;
        $parts = explode("\r\n\r\n", $this->_response);
        $this->_response_header = $parts[0];
        $this->_response_body = $parts[1];
        preg_match('/HTTP.* (?P<status>\d{3}) .*/Ui', $this->_response_header, $match);
        $this->_status = $match['status'];
    }




}