<?php
/**
 * Logger Tracking
 *
 * @author     rock <rock_guo@hexu.org>
 * @copyright  Copyright (c) 2010-2014 hexu Org. (http://www.hexu.org )
 * @license    http://www.hexu.org/license/
 * @version    CVS: $Id: Logger.php,v 1.1 2010/07/23 06:50:11 rock_guo Exp $
 */

/** define application environment */
defined('_DNS_TRACKING')  || define('_DNS_TRACKING', 'mysql:host=localhost;dbname=test');
defined('_DNS_USERNAME')  || define('_DNS_USERNAME', 'root');
defined('_DNS_PASSWORD')  || define('_DNS_PASSWORD', '');
defined('TABLE_PREFIX') || define('TABLE_PREFIX', 'qq_');
/**
 * Tracking_Tracker
 *
 * @category   Tracking
 * @package    Tracking_Tracker
 */
class Tracking_Logger {

    /**
     * @var resource
     */
    private $_dbTracking = null;
    /**
     * Singleton instance
     *
     * Marked only as protected to allow extension of the class. To extend,
     * simply override {@link getInstance()}.
     *
     * @var Tracking_Logger
     */
    protected static $_instance = null;
    /**
     * Singleton instance
     *
     * @return Tracking_Logger
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    /**
     * Constructor
     * Tracking_Logger
     */
    public function __construct() {

        if(null == $this->_dbTracking){
            $trkDns = _DNS_TRACKING;
            //$trkDns = Tracking_Registry::get('dbTracking');
            $this->_dbTracking = $this->_getConnection($trkDns);
        }
    }

    /**
     * @param string $method
     * @param array $params
     * @return bool
     * @throws Exception
     */
    public function __call($method, $params = array()) {
        if(empty($params)) {
            throw new Exception("Error Info: {$method} params is empty.");
        }
        $table = TABLE_PREFIX . strtolower(substr($method, 3)) . '_log';

        $fields = $values = '';
        $binds = [];
        foreach (array_shift($params) as $key => $val) {
            $fields .= ',`' . $key . '`';
            $values .= ',?';
            $binds[] = addcslashes(str_replace("'", "''", $val), "\000\n\r\\\032");
        }
        $fields  = substr($fields, 1);
        $values  = substr($values, 1);
        $sth = $this->_dbTracking->prepare("INSERT INTO {$table} ( {$fields} ) VALUES ( {$values} )");
        if($sth->execute($binds)) {
            return true;
        } else {
            $errors = $sth->errorInfo();
            throw new Exception("Error Info: {$errors[2]}.");
            //error_log($errors[2]);
        }
    }

    /**
     * @desc 取最近一小时的IP访问量
     * @param $params
     * @return mixed
     */
    public function isExist($params)
    {
        $table = TABLE_PREFIX . "groups_log";
        $sth = $this->_dbTracking->prepare("SELECT COUNT(*) cnt FROM $table WHERE `detail_url` = ?");

        $sth->execute([$params['detail_url']]);
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        //error_log($currentTime . '====' . $startTime, 0);

        return !empty($result['cnt']) ? $result['cnt'] : 0;
    }
    /**
     * @desc get front end mysql connection
     *
     * @param null $dsn
     * @return bool|PDO
     */
    private function _getConnection($dsn = null) {
        //suppress error
        if (null == $dsn) {
            return FALSE;
        }
        $options = [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"];

//        if (!empty($dsn->charset)) {
//            $initCommand = "SET NAMES '" . $dsn->charset . "'";
//            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = $initCommand; // 1002 = PDO::MYSQL_ATTR_INIT_COMMAND
//        }
        // var_dump("mysql:host={$dsn->host};dbname={$dsn->dbname}", $dsn->username, $dsn->password, $options);exit;
        // $conn = new PDO("mysql:host={$dsn->host};dbname={$dsn->dbname}", $dsn->username, $dsn->password, $options);
        $conn = new PDO($dsn, _DNS_USERNAME, _DNS_PASSWORD, $options);
        return $conn;

    }
}
