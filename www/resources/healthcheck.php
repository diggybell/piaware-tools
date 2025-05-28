<?php

include_once('../../lib/autoload.php');
include_once('../../lib/autoconfig.php');

use \DigTech\Logging\Logger as Logger;
use \DigTech\REST\SecureResource as SecureResource;

class healthcheck extends SecureResource
{
    public function __construct($request, $schema="digtech")
    {
        parent::__construct($request, $schema);

        $cfg = getGlobalConfiguration();
        $login = $cfg->getSection('db-piaware');
        $this->_conn->configure($login);

        if($this->_conn->connect())
        {
        }
        else
        {
            Logger::error("Unable to connect to database ($s)\n", $this->_conn->error());
        }
    }

    protected function isAuthorized()
    {
        $ret = false;

        $headers = getallheaders();
        if(isset($headers['Authorization']))
        {
            // get the user/password
            $credential = $headers['Authorization'];
            $credential = str_replace('Basic ', '', $credential);
            $credential = base64_decode($credential);
            list($user, $password) = explode(':', $credential);

            if($user == 'user' &&
               $password == 'password')
            {
                $ret = true;
                Logger::log("API Request Authorized for %s\n", $user);
            }
            else
            {
                Logger::log("API Request NOT Authorized for %s\n", $user);
            }
        }

        return $ret;
    }

    public function GET()
    {
        $data = [];

        $this->getSystemInfo($data);
        $this->getHTTPDInfo($data);
        $this->getDatabaseInfo($data);
        $this->getPHPInfo($data);
        return [ 'status' => 'Success', 'data' => $data ];
    }

    protected function getHTTPDInfo(&$data)
    {
        $data['httpd']['version'] = $_SERVER["SERVER_SOFTWARE"];
        $data['httpd']['vhostname'] = $_SERVER['SERVER_NAME'];
    }

    protected function getDatabaseInfo(&$data)
    {
        $sql = "SELECT VERSION() AS version, CURRENT_USER() AS user;";
        $res = $this->_conn->query($sql);
        if($res)
        {
            $row = $this->_conn->fetch($res);
            if($row)
            {
                $data['database']['version'] = $row['version'];
                $data['database']['user']    = $row['user'];
            }
            $this->_conn->freeResult($res);
        }
    }

    protected function getPHPInfo(&$data)
    {
        $data['php']['version'] = phpversion();
        $data['php']['sapi']    = php_sapi_name();
        $data['php']['memory_limit'] = ini_get('memory_limit');
        $data['php']['memory_used']  = number_format(memory_get_usage(), 0);
    }

    function getSystemInfo(&$data)
    {
        $parms = 
        [ 
           'a' => 'sys_all',
           's' => 'sys_os',
           'n' => 'sys_host',
           'r' => 'sys_release',
           'v' => 'sys_version',
           'm' => 'sys_architecture',
        ];

        $data['sys']['datetime'] = date('Y-m-d H:i:s');
        $data['sys']['hostname'] = gethostname();
        $data['sys']['hostip']   = gethostbyname(gethostname());

        foreach($parms as $parm => $desc)
        {
            list($class, $item) = explode('_', $desc);  
            $data[$class][$item] = php_uname($parm);
        }

        $data['sys']['disk_total'] = number_format(disk_total_space('/'), 0);
        $data['sys']['disk_free']  = number_format(disk_free_space('/'), 0);

        $data['sys']['environment'] = getGlobalConfiguration()->getEnvironment();
        $data['sys']['runstate']    = getGlobalConfiguration()->getRunState();
    }
}

