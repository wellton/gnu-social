<?php
// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.

/**
 * GS DB object abstraction
 *
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2010-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

defined('GNUSOCIAL') || die();

/**
 * GNU social abstraction of the Database library
 *
 * @copyright 2010-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class GS_DataObject extends Connection
{

    /**
     * Gets the DB object related to an object
     *
     * @access public
     * @return object The DB connection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDatabaseConnection()
    {
        return DriverManager::getConnection($params, $config);
    }

    /**
     * Find the number of results from a simple query
     *
     * for example
     *
     * $object = new mytable();
     * $object->name = "fred";
     * echo $object->count();
     * echo $object->count(true);  // dont use object vars.
     * echo $object->count('distinct mycol');   count distinct mycol.
     * echo $object->count('distinct mycol',true); // dont use object vars.
     * echo $object->count('distinct');      // count distinct id (eg. the primary key)
     *
     *
     * @param bool|string  (optional)
     *                  (true|false => see below not on whereAddonly)
     *                  (string)
     *                      "DISTINCT" => does a distinct count on the tables 'key' column
     *                      otherwise  => normally it counts primary keys - you can use
     *                                    this to do things like $do->count('distinct mycol');
     *
     * @param bool      $whereAddOnly (optional) If DB_DATAOBJECT_WHEREADD_ONLY is passed in then
     *                  we will build the condition only using the whereAdd's.  Default is to
     *                  build the condition using the object parameters as well.
     *
     * @access public
     * @return int
     */
    public function count(): int
    {
        global $_DB_DATAOBJECT;

        if (is_bool($countWhat)) {
            $whereAddOnly = $countWhat;
        }

        $t = clone($this);
        $items   = $t->table();

        $quoteIdentifiers = !empty($_DB_DATAOBJECT['CONFIG']['quote_identifiers']);


        if (!isset($t->_query)) {
            $this->raiseError(
                "You cannot do run count after you have run fetch()",
                DB_DATAOBJECT_ERROR_INVALIDARGS);
            return false;
        }
        $this->_connect();
        $DB = $_DB_DATAOBJECT['CONNECTIONS'][$this->_database_dsn_md5];


        if (!$whereAddOnly && $items)  {
            $t->_build_condition($items);
        }
        $keys = $this->keys();

        if (empty($keys[0]) && (!is_string($countWhat) || (strtoupper($countWhat) == 'DISTINCT'))) {
            $this->raiseError(
                "You cannot do run count without keys - use \$do->count('id'), or use \$do->count('distinct id')';",
                DB_DATAOBJECT_ERROR_INVALIDARGS,PEAR_ERROR_DIE);
            return false;

        }
        $table   = ($quoteIdentifiers ? $DB->quoteIdentifier($this->tableName()) : $this->tableName());
        $key_col = empty($keys[0]) ? '' : (($quoteIdentifiers ? $DB->quoteIdentifier($keys[0]) : $keys[0]));
        $as      = ($quoteIdentifiers ? $DB->quoteIdentifier('DATAOBJECT_NUM') : 'DATAOBJECT_NUM');

        // support distinct on default keys.
        $countWhat = (strtoupper($countWhat) == 'DISTINCT') ?
            "DISTINCT {$table}.{$key_col}" : $countWhat;

        $countWhat = is_string($countWhat) ? $countWhat : "{$table}.{$key_col}";

        $r = $t->_query(
            "SELECT count({$countWhat}) as $as
                FROM $table {$t->_join} {$t->_query['condition']}");
        if (PEAR::isError($r)) {
            return false;
        }

        $result  = $_DB_DATAOBJECT['RESULTS'][$t->_DB_resultid];
        $l = $result->fetchRow(DB_DATAOBJECT_FETCHMODE_ORDERED);
        // free the results - essential on oracle.
        $t->free();
        if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
            $this->debug('Count returned '. $l[0] ,1);
        }
        return (int) $l[0];
    }

    /**
     * Deletes items from table which match current objects variables
     *
     * Returns the true on success
     *
     * for example
     *
     * Designed to be extended
     *
     * $object = new mytable();
     * $object->ID=123;
     * echo $object->delete(); // builds a conditon
     *
     * $object = new mytable();
     * $object->whereAdd('age > 12');
     * $object->limit(1);
     * $object->orderBy('age DESC');
     * $object->delete(true); // dont use object vars, use the conditions, limit and order.
     *
     * @param bool $useWhere (optional) If DB_DATAOBJECT_WHEREADD_ONLY is passed in then
     *             we will build the condition only using the whereAdd's.  Default is to
     *             build the condition only using the object parameters.
     *
     * @access public
     * @return mixed Int (No. of rows affected) on success, false on failure, 0 on no data affected
     */
    public function delete($useWhere = false)
    {
        global $_DB_DATAOBJECT;
        // connect will load the config!
        $this->_connect();
        $DB = $_DB_DATAOBJECT['CONNECTIONS'][$this->_database_dsn_md5];
        $quoteIdentifiers  = !empty($_DB_DATAOBJECT['CONFIG']['quote_identifiers']);

        $extra_cond = ' ' . (isset($this->_query['order_by']) ? $this->_query['order_by'] : '');

        if (!$useWhere) {

            $keys = $this->keys();
            $this->_query = array(); // as it's probably unset!
            $this->_query['condition'] = ''; // default behaviour not to use where condition
            $this->_build_condition($this->table(),$keys);
            // if primary keys are not set then use data from rest of object.
            if (!$this->_query['condition']) {
                $this->_build_condition($this->table(),array(),$keys);
            }
            $extra_cond = '';
        }


        // don't delete without a condition
        if (($this->_query !== false) && $this->_query['condition']) {

            $table = ($quoteIdentifiers ? $DB->quoteIdentifier($this->tableName()) : $this->tableName());
            $sql = "DELETE ";
            // using a joined delete. - with useWhere..
            $sql .= (!empty($this->_join) && $useWhere) ?
                "{$table} FROM {$table} {$this->_join} " :
                "FROM {$table} ";

            $sql .= $this->_query['condition']. $extra_cond;

            // add limit..

            if (isset($this->_query['limit_start']) && strlen($this->_query['limit_start'] . $this->_query['limit_count'])) {

                if (!isset($_DB_DATAOBJECT['CONFIG']['db_driver']) ||
                    ($_DB_DATAOBJECT['CONFIG']['db_driver'] == 'DB')) {
                    // pear DB
                    $sql = $DB->modifyLimitQuery($sql,$this->_query['limit_start'], $this->_query['limit_count']);

                } else {
                    // MDB2
                    $DB->setLimit( $this->_query['limit_count'],$this->_query['limit_start']);
                }

            }


            $r = $this->_query($sql);


            if (PEAR::isError($r)) {
                $this->raiseError($r);
                return false;
            }
            if ($r < 1) {
                return 0;
            }
            $this->_clear_cache();
            return $r;
        } else {
            $this->raiseError("delete: No condition specifed for query", DB_DATAOBJECT_ERROR_NODATA);
            return false;
        }
    }

    /**
     * Get a result using key, value.
     *
     * for example
     * $object->get("ID",1234);
     * Returns Number of rows located (usually 1) for success,
     * and puts all the table columns into this classes variables
     *
     * see the fetch example on how to extend this.
     *
     * if no value is entered, it is assumed that $key is a value
     * and get will then use the first key in keys()
     * to obtain the key.
     *
     * @param   string  $k column
     * @param   string  $v value
     * @access  public
     * @return  int     No. of rows
     */
    public function get($k = null, $v = null): int
    {
        global $_DB_DATAOBJECT;
        if (empty($_DB_DATAOBJECT['CONFIG'])) {
            DB_DataObject::_loadConfig();
        }
        $keys = array();

        if ($v === null) {
            $v = $k;
            $keys = $this->keys();
            if (!$keys) {
                $this->raiseError("No Keys available for {$this->tableName()}", DB_DATAOBJECT_ERROR_INVALIDCONFIG);
                return false;
            }
            $k = $keys[0];
        }
        if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
            $this->debug("$k $v " .print_r($keys,true), "GET");
        }

        if ($v === null) {
            $this->raiseError("No Value specified for get", DB_DATAOBJECT_ERROR_INVALIDARGS);
            return false;
        }
        $this->$k = $v;
        return $this->find(1);
    }

    public static function staticGet(string $class, string $k, $v = null)
    {
        $lclass = strtolower($class);
        global $_DB_DATAOBJECT;
        if (empty($_DB_DATAOBJECT['CONFIG'])) {
            DB_DataObject::_loadConfig();
        }



        $key = "$k:$v";
        if ($v === null) {
            $key = $k;
        }
        if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
            DB_DataObject::debug("$class $key","STATIC GET - TRY CACHE");
        }
        if (!empty($_DB_DATAOBJECT['CACHE'][$lclass][$key])) {
            return $_DB_DATAOBJECT['CACHE'][$lclass][$key];
        }
        if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
            DB_DataObject::debug("$class $key","STATIC GET - NOT IN CACHE");
        }

        $obj = DB_DataObject::factory(substr($class,strlen($_DB_DATAOBJECT['CONFIG']['class_prefix'])));
        if (PEAR::isError($obj)) {
            $dor = new DB_DataObject();
            $dor->raiseError("could not autoload $class", DB_DATAOBJECT_ERROR_NOCLASS);
            $r = false;
            return $r;
        }

        if (!isset($_DB_DATAOBJECT['CACHE'][$lclass])) {
            $_DB_DATAOBJECT['CACHE'][$lclass] = array();
        }
        if (!$obj->get($k,$v)) {
            $dor = new DB_DataObject();
            $dor->raiseError("No Data return from get $k $v", DB_DATAOBJECT_ERROR_NODATA);

            $r = false;
            return $r;
        }
        $_DB_DATAOBJECT['CACHE'][$lclass][$key] = $obj;
        return $_DB_DATAOBJECT['CACHE'][$lclass][$key];
    }

    /**
     * fetches next row into this objects var's
     *
     * returns 1 on success 0 on failure
     *
     *
     *
     * Example
     * $object = new mytable();
     * $object->name = "fred";
     * $object->find();
     * $store = array();
     * while ($object->fetch()) {
     *   echo $this->ID;
     *   $store[] = $object; // builds an array of object lines.
     * }
     *
     * to add features to a fetch
     * function fetch () {
     *    $ret = parent::fetch();
     *    $this->date_formated = date('dmY',$this->date);
     *    return $ret;
     * }
     *
     * @access  public
     * @return  bool on success
     */
    public function fetch(): bool
    {
        global $_DB_DATAOBJECT;
        if (empty($_DB_DATAOBJECT['CONFIG'])) {
            DB_DataObject::_loadConfig();
        }
        if (empty($this->N)) {
            if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
                $this->debug("No data returned from FIND (eg. N is 0)","FETCH", 3);
            }
            return false;
        }

        if (empty($_DB_DATAOBJECT['RESULTS'][$this->_DB_resultid]) ||
            !is_object($result = $_DB_DATAOBJECT['RESULTS'][$this->_DB_resultid]))
        {
            if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
                $this->debug('fetched on object after fetch completed (no results found)');
            }
            return false;
        }


        $array = $result->fetchRow(DB_DATAOBJECT_FETCHMODE_ASSOC);
        if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
            $this->debug(serialize($array),"FETCH");
        }

        // fetched after last row..
        if ($array === null) {
            if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
                $t= explode(' ',microtime());

                $this->debug("Last Data Fetch'ed after " .
                    ($t[0]+$t[1]- $_DB_DATAOBJECT['QUERYENDTIME']  ) .
                    " seconds",
                    "FETCH", 1);
            }
            // reduce the memory usage a bit... (but leave the id in, so count() works ok on it)
            unset($_DB_DATAOBJECT['RESULTS'][$this->_DB_resultid]);

            // we need to keep a copy of resultfields locally so toArray() still works
            // however we dont want to keep it in the global cache..

            if (!empty($_DB_DATAOBJECT['RESULTFIELDS'][$this->_DB_resultid])) {
                $this->_resultFields = $_DB_DATAOBJECT['RESULTFIELDS'][$this->_DB_resultid];
                unset($_DB_DATAOBJECT['RESULTFIELDS'][$this->_DB_resultid]);
            }
            // this is probably end of data!!
            //DB_DataObject::raiseError("fetch: no data returned", DB_DATAOBJECT_ERROR_NODATA);
            return false;
        }
        // make sure resultFields is always empty..
        $this->_resultFields = false;

        if (!isset($_DB_DATAOBJECT['RESULTFIELDS'][$this->_DB_resultid])) {
            // note: we dont declare this to keep the print_r size down.
            $_DB_DATAOBJECT['RESULTFIELDS'][$this->_DB_resultid]= array_flip(array_keys($array));
        }
        $replace = array('.', ' ');
        foreach($array as $k=>$v) {
            // use strpos as str_replace is slow.
            $kk =  (strpos($k, '.') === false && strpos($k, ' ') === false) ?
                $k : str_replace($replace, '_', $k);

            if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
                $this->debug("$kk = ". $array[$k], "fetchrow LINE", 3);
            }
            $this->$kk = $array[$k];
        }

        // set link flag
        $this->_link_loaded=false;
        if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
            $this->debug("{$this->tableName()} DONE", "fetchrow",2);
        }
        if (($this->_query !== false) &&  empty($_DB_DATAOBJECT['CONFIG']['keep_query_after_fetch'])) {
            $this->_query = false;
        }
        return true;
    }

    /**
     * find results, either normal or crosstable
     *
     * for example
     *
     * $object = new mytable();
     * $object->ID = 1;
     * $object->find();
     *
     *
     * will set $object->N to number of rows, and expects next command to fetch rows
     * will return $object->N
     *
     * if an error occurs $object->N will be set to false and return value will also be false;
     * if numRows is not supported it will
     *
     *
     * @param   bool $n Fetch first result
     * @access  public
     * @return  mixed (number of rows returned, or true if numRows fetching is not supported)
     */
    public function find($n = false)
    {
        global $_DB_DATAOBJECT;
        if ($this->_query === false) {
            $this->raiseError(
                "You cannot do two queries on the same object (copy it before finding)",
                DB_DATAOBJECT_ERROR_INVALIDARGS);
            return false;
        }

        if (empty($_DB_DATAOBJECT['CONFIG'])) {
            DB_DataObject::_loadConfig();
        }

        if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
            $this->debug($n, "find",1);
        }
        if (!strlen($this->tableName())) {
            // xdebug can backtrace this!
            trigger_error("NO \$__table SPECIFIED in class definition",E_USER_ERROR);
        }
        $this->N = 0;
        $query_before = $this->_query;
        $this->_build_condition($this->table()) ;


        $this->_connect();
        $DB = $_DB_DATAOBJECT['CONNECTIONS'][$this->_database_dsn_md5];


        $sql = $this->_build_select();

        foreach ($this->_query['unions'] as $union_ar) {
            $sql .=   $union_ar[1] .   $union_ar[0]->_build_select() . " \n";
        }

        $sql .=  $this->_query['order_by']  . " \n";


        /* We are checking for method modifyLimitQuery as it is PEAR DB specific */
        if ((!isset($_DB_DATAOBJECT['CONFIG']['db_driver'])) ||
            ($_DB_DATAOBJECT['CONFIG']['db_driver'] == 'DB')) {
            /* PEAR DB specific */

            if (isset($this->_query['limit_start']) && strlen($this->_query['limit_start'] . $this->_query['limit_count'])) {
                $sql = $DB->modifyLimitQuery($sql,$this->_query['limit_start'], $this->_query['limit_count']);
            }
        } else {
            /* theoretically MDB2! */
            if (isset($this->_query['limit_start']) && strlen($this->_query['limit_start'] . $this->_query['limit_count'])) {
                $DB->setLimit($this->_query['limit_count'],$this->_query['limit_start']);
            }
        }


        $err = $this->_query($sql);
        if (is_a($err,'PEAR_Error')) {
            return false;
        }

        if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
            $this->debug("CHECK autofetchd $n", "find", 1);
        }

        // find(true)

        $ret = $this->N;
        if (!$ret && !empty($_DB_DATAOBJECT['RESULTS'][$this->_DB_resultid])) {
            // clear up memory if nothing found!?
            unset($_DB_DATAOBJECT['RESULTS'][$this->_DB_resultid]);
        }

        if ($n && $this->N > 0 ) {
            if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
                $this->debug("ABOUT TO AUTOFETCH", "find", 1);
            }
            $fs = $this->fetch();
            // if fetch returns false (eg. failed), then the backend doesnt support numRows (eg. ret=true)
            // - hence find() also returns false..
            $ret = ($ret === true) ? $fs : $ret;
        }
        if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
            $this->debug("DONE", "find", 1);
        }
        $this->_query = $query_before;
        return $ret;
    }

    /**
     * fetches all results as an array,
     *
     * return format is dependant on args.
     * if selectAdd() has not been called on the object, then it will add the correct columns to the query.
     *
     * A) Array of values (eg. a list of 'id')
     *
     * $x = DB_DataObject::factory('mytable');
     * $x->whereAdd('something = 1')
     * $ar = $x->fetchAll('id');
     * -- returns array(1,2,3,4,5)
     *
     * B) Array of values (not from table)
     *
     * $x = DB_DataObject::factory('mytable');
     * $x->whereAdd('something = 1');
     * $x->selectAdd();
     * $x->selectAdd('distinct(group_id) as group_id');
     * $ar = $x->fetchAll('group_id');
     * -- returns array(1,2,3,4,5)
     *     *
     * C) A key=>value associative array
     *
     * $x = DB_DataObject::factory('mytable');
     * $x->whereAdd('something = 1')
     * $ar = $x->fetchAll('id','name');
     * -- returns array(1=>'fred',2=>'blogs',3=> .......
     *
     * D) array of objects
     * $x = DB_DataObject::factory('mytable');
     * $x->whereAdd('something = 1');
     * $ar = $x->fetchAll();
     *
     * E) array of arrays (for example)
     * $x = DB_DataObject::factory('mytable');
     * $x->whereAdd('something = 1');
     * $ar = $x->fetchAll(false,false,'toArray');
     *
     *
     * @param    string|false  $k key
     * @param    string|false  $v value
     * @param    string|false  $method method to call on each result to get array value (eg. 'toArray')
     * @access  public
     * @return  array  format dependant on arguments, may be empty
     */
    function fetchAll($k= false, $v = false, $method = false)
    {
        // should it even do this!!!?!?
        if ($k !== false &&
            (   // only do this is we have not been explicit..
                empty($this->_query['data_select']) ||
                ($this->_query['data_select'] == '*')
            )
        ) {
            $this->selectAdd();
            $this->selectAdd($k);
            if ($v !== false) {
                $this->selectAdd($v);
            }
        }

        $this->find();
        $ret = array();
        while ($this->fetch()) {
            if ($v !== false) {
                $ret[$this->$k] = $this->$v;
                continue;
            }
            $ret[] = $k === false ?
                ($method == false ? clone($this)  : $this->$method())
                : $this->$k;
        }
        return $ret;

    }

    /**
     * fetches a specific row into this object variables
     *
     * Not recommended - better to use fetch()
     *
     * Returns true on success
     *
     * @param  int   $row  row
     * @access public
     * @return bool true on success
     */
    public function fetchRow($row = null): bool
    {
        global $_DB_DATAOBJECT;
        if (empty($_DB_DATAOBJECT['CONFIG'])) {
            $this->_loadConfig();
        }
        if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
            $this->debug("{$this->tableName()} $row of {$this->N}", "fetchrow",3);
        }
        if (!$this->tableName()) {
            $this->raiseError("fetchrow: No table", DB_DATAOBJECT_ERROR_INVALIDCONFIG);
            return false;
        }
        if ($row === null) {
            $this->raiseError("fetchrow: No row specified", DB_DATAOBJECT_ERROR_INVALIDARGS);
            return false;
        }
        if (!$this->N) {
            $this->raiseError("fetchrow: No results avaiable", DB_DATAOBJECT_ERROR_NODATA);
            return false;
        }
        if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
            $this->debug("{$this->tableName()} $row of {$this->N}", "fetchrow",3);
        }


        $result = $_DB_DATAOBJECT['RESULTS'][$this->_DB_resultid];
        $array  = $result->fetchrow(DB_DATAOBJECT_FETCHMODE_ASSOC,$row);
        if (!is_array($array)) {
            $this->raiseError("fetchrow: No results available", DB_DATAOBJECT_ERROR_NODATA);
            return false;
        }
        $replace = array('.', ' ');
        foreach($array as $k => $v) {
            // use strpos as str_replace is slow.
            $kk =  (strpos($k, '.') === false && strpos($k, ' ') === false) ?
                $k : str_replace($replace, '_', $k);

            if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
                $this->debug("$kk = ". $array[$k], "fetchrow LINE", 3);
            }
            $this->$kk = $array[$k];
        }

        if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
            $this->debug("{$this->tableName()} DONE", "fetchrow", 3);
        }
        return true;
    }

    /**
     * Insert the current objects variables into the database
     *
     * Returns the ID of the inserted element (if auto increment or sequences are used.)
     *
     * for example
     *
     * Designed to be extended
     *
     * $object = new mytable();
     * $object->name = "fred";
     * echo $object->insert();
     *
     * @access public
     * @return mixed false on failure, int when auto increment or sequence used, otherwise true on success
     */
    public function insert()
    {
        global $_DB_DATAOBJECT;

        // we need to write to the connection (For nextid) - so us the real
        // one not, a copyied on (as ret-by-ref fails with overload!)

        if (!isset($_DB_DATAOBJECT['CONNECTIONS'][$this->_database_dsn_md5])) {
            $this->_connect();
        }

        $quoteIdentifiers  = !empty($_DB_DATAOBJECT['CONFIG']['quote_identifiers']);

        $DB = $_DB_DATAOBJECT['CONNECTIONS'][$this->_database_dsn_md5];

        $items = $this->table();

        if (!$items) {
            $this->raiseError("insert:No table definition for {$this->tableName()}",
                DB_DATAOBJECT_ERROR_INVALIDCONFIG);
            return false;
        }
        $options = $_DB_DATAOBJECT['CONFIG'];


        $datasaved = 1;
        $leftq     = '';
        $rightq    = '';

        $seqKeys   = isset($_DB_DATAOBJECT['SEQUENCE'][$this->_database][$this->tableName()]) ?
            $_DB_DATAOBJECT['SEQUENCE'][$this->_database][$this->tableName()] :
            $this->sequenceKey();

        $key       = isset($seqKeys[0]) ? $seqKeys[0] : false;
        $useNative = isset($seqKeys[1]) ? $seqKeys[1] : false;
        $seq       = isset($seqKeys[2]) ? $seqKeys[2] : false;

        $dbtype    = $_DB_DATAOBJECT['CONNECTIONS'][$this->_database_dsn_md5]->dsn["phptype"];


        // nativeSequences or Sequences..

        // big check for using sequences

        if (($key !== false) && !$useNative) {

            if (!$seq) {
                $keyvalue =  $DB->nextId($this->tableName());
            } else {
                $f = $DB->getOption('seqname_format');
                $DB->setOption('seqname_format','%s');
                $keyvalue =  $DB->nextId($seq);
                $DB->setOption('seqname_format',$f);
            }
            if (PEAR::isError($keyvalue)) {
                $this->raiseError($keyvalue->toString(), DB_DATAOBJECT_ERROR_INVALIDCONFIG);
                return false;
            }
            $this->$key = $keyvalue;
        }

        // if we haven't set disable_null_strings to "full"
        $ignore_null = !isset($options['disable_null_strings'])
            || !is_string($options['disable_null_strings'])
            || strtolower($options['disable_null_strings']) !== 'full' ;


        foreach($items as $k => $v) {

            // if we are using autoincrement - skip the column...
            if ($key && ($k == $key) && $useNative) {
                continue;
            }


            // Ignore INTEGERS which aren't set to a value - or empty string..
            if ( (!isset($this->$k) || ($v == 1 && $this->$k === ''))
                && $ignore_null
            ) {
                continue;
            }
            // dont insert data into mysql timestamps
            // use query() if you really want to do this!!!!
            if ($v & DB_DATAOBJECT_MYSQLTIMESTAMP) {
                continue;
            }

            if ($leftq) {
                $leftq  .= ', ';
                $rightq .= ', ';
            }

            $leftq .= ($quoteIdentifiers ? ($DB->quoteIdentifier($k) . ' ')  : "$k ");

            if (is_object($this->$k) && is_a($this->$k,'DB_DataObject_Cast')) {
                $value = $this->$k->toString($v,$DB);
                if (PEAR::isError($value)) {
                    $this->raiseError($value->toString() ,DB_DATAOBJECT_ERROR_INVALIDARGS);
                    return false;
                }
                $rightq .=  $value;
                continue;
            }


            if (!($v & DB_DATAOBJECT_NOTNULL) && DB_DataObject::_is_null($this,$k)) {
                $rightq .= " NULL ";
                continue;
            }
            // DATE is empty... on a col. that can be null..
            // note: this may be usefull for time as well..
            if (!$this->$k &&
                (($v & DB_DATAOBJECT_DATE) || ($v & DB_DATAOBJECT_TIME)) &&
                !($v & DB_DATAOBJECT_NOTNULL)) {

                $rightq .= " NULL ";
                continue;
            }


            if ($v & DB_DATAOBJECT_STR) {
                $rightq .= $this->_quote((string) (
                    ($v & DB_DATAOBJECT_BOOL) ?
                        // this is thanks to the braindead idea of postgres to
                        // use t/f for boolean.
                        (($this->$k === 'f') ? 0 : (int)(bool) $this->$k) :
                        $this->$k
                    )) . " ";
                continue;
            }
            if (is_numeric($this->$k)) {
                $rightq .=" {$this->$k} ";
                continue;
            }
            /* flag up string values - only at debug level... !!!??? */
            if (is_object($this->$k) || is_array($this->$k)) {
                $this->debug('ODD DATA: ' .$k . ' ' .  print_r($this->$k,true),'ERROR');
            }

            // at present we only cast to integers
            // - V2 may store additional data about float/int
            $rightq .= ' ' . intval($this->$k) . ' ';

        }

        // not sure why we let empty insert here.. - I guess to generate a blank row..


        if ($leftq || $useNative) {
            $table = ($quoteIdentifiers ? $DB->quoteIdentifier($this->tableName())    : $this->tableName());


            if (($dbtype == 'pgsql') && empty($leftq)) {
                $r = $this->_query("INSERT INTO {$table} DEFAULT VALUES");
            } else {
                $r = $this->_query("INSERT INTO {$table} ($leftq) VALUES ($rightq) ");
            }




            if (PEAR::isError($r)) {
                $this->raiseError($r);
                return false;
            }

            if ($r < 1) {
                return 0;
            }


            // now do we have an integer key!

            if ($key && $useNative) {
                switch ($dbtype) {
                    case 'mysql':
                    case 'mysqli':
                        $method = "{$dbtype}_insert_id";
                        $this->$key = $method(
                            $_DB_DATAOBJECT['CONNECTIONS'][$this->_database_dsn_md5]->connection
                        );
                        break;

                    case 'mssql':
                        // note this is not really thread safe - you should wrapp it with
                        // transactions = eg.
                        // $db->query('BEGIN');
                        // $db->insert();
                        // $db->query('COMMIT');
                        $db_driver = empty($options['db_driver']) ? 'DB' : $options['db_driver'];
                        $method = ($db_driver  == 'DB') ? 'getOne' : 'queryOne';
                        $mssql_key = $DB->$method("SELECT @@IDENTITY");
                        if (PEAR::isError($mssql_key)) {
                            $this->raiseError($mssql_key);
                            return false;
                        }
                        $this->$key = $mssql_key;
                        break;

                    case 'pgsql':
                        if (!$seq) {
                            $seq = $DB->getSequenceName(strtolower($this->tableName()));
                        }
                        $db_driver = empty($options['db_driver']) ? 'DB' : $options['db_driver'];
                        $method = ($db_driver  == 'DB') ? 'getOne' : 'queryOne';
                        $pgsql_key = $DB->$method("SELECT currval('".$seq . "')");


                        if (PEAR::isError($pgsql_key)) {
                            $this->raiseError($pgsql_key);
                            return false;
                        }
                        $this->$key = $pgsql_key;
                        break;

                    case 'ifx':
                        $this->$key = array_shift (
                            ifx_fetch_row (
                                ifx_query(
                                    "select DBINFO('sqlca.sqlerrd1') FROM systables where tabid=1",
                                    $_DB_DATAOBJECT['CONNECTIONS'][$this->_database_dsn_md5]->connection,
                                    IFX_SCROLL
                                ),
                                "FIRST"
                            )
                        );
                        break;

                }

            }

            if (isset($_DB_DATAOBJECT['CACHE'][strtolower(get_class($this))])) {
                $this->_clear_cache();
            }
            if ($key) {
                return $this->$key;
            }
            return true;
        }
        $this->raiseError("insert: No Data specifed for query", DB_DATAOBJECT_ERROR_NODATA);
        return false;
    }

    /**
     * joinAdd - adds another dataobject to this, building a joined query.
     *
     * example (requires links.ini to be set up correctly)
     * // get all the images for product 24
     * $i = new DataObject_Image();
     * $pi = new DataObjects_Product_image();
     * $pi->product_id = 24; // set the product id to 24
     * $i->joinAdd($pi); // add the product_image connectoin
     * $i->find();
     * while ($i->fetch()) {
     *     // do stuff
     * }
     * // an example with 2 joins
     * // get all the images linked with products or productgroups
     * $i = new DataObject_Image();
     * $pi = new DataObject_Product_image();
     * $pgi = new DataObject_Productgroup_image();
     * $i->joinAdd($pi);
     * $i->joinAdd($pgi);
     * $i->find();
     * while ($i->fetch()) {
     *     // do stuff
     * }
     *
     *
     * @param    optional $obj       object |array    the joining object (no value resets the join)
     *                                          If you use an array here it should be in the format:
     *                                          array('local_column','remotetable:remote_column');
     *                                             if remotetable does not have a definition, you should
     *                                             use @ to hide the include error message..
     *                                          array('local_column',  $dataobject , 'remote_column');
     *                                             if array has 3 args, then second is assumed to be the linked dataobject.
     *
     * @param    optional $joinType  string | array
     *                                          'LEFT'|'INNER'|'RIGHT'|'' Inner is default, '' indicates
     *                                          just select ... from a,b,c with no join and
     *                                          links are added as where items.
     *
     *                                          If second Argument is array, it is assumed to be an associative
     *                                          array with arguments matching below = eg.
     *                                          'joinType' => 'INNER',
     *                                          'joinAs' => '...'
     *                                          'joinCol' => ....
     *                                          'useWhereAsOn' => false,
     *
     * @param    optional $joinAs    string     if you want to select the table as anther name
     *                                          useful when you want to select multiple columsn
     *                                          from a secondary table.

     * @param    optional $joinCol   string     The column on This objects table to match (needed
     *                                          if this table links to the child object in
     *                                          multiple places eg.
     *                                          user->friend (is a link to another user)
     *                                          user->mother (is a link to another user..)
     *
     *           optional 'useWhereAsOn' bool   default false;
     *                                          convert the where argments from the object being added
     *                                          into ON arguments.
     *
     *
     * @return   void
     * @access   public
     * @author   Stijn de Reede      <sjr@gmx.co.uk>
     */
    public function joinAdd($obj = false, $joinType='INNER', $joinAs=false, $joinCol=false)//:void XXX PHP: Upgrade to PHP 7.1
    {
    }

    /**
     * Updates  current objects variables into the database
     * uses the keys() to decide how to update
     * Returns the  true on success
     *
     * for example
     *
     * $object = DB_DataObject::factory('mytable');
     * $object->get("ID",234);
     * $object->email="testing@test.com";
     * if(!$object->update())
     *   echo "UPDATE FAILED";
     *
     * to only update changed items :
     * $dataobject->get(132);
     * $original = $dataobject; // clone/copy it..
     * $dataobject->setFrom($_POST);
     * if ($dataobject->validate()) {
     *    $dataobject->update($original);
     * } // otherwise an error...
     *
     * performing global updates:
     * $object = DB_DataObject::factory('mytable');
     * $object->status = "dead";
     * $object->whereAdd('age > 150');
     * $object->update(DB_DATAOBJECT_WHEREADD_ONLY);
     *
     * @param object dataobject (optional) | DB_DATAOBJECT_WHEREADD_ONLY - used to only update changed items.
     * @access public
     * @return  int rows affected or false on failure
     */
    public function update($dataObject = false): int
    {
        global $_DB_DATAOBJECT;
        // connect will load the config!
        $this->_connect();


        $original_query = $this->_query;

        $items = $this->table();

        // only apply update against sequence key if it is set?????

        $seq = $this->sequenceKey();
        if ($seq[0] !== false) {
            $keys = array($seq[0]);
            if (!isset($this->{$keys[0]}) && $dataObject !== true) {
                $this->raiseError("update: trying to perform an update without 
                        the key set, and argument to update is not 
                        DB_DATAOBJECT_WHEREADD_ONLY
                    " . print_r(array('seq' => $seq, 'keys' => $keys), true), DB_DATAOBJECT_ERROR_INVALIDARGS);
                return false;
            }
        } else {
            $keys = $this->keys();
        }


        if (!$items) {
            $this->raiseError("update:No table definition for {$this->tableName()}", DB_DATAOBJECT_ERROR_INVALIDCONFIG);
            return false;
        }
        $datasaved = 1;
        $settings = '';
        $this->_connect();

        $DB = $_DB_DATAOBJECT['CONNECTIONS'][$this->_database_dsn_md5];
        $dbtype = $DB->dsn["phptype"];
        $quoteIdentifiers = !empty($_DB_DATAOBJECT['CONFIG']['quote_identifiers']);
        $options = $_DB_DATAOBJECT['CONFIG'];


        $ignore_null = !isset($options['disable_null_strings'])
            || !is_string($options['disable_null_strings'])
            || strtolower($options['disable_null_strings']) !== 'full';


        foreach ($items as $k => $v) {

            // I think this is ignoring empty vlalues
            if ((!isset($this->$k) || ($v == 1 && $this->$k === ''))
                && $ignore_null
            ) {
                continue;
            }
            // ignore stuff thats

            // dont write things that havent changed..
            if (($dataObject !== false) && isset($dataObject->$k) && ($dataObject->$k === $this->$k)) {
                continue;
            }

            // - dont write keys to left.!!!
            if (in_array($k, $keys)) {
                continue;
            }

            // dont insert data into mysql timestamps
            // use query() if you really want to do this!!!!
            if ($v & DB_DATAOBJECT_MYSQLTIMESTAMP) {
                continue;
            }


            if ($settings) {
                $settings .= ', ';
            }

            $kSql = ($quoteIdentifiers ? $DB->quoteIdentifier($k) : $k);

            if (is_object($this->$k) && is_a($this->$k, 'DB_DataObject_Cast')) {
                $value = $this->$k->toString($v, $DB);
                if (PEAR::isError($value)) {
                    $this->raiseError($value->getMessage(), DB_DATAOBJECT_ERROR_INVALIDARG);
                    return false;
                }
                $settings .= "$kSql = $value ";
                continue;
            }

            // special values ... at least null is handled...
            if (!($v & DB_DATAOBJECT_NOTNULL) && DB_DataObject::_is_null($this, $k)) {
                $settings .= "$kSql = NULL ";
                continue;
            }
            // DATE is empty... on a col. that can be null..
            // note: this may be usefull for time as well..
            if (!$this->$k &&
                (($v & DB_DATAOBJECT_DATE) || ($v & DB_DATAOBJECT_TIME)) &&
                !($v & DB_DATAOBJECT_NOTNULL)) {

                $settings .= "$kSql = NULL ";
                continue;
            }


            if ($v & DB_DATAOBJECT_STR) {
                $settings .= "$kSql = " . $this->_quote((string)(
                    ($v & DB_DATAOBJECT_BOOL) ?
                        // this is thanks to the braindead idea of postgres to
                        // use t/f for boolean.
                        (($this->$k === 'f') ? 0 : (int)(bool)$this->$k) :
                        $this->$k
                    )) . ' ';
                continue;
            }
            if (is_numeric($this->$k)) {
                $settings .= "$kSql = {$this->$k} ";
                continue;
            }
            // at present we only cast to integers
            // - V2 may store additional data about float/int
            $settings .= "$kSql = " . intval($this->$k) . ' ';
        }


        if (!empty($_DB_DATAOBJECT['CONFIG']['debug'])) {
            $this->debug("got keys as " . serialize($keys), 3);
        }
        if ($dataObject !== true) {
            $this->_build_condition($items, $keys);
        } else {
            // prevent wiping out of data!
            if (empty($this->_query['condition'])) {
                $this->raiseError("update: global table update not available
                        do \$do->whereAdd('1=1'); if you really want to do that.
                    ", DB_DATAOBJECT_ERROR_INVALIDARGS);
                return false;
            }
        }


        //  echo " $settings, $this->condition ";
        if ($settings && isset($this->_query) && $this->_query['condition']) {

            $table = ($quoteIdentifiers ? $DB->quoteIdentifier($this->tableName()) : $this->tableName());

            $r = $this->_query("UPDATE  {$table}  SET {$settings} {$this->_query['condition']} ");

            // restore original query conditions.
            $this->_query = $original_query;

            if (PEAR::isError($r)) {
                $this->raiseError($r);
                return false;
            }
            if ($r < 1) {
                return 0;
            }

            $this->_clear_cache();
            return $r;
        }
        // restore original query conditions.
        $this->_query = $original_query;

        // if you manually specified a dataobject, and there where no changes - then it's ok..
        if ($dataObject !== false) {
            return true;
        }

        $this->raiseError(
            "update: No Data specifed for query $settings , {$this->_query['condition']}",
            DB_DATAOBJECT_ERROR_NODATA);
        return false;
    }
}
