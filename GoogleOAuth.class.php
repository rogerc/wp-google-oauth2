<?php

require_once 'src/Google_Client.php';
require_once 'src/contrib/Google_Oauth2Service.php';

class GoogleOAuth {

    private $client;
    private $oauth;
    private $accessToken;
    private $user;

    public static function validateConfig($config){
        $notOK = 5;
        if(is_array($config)){
            while(list($key,$value) = each($config)){
                switch($key){
                    case 'app_name':
                    case 'client_id':
                    case 'client_secret':
                    case 'redirect_url':
                    case 'developer_key':
                        $notOK--;
                        break;
                }
            }
        }
        return ($notOK == 0);
    }

    public function __construct($config){
        if(!self::validateConfig($config)){
            throw new Exception("Missing config");
        }
        $client = new Google_Client();
        $client->setApplicationName($config['app_name']);
        $client->setClientId($config['client_id']);
        $client->setClientSecret($config['client_secret']);
        $client->setRedirectUri($config['redirect_url']);
        $client->setDeveloperKey($config['developer_key']);
        $client->setApprovalPrompt($config['approval_prompt']);
        $client->setAccessType($config['access_type']);
        $this->client = $client;
        $this->oauth2 = new Google_Oauth2Service($client);
    }

    public function getAuthURL($scope,$state){
        if(trim(state)){
            $this->client->setState($state);
        }
        if(!trim($scope)){
            $scope = "https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile";
        }
        $this->client->setScopes($scope);
        return $this->client->createAuthUrl();
    }

    public function getAccessToken(){
        $this->accessToken = $this->client->getAccessToken();
        return $this->accessToken;
    }

    public function setAccessToken($token){
        $this->client->setAccessToken($token);
        $this->accessToken = $token;
    }

    public function revokeToken(){
        return $this->client->revokeToken();
    }

    public function authenticate($code){
        $token = $this->client->authenticate($code);
        if(!empty($token)){
            $this->setAccessToken($token);
            return $token;
        }
    }

    public function getUserInfo(){
        error_log("getting user info",3,"/tmp/google_oauth.debug.log");
        if(!is_array($this->user)){
            $this->user = $this->oauth2->userinfo->get();
        }
        return $this->user;
    }

    public function getEmailAddress(){
        if(!is_array($this->user)){
            $this->getUserInfo();
        }
        return filter_var($this->user['email'], FILTER_SANITIZE_EMAIL);
    }

    public function getID(){
        if(!is_array($this->user)){
            $this->getUserInfo();
        }
        return filter_var($this->user['id'], FILTER_SANITIZE_NUMBER_INT);
    }

    public function getUserDomain(){
        if(!is_array($this->user)){
            $this->getUserInfo();
        }
        $domain = $this->user['hd'];
        if(!$domain){
            $domain = substr($this->getEmailAddress(),strpos($this->getEmailAddress(),"@")+1);
        }
        return $domain;

    }

    private function getTokenVariable($var){
        $token = json_decode($this->accessToken, true);
        if(isset($token[$var])){
            return $token[$var];
        }
        return null;
    }

    public function getTokenCreatedTime(){
        return $this->getTokenVariable("created");
    }

    public function getTokenExpiresIn(){
        return $this->getTokenVariable("expires_in");
    }

    public function getRefreshToken(){
        error_log("Refresh TOKEN");
        error_log($this->getTokenVariable("refresh_token"));
        error_log($this->accessToken);
        return $this->getTokenVariable("refresh_token");
    }

    public function validateToken(){
        return !$this->client->isAccessTokenExpired();
    }

    public function refreshAccessToken($token){
        if(empty($token)){
            return false;
        }
        try{
            $this->client->refreshToken($token);
            $this->getAccessToken();
            return true;
        }catch(Google_AuthException $e){
            error_log($e);
            return false;
        }
    }
}