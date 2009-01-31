<?php # -*- coding: utf-8 -*- 
# DBA handler
# Written by Balazs Nagy <js@js.hu>
# Version: 2005-02-09
#
# Call it as DBA::singleton($dsn), because only assures that already opened
# connections aren''t opened again, and it allows use of implicit closes

$GLOBALS["__databases"] = false;
$GLOBALS["confdefs"]["dba_default_persistency"] = true;
$GLOBALS["debug"] = false;

function debug_log($str) {
	if (isset($GLOBALS["debug"]) && $GLOBALS["debug"] === true)
		error_log($str);
}

class DBA_DSN
{
    private $dsn;
    private $query;

    function __construct(string $dsn) {
        list($this->dsn, $this->query) = parse($dsn);
    }

    /**
     * $dsn = "<driver>://<username>:<password>@<host>:<port>/<database>?<query>"
     */
    private function parse(string $dsn) {
        $query = "";
        if (strpos('\?', $dsn) !== false)
            list($dsn, $query) = split('\?', $dsn, 2);
        $parsed_query = $this->parse_str($query);
        $parsed_dsn   = $this->parse_dsn($dsn);
        return array($parsed_query, $parsed_dsn);
    }

    private function parse_str(string $query) {
        parse_str($query, $parsed_query);
        $parsed_query['mode'] = new DBA_Option_OpenMode($parsed_query['mode']);
        $parsed_query['onopen'] = new DBA_Option_OnOpenHandler($parsed_query['onopen']);
        $parsed_query['persistent'] = new DBA_Option_Persistent($parsed_query['persistent']);
        return $parsed_query;
    }

    private function parse_dsn(string $dsn) {
        $parsed_query = array(
            'scheme'   => '',
            'user'     => '',
            'password' => '',
            'host'     => '',
            'port'     => '',
            'database' => '',
        );

        $parsed_query['scheme'] = new DBA_DSN_Scheme($parsed_query['scheme']);
        $parsed_query['user'] = new DBA_DSN_User($parsed_query['user']);
        $parsed_query['password'] = new DBA_DSN_Password($parsed_query['password']);
        $parsed_query['host'] = new DBA_DSN_Host($parsed_query['host']);
        $parsed_query['port'] = new DBA_DSN_Port($parsed_query['port']);
        $parsed_query['database'] = new DBA_DSN_Database($parsed_query['database']);
        return $parsed_query;
    }
}

class DBA_DSN_Scheme {
    private $scheme;
    function __construct(string $scheme) {
        $this->scheme = $scheme;
    }
    function value() {
        return $this->scheme;
    }
    function has_dba_handler() {
        $handler_name = $this->scheme;
        return function_exists("dba_handlers")
            && in_array($handler_name, dba_handlers());
    }
}

class DBA_DSN_User {
    private $user;
    function __construct(string $user) {
        $this->user = $user;
    }
    function value() {
        return $this->user;
    }
}

class DBA_DSN_Password {
    private $password;
    function __construct(string $password) {
        $this->password = $password;
    }
    function value() {
        return $this->password;
    }
}

class DBA_DSN_Host {
    private $host;
    function __construct(string $host) {
        $this->host = $host;
    }
    function value() {
        return $this->host;
    }
}

class DBA_DSN_Port {
    private $port;
    function __construct(string $port) {
        $this->port = $port;
    }
    function value() {
        return $this->port;
    }
}

class DBA_DSN_Database {
    private $database;
    function __construct(string $database) {
        $this->database = $database;
    }
    function value() {
        return $this->database;
    }
    function realpath() {
        return "";
    }
}

class DBA_Option {
    
}

class DBA_Option_OpenMode {
    private $mode;
    function __construct(string $mode) {
        $this->mode = $mode;
    }
    function is_create() { return $this->mode == "c"; }
    function is_readonly() { return $this->mode == "r"; }
    function is_readwrite_or_create() { return $this->mode == "w"; }
    function is_unknown() {
        return !($this->is_create() ||
                 $this->is_readonly() ||
                 $this->is_readwrite_or_create());
    }
}

class DBA_Option_OnOpenHandler {
    private $handler_function_name;
    function __construct(string $handler_function_name) {
        $this->handler_function_name = $handler_function_name;
    }
    function call() {
        if (function_exists($this->handler_function_name))
            call_user_func($this->handler_function_name);
    }
}

class DBA_Option_Persistent {
    private $condition;
    function __construct($condition) {
        $this->condition = $condition;
    }
    function is_enable() {
        return preg_match("true|1|on|enabled", $condition) === 1;
    }
}

class DBA
{
	var $dsn;
	var $info;
	var $res;
	var $opt;
	var $handler;

	/** Creates a new DBA object defined by dsn.
	 *  It assures that the same DBA object is returned to the same dsn''s
	 *  @param $dsn text Data Source Name: type:///file/opt=val&opt2=val2...
	 *    Each file can be relative to datadir.
	 *    Valid options:
	 *      persistent=true|1|on|enabled: enables persistency. Any others disables
	 *      onopen=functionname: calls function if new database is created
	 *      mode=c|r|w: open mode. c-create new, r-readonly, w-readwrite or create
	 */
	function &singleton($dsn)
	{
		$dbs = &$GLOBALS["__databases"];
		if (isset($dbs[$dsn])) {
			debug_log("DBA::singleton: get old db as $dsn");
			$that = &$dbs[$dsn];
		} else {
			$that = new DBA($dsn);
			$dbs[$dsn] = &$that;
			debug_log("DBA::singleton: create new db as $dsn");
		}
		return $that;
	}

	function closeall()
	{
		$dbs = &$GLOBALS["__databases"];
		if (is_array($dbs) && count($dbs)) foreach ($dbs as $db)
			$db->close();
	}

	# quick and dirty DSN parser
	function __parseDSN($dsn)
	{
		$ret = array(
			"scheme" => "",
			"path" => "",
			"query" => ""
		); 
		$off = strpos($dsn, "://");
		if ($off !== false)
			$ret["scheme"] = substr($dsn, 0, $off);
		$off += 3;
		$off2 = strpos($dsn, "/", $off);
		if ($off2 !== false)
			$off = $off2;
		$off += 1;
		$off2 = strpos($dsn, "?", $off);
		if ($off2 !== false) {
			$ret["path"] = substr($dsn, $off, $off2-$off);
			$ret["query"] = substr($dsn, $off2+1);
		} else
			$ret["path"] = substr($dsn, $off);
		return $ret;
	}

	function DBA($dsn)
	{
        // 必要ない?
		$this->dsn = $dsn;
		$info = DBA::__parseDSN($dsn);
		if (!isset($info["scheme"]))
			return false;
		$this->info = $info;
		$this->res = false;
		$this->handler = $info["scheme"];
		if (!function_exists("dba_handlers")
		 || !in_array($this->handler, dba_handlers()))
			$this->handler = false;
		if (strlen($info["query"]))
			parse_str($info["query"], $this->opt);
	}

	function &getOpt($opt)
	{
		if (!$this || !is_a($this, "DBA") || $this->res !== false)
			return false;
		$ret =& $this->opt[$opt];
		if (isset($ret))
			return $ret;
		return null;
	}

	function isOpt($opt, $val)
	{
		$ret =& getOpt($opt);
		if (is_bool($val))
			return $ret === $val;
		return $ret == $val;
	}

	function open()
	{
		global $conf;

		if (!$this || !is_a($this, "DBA"))
			return false;
		if ($this->res !== false)
			return true;
		$fn = rpath($conf["datadir"], $this->info['path']);
		if ($this->isOpt("mode", "c"))
			$mode = "n";
		elseif ($this->isOpt("mode", "r")) {
			if (!file_exists($fn))
				return false;
			$mode = "r";
		} else {
			if (file_exists($fn))
				$mode = "w";
			else {
				$mode = "n";
				if (function_exists($this->getOpt("onopen")))
					call_user_func($this->getOpt("onopen"));
			}
		}
		if ($this->handler !== false) {
			$persistent =& $this->getOpt("persistent");
			if (!isset($persistent))
				$persistent =& $conf["dba_default_persistency"];
			else
				$persistent = strtolower($persistent);
			switch ($persistent) {
			case true:
			case "1":
			case "on":
			case "true":
			case "enabled":
				$caller = "dba_popen";
				break;
			default:
				$caller = "dba_open";
			}
			$this->res = $caller($fn, $mode, $this->handler);
			debug_log("$caller($fn, $mode, $this->handler) = $this->res");
			return is_resource($this->res);
		}
		if (!file_exists($fn) && !is_file($fn)
			&& !is_readable($fn) && !touch($fn))
			return false;
		$mode = !!strstr($mode, "w");
		$this->res = $fn;
		$this->cache = array(
			"mode" => $mode,
			"sync" => true,
			"data" => @unserialize(file_get_contents($fn))
		);
		return true;
	}

	function close()
	{
		if (!$this || !is_a($this, "DBA") || $this->res === false)
			return false;
		if ($this->handler) {
			dba_close($this->res);
			debug_log("dba_close($this->res)");
		} else {
			$this->sync();
			$this->cache = false;
		}
		$this->res = false;
	}

	function first()
	{
		if (!$this || !is_a($this, "DBA"))
			return false;
		if (!$this->open())
			return false;
		if ($this->handler) {
			$key = dba_firstkey($this->res);
			debug_log("dba_firstkey($this->res)");
			return $key;
		}
		if (!is_array($this->cache["data"]) || !count($this->cache["data"]))
			return false;
		if (reset($this->cache["data"]) === false)
			return false;
		return key($this->cache["data"]);
	}

	function next()
	{
		if (!$this || !is_a($this, "DBA"))
			return false;
		if (!$this->open())
			return false;
		if ($this->handler) {
			debug_log("dba_nextkey($this->res)");
			return dba_nextkey($this->res);
		}
		if (!is_array($this->cache["data"]) || !count($this->cache["data"]))
			return false;
		if (next($this->cache["data"]) === false)
			return false;
		return key($this->cache["data"]);
	}

	function fetch($key)
	{
		if (!$this || !is_a($this, "DBA"))
			return false;
		if (!$this->open())
			return false;
		if ($this->handler) {
			debug_log("dba_fetch($key, $this->res)");
			$val = dba_fetch($key, $this->res);
			if (!is_string($val))
				return null;
			$v = @unserialize($val);
			if ($v === false && $val != 'b:0;') {
				// old value, needs correction
				$v = explode(",", $val, 3);
				$v[2] = str_replace("&#44;", ",", $v[2]);
				dba_replace($key, serialize($v), $this->res);
			}
			return $v;
		}
		if (!is_array($this->cache["data"]) || !count($this->cache["data"]) || !isset($this->cache["data"][$key]))
			return false;
		return $this->cache["data"][$key];
	}

	function insert($key, $val)
	{
		if (!$this || !is_a($this, "DBA"))
			return false;
		if (!$this->open())
			return false;
		if ($this->handler) {
			debug_log("dba_insert($key, $val, $this->res)");
			return dba_insert($key, serialize($val), $this->res);
		}
		if (!is_array($this->cache["data"]))
			$this->cache["data"] = array();
		elseif (isset($this->cache["data"][$key]))
			return false;
		$this->cache["data"][$key] = $val;
		$this->cache["sync"] = false;
		return true;
	}

	function replace($key, $val)
	{
		if (!$this || !is_a($this, "DBA"))
			return false;
		if (!$this->open())
			return false;
		if ($this->handler) {
			debug_log("dba_replace($key, $val, $this->res)");
			return dba_replace($key, serialize($val), $this->res);
		}
		if (!is_array($this->cache["data"]))
			$this->cache["data"] = array();
		debug_log("DBA::replace($key, $val, $this->res)");
		$this->cache["data"][$key] = $val;
		$this->cache["sync"] = false;
		return true;
 	}

	function delete($key)
	{
		if (!$this || !is_a($this, "DBA"))
			return false;
		if (!$this->open())
			return false;
		if ($this->handler) {
			debug_log("dba_delete($key, $this->res)");
			return dba_delete($key, $this->res);
		}
		if (!is_array($this->cache["data"]) || !count($this->cache["data"]) || !isset($this->cache["data"][$key]))
			return false;
		unset($this->cache["data"][$key]);
		$this->cache["sync"] = false;
		return true;
	}

	function optimize()
	{
		if (!$this || !is_a($this, "DBA") || $this->res === false || $this->handler === false)
			return false;
		dba_optimize($this->res);
		debug_log("dba_optimize($this->res)");
	}

	function sync()
	{
		if (!$this || !is_a($this, "DBA") || $this->res === false)
			return false;
		if ($this->handler && $this->res) {
			debug_log("dba_sync($this->res)");
			return dba_sync($this->res);
		}
		if (!$this->cache["mode"] || $this->cache["sync"])
			return true;
		$fh = fopen($this->res, "w");
		fwrite($fh, serialize($this->cache["data"]));
		fclose($fh);
		return true;
	}
}
?>
