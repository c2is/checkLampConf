#!/usr/bin/php
<?php
/*
 * Check server configs
 * First run chmod +x checkconf.php
 */
$processing  = new Container();
$processing -> params["apache.version"] = "2.2" ;
$processing -> params["php.version"] = "5.3" ;
$processing -> load() ;

class System {
    var $executeDisplay = false;
    // set type of value which will be set in the reference, false : the return of exec function, true : its results var
    var $executeGetResults = false;
    var $showFormat;
    public function __construct(){
        $this -> commandsCheckList = array("lsb_release","cat","ls","cut","whoami","groups","php");
        $this -> showFormat = "\033[0;33m%s\033[0m\n";
        $this -> showAlertFormat = "\033[0;31m%s\033[0m\n";
        $this -> checkExeAndFiles();
        $this->Execute("lsb_release -i | cut -d: -f2",$sysId,"Checking system id");
        $this->Execute("lsb_release -r | cut -d: -f2",$sysRelease,"Checking system release");
        $this -> sysId = $sysId;
        $this -> sysRelease = $sysRelease;
    }
    public function Execute($cmd,&$res,$notice=null)
    {
      $results = array();
      $status = 0;
      if($this -> executeGetResults){
          exec($cmd, $res, $status);
      }
       else{
           $res = trim(exec($cmd, $results, $status));
       }

       if($notice){
           $this -> show($notice." : ".$res);
       }
        if($this -> executeDisplay){
            foreach ($results as  $line){
                $this -> show($line);
            }
        }

      return $status;
    }
    public function show($str,$alert=null){
        if($alert){
            printf($this -> showAlertFormat,$str);
        }
        else{
            printf($this -> showFormat,$str);
        }
    }
    /*
     * Check if commands and files used by this script are available
     */
    public function checkExeAndFiles(){
        foreach($this -> commandsCheckList as $command){
            $this -> Execute("which ". $command,$res);
            if($res == ""){
                $this -> show("Command ".$command." unavailable",true);
                die();
            }
        }
    }
}
class Apache{
    var $system;
    public function __construct($system,$apacheVersionExpected){
        $this -> system = $system;
        $this -> apacheVersionExpected = $apacheVersionExpected;
        switch($this -> system -> sysId){
            case "Ubuntu":
            case "Debian":
                $tmp = array();
                $system -> Execute("cat /etc/apache2/envvars | grep APACHE_RUN_USER | sed -e 's/.*=//'",$this -> runUser,"Checking Apache Run User");
                $system -> Execute("cat /etc/apache2/envvars | grep APACHE_RUN_GROUP | sed -e 's/.*=//'",$this -> runGroup,"Checking Apache Run Group");
                $system -> executeGetResults = true;
                $system -> Execute("ps -ef | grep apache",$tmp);
                $tmp = $tmp[0];
                $tmp = explode(" ",$tmp);
                foreach($tmp as $col){
                    if(strpos($col,"/apache") !== false){
                        $this -> binPath = $col;
                    }
                }
                $tmp = array();
                $system -> Execute($this -> binPath." -v",$tmp);
                $tmp = $tmp[0];
                $tmp = explode(":",$tmp);
                $tmp = trim($tmp[1]);
                preg_match("/apache\/([^ ]*)/i",$tmp,$match);
                $this -> version = $match[1];
                if(! preg_match("/^".$this -> apacheVersionExpected."/i",$this -> version)){
                    $system -> show("Checking Apache Version: mismatch, ".$this -> apacheVersionExpected." expected and ".$this -> version." installed",true);
                }
                else{
                    $system -> show("Checking Apache Version: Ok, ".$this -> apacheVersionExpected." expected and ".$this -> version." installed");
                }
                $system -> executeGetResults = false;
            break;
        }

    }
}
class Php{
    var $system;
    public function __construct($system,$phpVersionMin){
       $this -> system = $system;
       $this -> phpVersionMin = $phpVersionMin;
        $this -> version = phpversion();

        if(! preg_match("/^".$this -> phpVersionMin."/i",$this -> version)){
            $system -> show("Checking Php Version: mismatch, ".$this -> phpVersionMin." expected and ".$this -> version." installed",true);
        }
        else{
            $system -> show("Checking Php Version: Ok, ".$this -> phpVersionMin." expected and ".$this -> version." installed");
        }

    }
}
class Directories {
    var $apache;
    var $system;
    public function __construct($system,$apache){
        $this -> system = $system;
        $this -> apache = $apache;
        $system -> Execute("ls -ld ".__DIR__." | cut -d\" \" -f3",$this -> dirOwner,"Checking Dir Owner");
        $system -> Execute("ls -ld ".__DIR__." | cut -d\" \" -f3",$this -> dirGroup,"Checking Dir Group");
        $system -> Execute("whoami",$this -> user,"Checking Whoami");
        if(!is_writable(__DIR__)){
            $this -> system -> show("Checking if ".__DIR__." is writable : false",true);
        }
        else{
            $this -> system -> show("Checking if ".__DIR__." is writable : true");
        }
    }
}

class Users {
    var $apache;
    var $system;
    public function __construct($system,$apache,$directories){
        $this -> system = $system;
        $this -> apache = $apache;
        $this -> directories = $directories;
        $system -> Execute("groups ".$this -> directories -> user." | cut -d: -f2",$this -> userGroups,"Checking Groups for user ".$this -> directories -> user);
        $system -> Execute("groups ".$this -> apache -> runUser." | cut -d: -f2",$this -> apacheGroups,"Checking Groups for user ".$this -> apache -> runUser);
        $tmp = explode(" ",$this -> apacheGroups);
        $this -> apachePrimaryGroup = $tmp[0];
        $tmp = explode(" ",$this -> userGroups);
        $this -> userPrimaryGroup = $tmp[0];
        $ok = true;
        if(! in_array($this -> apachePrimaryGroup,explode(" ",$this -> userGroups))){
            $ok = false;
            $this -> system -> show("user ".$this -> directories -> user." doesn't belong to ".$this -> apachePrimaryGroup."'s group",true);
        }
        if(! in_array($this -> userPrimaryGroup,explode(" ",$this -> apacheGroups))){
            $ok = false;
            $this -> system -> show("user ".$this -> apache -> runUser." doesn't belong to ".$this -> userPrimaryGroup."'s group",true);
        }
        if($ok){
            $this -> system -> show("groups definitions are Ok for users ".$this -> directories -> user." and ".$this -> apache -> runUser);
        }
    }
}
class Container {
    var $services = array();
    var $params = array();
    function __construct(){
        $this -> params["apache.version"] = "2";
        $this -> params["php.version"] = "5.3";
        $this->services["system"] = function($c){
            static $instance;
            if (!isset($instance)){
                $instance = new System;
            }
            return $instance;
        };
        $this->services["apache"] = function($c){
            static $instance;
            if (!isset($instance)){
                $instance = new Apache($c->services["system"]($c),$c -> params["apache.version"]);
            }
            return $instance;

        };
        $this->services["php"] = function($c){
            static $instance;
            if (!isset($instance)){
                $instance = new Php($c->services["system"]($c),$c -> params["php.version"]);
            }
            return $instance;

        };
        $this->services["directories"] = function($c){
            static $instance;
            if (!isset($instance)){
               $instance = new Directories($c->services["system"]($c),$c->services["apache"]($c));
            }
            return $instance;
        };
        $this->services["users"] = function($c){
            static $instance;
           if (!isset($instance)){
              $instance = new Users($c->services["system"]($c),$c->services["apache"]($c),$c->services["directories"]($c));
           }
           return $instance;

        };
    }
    function __get($k){
        return $this -> services[$k]($this);

    }
    function load(){
        $this -> users;
        $this -> php;
    }

}

