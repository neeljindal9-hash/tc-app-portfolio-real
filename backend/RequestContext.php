<?php
 class RequestContext {
    public $get, $post, $server, $session, $fromProxy, $dbconn, $redirect, $origin;
    public int $status;
    private $rawContentCallable;

    public function __construct($sR, $origin = "client_request") {
        $this->origin = $origin;
        if ($origin === "system_daemon") {
            // $sR would be null
            $this->get = [];
            $this->post = [];
            $this->server = [];
            $this->fromProxy = true;
            $this->server['SERVER_NAME'] = 'thecarve.app';
            $this->server['base_path'] = "https://thecarve.app";
            $this->rawContentCallable = null;
            $this->status = 200; // default
            
        } else {
            $this->get = $sR->get ?? [];
            $this->post = $sR->post ?? [];
            $this->server = $sR->server ?? [];
            $this->fromProxy = true;
            $this->server['REQUEST_URI'] = $sR->server['request_uri'];
            $this->server['REQUEST_METHOD'] = $sR->server['request_method'];
            $this->server['SERVER_NAME'] = 'thecarve.app';
            $this->server['base_path'] = "https://thecarve.app";
            $this->rawContentCallable = $sR->rawContent(...);
            $this->status = 200; // default
        }
    }

    public function rawContent() {
        if ($this->origin === "system_daemon") {
            error_log("CRITICAL: Incompatible content body method requested for system_daemon RequestContext object");
            return null;
        }
        return ($this->rawContentCallable)();
    }
}