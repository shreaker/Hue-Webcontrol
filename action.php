<?php 
//////////////////////////////////////////////////////////////////////////////////////////////////
/// Author : Michael M. https://github.com/shreaker
/// Date   : 7.9.2016
/// License: GPL3
/// Project: Hue Webcontrol
/// Desc.  : Control Phillips Hue lamps with a responsive webpage from any device in a simple way.
/// Info   : More info at https://github.com/shreaker/Hue-Webcontrol
//////////////////////////////////////////////////////////////////////////////////////////////////
/*
//Uncomment for debugging only!
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
*/

require_once __DIR__ . '/Phue/vendor/autoload.php';

//Constants
define("HTTP_OK", 200);
define("HTTP_BAD_REQUEST", 400);
define("URL_HOME", "http://192.168.2.50/index.html");
define("URL_ERROR_MSG", "http://192.168.2.50/errorMessage.html");

///////////////////////////////////////////////////////////////////////////////////////////
$userIO = new UserIO();
$userCmd = $userIO->getUserCmd('POST');

$hueWebControl = new HueWebControl();
$ret = $hueWebControl->computeUserCmdLight($userCmd[light]);

if($ret == HTTP_OK){
    $userIO->movePage(HTTP_OK, URL_HOME);
}else{
    $userIO->movePage(HTTP_BAD_REQUEST, URL_ERROR_MSG);
}

///////////////////////////////////////////////////////////////////////////////////////////
class UserIO{
    public function __construct() {
        //do nothing.
    }

    public function getUserCmd($type){
           return $this->readFormToArray($type);
    }

    private function readFormToArray($type)
    {
        if($type == 'REQUEST')
            $data = $_REQUEST;
        elseif($type == 'POST')
            $data = $_POST;
        elseif($type == 'GET')
            $data = $_GET;

        $retArray = array();
        foreach($data as $key => $value)
            $retArray[$this->validateUserInput($key)] = $this->validateUserInput($value);
            
        return $retArray;   
    }

    private function validateUserInput($string) { 
        return htmlspecialchars(stripslashes(trim($string))); 
    }

    public function movePage($num,$url){
       static $http = array (
           100 => "HTTP/1.1 100 Continue",
           101 => "HTTP/1.1 101 Switching Protocols",
           200 => "HTTP/1.1 200 OK",
           201 => "HTTP/1.1 201 Created",
           202 => "HTTP/1.1 202 Accepted",
           203 => "HTTP/1.1 203 Non-Authoritative Information",
           204 => "HTTP/1.1 204 No Content",
           205 => "HTTP/1.1 205 Reset Content",
           206 => "HTTP/1.1 206 Partial Content",
           300 => "HTTP/1.1 300 Multiple Choices",
           301 => "HTTP/1.1 301 Moved Permanently",
           302 => "HTTP/1.1 302 Found",
           303 => "HTTP/1.1 303 See Other",
           304 => "HTTP/1.1 304 Not Modified",
           305 => "HTTP/1.1 305 Use Proxy",
           307 => "HTTP/1.1 307 Temporary Redirect",
           400 => "HTTP/1.1 400 Bad Request",
           401 => "HTTP/1.1 401 Unauthorized",
           402 => "HTTP/1.1 402 Payment Required",
           403 => "HTTP/1.1 403 Forbidden",
           404 => "HTTP/1.1 404 Not Found",
           405 => "HTTP/1.1 405 Method Not Allowed",
           406 => "HTTP/1.1 406 Not Acceptable",
           407 => "HTTP/1.1 407 Proxy Authentication Required",
           408 => "HTTP/1.1 408 Request Time-out",
           409 => "HTTP/1.1 409 Conflict",
           410 => "HTTP/1.1 410 Gone",
           411 => "HTTP/1.1 411 Length Required",
           412 => "HTTP/1.1 412 Precondition Failed",
           413 => "HTTP/1.1 413 Request Entity Too Large",
           414 => "HTTP/1.1 414 Request-URI Too Large",
           415 => "HTTP/1.1 415 Unsupported Media Type",
           416 => "HTTP/1.1 416 Requested range not satisfiable",
           417 => "HTTP/1.1 417 Expectation Failed",
           500 => "HTTP/1.1 500 Internal Server Error",
           501 => "HTTP/1.1 501 Not Implemented",
           502 => "HTTP/1.1 502 Bad Gateway",
           503 => "HTTP/1.1 503 Service Unavailable",
           504 => "HTTP/1.1 504 Gateway Time-out"
       );
       header($http[$num]);
       header ("Location: $url");
    }
}

///////////////////////////////////////////////////////////////////////////////////////////
class HueWebControl{

    //Defines
    private $HUE_BRIDGE_IP = "192.168.2.200";
    private $HUE_BRIDGE_USER_ID = "PUT YOUR OWN KEY HERE"; //insert your own key here!
    private $LIGHT_LIVINGROOM_1 = 1;
    private $LIGHT_BEDROOM_1 = 2;

    private $COLOR_RED = 65280;
    private $COLOR_GREEN = 25500;
    private $COLOR_BLUE = 46920;
    private $COLOR_PINK = 56100;
    private $COLOR_ORANGE = 9000;
    private $BRIGHTNESS_MAX = 255;
    private $SATURATION_MAX = 255;


    private $client;
    private $lights;

    public function __construct() {
        $this->initThisClient();
        $this->initLights(); 
    }

    public function computeUserCmdLight($userCmd){   
        $ret = HTTP_OK;
        switch ($userCmd) {
            case "off":
                $this->turnOffAllLights();
                break;
            case "red":
                $this->turnOnAllLights($this->BRIGHTNESS_MAX, $this->SATURATION_MAX, $this->COLOR_RED);
                break;   
            case "green":
                $this->turnOnAllLights($this->BRIGHTNESS_MAX, $this->SATURATION_MAX, $this->COLOR_GREEN);
                break; 
            case "blue":
                $this->turnOnAllLights($this->BRIGHTNESS_MAX, $this->SATURATION_MAX, $this->COLOR_BLUE);
                break; 
            case "pink":
                $this->turnOnAllLights($this->BRIGHTNESS_MAX, $this->SATURATION_MAX, $this->COLOR_PINK);
                break; 
            case "night":
                $this->turnOnLight($this->lights[$this->LIGHT_BEDROOM_1], $this->BRIGHTNESS_MAX/4, $this->SATURATION_MAX, $this->COLOR_BLUE);
                $this->turnOffLight($this->lights[$this->LIGHT_LIVINGROOM_1]);
                break; 
            case "white":
                $this->turnOnAllLights($this->BRIGHTNESS_MAX, 0, $this->COLOR_ORANGE);
                break; 
            case "orange":
                $this->turnOnAllLights($this->BRIGHTNESS_MAX, $this->SATURATION_MAX, $this->COLOR_ORANGE);
                break; 
            default:
                 $ret = HTTP_BAD_REQUEST;        
        }
        return $ret;
    }

    private function initThisClient(){
        $this->client = new \Phue\Client($this->HUE_BRIDGE_IP, $this->HUE_BRIDGE_USER_ID);
    }

    private function initLights(){
        $this->lights = $this->client->getLights(); 
    }

    private function turnOffAllLights(){
            foreach ($this->lights as $light) {
                $this->turnOffLight($light);
            }
    }

    private function turnOffLight($light){
                $light->setOn(false);
    }


    private function turnOnAllLights($brightness, $saturation, $hue){
            foreach ($this->lights as $light) {
                $this->turnOnLight($light, $brightness, $saturation, $hue);
            }
    }

    private function turnOnLight($light, $brightness, $saturation, $hue){
            $light->setOn(true);
            $light->setBrightness($brightness);
            $light->setSaturation($saturation);
            $light->setHue($hue);
    }
    
}


?>
