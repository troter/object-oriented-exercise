<?php
class AccessURL {
    protected $url;
    function __construct() {
        $this->url = $this->build_access_url();
    }

    function url() {
        return $this->url;
    }

    private function is_https() {
        return array_key_exists("HTTPS", $_SERVER) && $_SERVER["HTTPS"] == "ON";
    }

    private function is_default_port_for_protocol($protocol, $port) {
        if ($protocol === "https" && $port == 443)
            return true;
        if ($protocol === "http" && $port == 80)
            return true;
        return false;
    }

    private function build_access_url() {
        $protocol = $this->is_https() ? "https" : "http";
        $server_name = $_SERVER['SERVER_NAME'];
        $server_port = $_SERVER['SERVER_PORT'];
        $script_name = $_SERVER['SCRIPT_NAME'];

        $url = "${protocol}://${server_name}";
        if (! $this->is_default_port_for_protocol($protocol, $port))
            $url = "${url}:${server_port}";
        $url = "${url}${script_name}";
        return $url;
    }
}
