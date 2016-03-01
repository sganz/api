<?php
namespace common\helpers;

require_once 'protected/common/helpers/CLQLConst.php';

use common\helpers\MsgList;

/**
 * CLQL (Car Labs Query Langue) Manager
 */

class CLQLReqMgr {

	private $CLQL_req;			// holds decoded JSON as PHP Array
	private $CLQL_req_valid;	// holds state of validity of $req
	private $CLQL_resp;			// holds mapped response records (array or recs)
	private $CLQL_resp_valid;	// holds state of response

	private $error_stack;
	private $debug_stack;
	private $plan_stack;

	private $debug_enabled;
	private $verbose_enabled;
	private $plan_enabled;
	private $unique_field_list;
	private $user_token; 			// a string

	private $intern_to_extern_map;	// Map internal field names (db, computed, etc) to user (external) field names
	private $extern_to_intern_map;	// Map external field names (user) to internal (db, computed, etc)

	private static $error_str_table = [
		CLQL_STATUS_OK => 'OK',
		CLQL_STATUS_INVALID_REQUEST => 'Invalid Request Sent',
		CLQL_STATUS_INVALID_LIST_ITEM => 'Invalid Item in Specified List',
		CLQL_STATUS_INVALID_RANGE => 'Missing or Invalid From/To Range',
		CLQL_STATUS_MISSING_SECTION => 'Required Section Missing',
		CLQL_STATUS_INVALID_SECTION => 'Invalid Section Found',
		CLQL_STATUS_UNKNOWN_SECTION => 'Unknown Section Found',
		CLQL_STATUS_EMPTY_SECTION => 'Empty Section Found',
		CLQL_STATUS_MAL_FORMED_JSON => 'Invalid or Mal formed JSON request',
		CLQL_STATUS_INVALID_SORT_FIELD => 'Invalid Fetch Sort Field Specified',
		CLQL_STATUS_INVALID_REQUESTING_FIELD => 'Invalid Requesting Field',
		CLQL_STATUS_INVALID_CONSTRAINT_FIELD => 'Invalid Constraint Field',
		CLQL_STATUS_INVALID_SYSTEM_FIELD => 'Invalid System Field',
		CLQL_STATUS_INVALID_SCORE_FIELD => 'Invalid Score Field',
		CLQL_STATUS_INVALID_FETCH_FIELD => 'Invalid Fetch Field',
		CLQL_STATUS_INVALID_CONSTRAINT_STRUCTURE => 'Invalid Constraint Structure',
		CLQL_STATUS_INVALID_CONSTRAINT_DATA_TYPE => 'Mismatched or Invalid Data type',
		CLQL_STATUS_INVALID_SYSTEM_SECTION => 'Invalid System Section',
		CLQL_STATUS_INVALID_CONSTRAINT_SECTION => 'Invalid Constraint Section',
		CLQL_STATUS_INVALID_SCORE_SECTION => 'Invalid Score Section',
		CLQL_STATUS_INVALID_FETCH_SECTION  => 'Invalid Fetch Section',
		CLQL_STATUS_INVALID_REQUESTING_SECTION => 'Invalid Requesting Section',

		CLQL_STATUS_ERROR_BOOL_EXPECTED => 'Boolean Value Expected',
		CLQL_STATUS_ERROR_INT_EXPECTED => 'Integer Value Expected',
		CLQL_STATUS_ERROR_NUMERIC_EXPECTED => 'NUMERICing Point Value Expected',
		CLQL_STATUS_ERROR_STRING_EXPECTED => 'String Value Expected',

		CLQL_STATUS_ERROR_NESTING_TOO_DEEP => 'Requesting Structure Nesting Too Deep',
		CLQL_STATUS_ERROR_TOO_MANY_FIELDS => 'Requesting Structure Has Too Many Fields',
		CLQL_STATUS_ERROR_EMPTY_CONSTRAINT_LIST => 'Constraint has Empty List',
		CLQL_STATUS_ERROR_EMPTY_CONSTRAINT_RANGE => 'Constraint has Empty Range',
		CLQL_STATUS_ERROR_MAX_CONSTRAINT_LIST => 'Constraint List has too many entries',
		CLQL_STATUS_ERROR_CORRUPT_CONSTRAINT_LIST => 'Corrupt or Invalid Format for Constraint List',
		CLQL_STATUS_MISSING_RANGE => 'Missing or Empty Range Specifier, Must Have `from` or `to`',
		CLQL_STATUS_DUPE_CONSTRAINT_FIELD => 'Duplicate Constraint Field Encountered',
		CLQL_STATUS_DUPE_REQUESTING_FIELD => 'Duplicate Requesting Field Encountered',
		CLQL_STATUS_ERROR_NO_VALID_REQUEST => 'No Valid Request to Process, Error on Response Construction',
		CLQL_STATUS_INVALID_DATA_RESPONSE => 'Invalid Data Response (from Query System)',
		CLQL_STATUS_INVALID_ENGINE_QUERY => 'Query Engine Reports Invalid Query',
		CLQL_STATUS_INVALID_USER_TOKEN => 'Invalid User Token',
		CLQL_STATUS_INVALID_USER_TOKEN_FORMAT => 'Invalid User Token Format',

		CLQL_STATUS_OZOB_ERROR => 'OZOB ERROR, FATAL'
	];

	private static $body_type_map = [
		'car' => 'car',
		'minivan' => 'minivan',
		'suv' => 'suv',
		'truck' => 'truck',
		'van' => 'van',
	];

	private static $body_size_map = [
		'compact' => 'compact',
		'large' => 'large',
		'midsize' => 'midsize',
	];

	private static $body_style_map = [
		'2dr hatchback' => '2dr hatchback',
		'2dr suv' => '2dr suv',
		'4dr hatchback'=>'4dr hatchback',
		'4dr suv' => '4dr suv',
		'cargo minivan'=>'cargo minivan',
		'cargo minivan'=>'cargo minivan',
		'cargo van' => 'cargo van',
		'convertible' => 'convertible',
		'convertible suv' => 'convertible suv',
		'coupe' => 'coupe',
		'crew cab pickup' => 'crew cab pickup',
		'extended cab pickup' => 'extended cab pickup',
		'passenger minivan' => 'passenger minivan',
		'passenger van' => 'passenger van',
		'regular cab pickup' => 'regular cab pickup',
		'sedan' => 'sedan',
		'wagon' => 'wagon',
	];

	private static $fld_tbl = [
	// Data is mapped like this. Using offset for now, may later use a map...
	// 'CLQL_NAME' => [CLQL_SRC_FLD_NAME, CLQL_SOURCE, CLQL_TYPE, CLQL_CONSTRAINT_TYPE, CLQL_DEFAULT, CLQL_VALID_ELEMENTS, CLQL_SORTABLE, CLQL_SCOREABLE],


		'id' => ['style_id', CLQL_SRC_FLAT_FILE, CLQL_INT_TYPE, CLQL_CONSTRAINT_LIST_TYPE, '', [], false, false],

		'make' 		=> ['make_name', CLQL_SRC_FLAT_FILE, CLQL_STRING_TYPE, CLQL_CONSTRAINT_LIST_TYPE, '', [], true, false],
		'model'		=> ['model_name', CLQL_SRC_FLAT_FILE, CLQL_STRING_TYPE, CLQL_CONSTRAINT_LIST_TYPE, '', [], true, false],
		'name' 		=> ['full_name', CLQL_SRC_COMPUTED, CLQL_STRING_TYPE, CLQL_CONSTRAINT_INVALID, '', [], true, false],
		'price' 	=> ['price_invoice', CLQL_SRC_FLAT_FILE, CLQL_NUMERIC_TYPE, CLQL_CONSTRAINT_RANGE_TYPE, '', [], true, true],

		'bodyType' => ['body_type', CLQL_SRC_FLAT_FILE, CLQL_STRING_TYPE, CLQL_CONSTRAINT_LIST_TYPE, '', [], false, false],
		'bodySize' => ['body_size', CLQL_SRC_FLAT_FILE, CLQL_STRING_TYPE, CLQL_CONSTRAINT_LIST_TYPE, '', [], false, false],
		'bodyStyle' => ['body_style', CLQL_SRC_FLAT_FILE, CLQL_STRING_TYPE, CLQL_CONSTRAINT_LIST_TYPE, '', [], false, false],

		'mpg' => ['mpg', CLQL_SRC_FLAT_FILE, CLQL_INT_TYPE, CLQL_CONSTRAINT_RANGE_TYPE, '', [], true, false],
		'weight' => ['gross_weight', CLQL_SRC_FLAT_FILE, CLQL_NUMERIC_TYPE, CLQL_CONSTRAINT_RANGE_TYPE, '', [], true, true],
		'seats' => ['num_seats', CLQL_SRC_FLAT_FILE, CLQL_INT_TYPE, CLQL_CONSTRAINT_RANGE_TYPE, '', [], true, false],
		'horsepower' => ['horsepower', CLQL_SRC_FLAT_FILE, CLQL_INT_TYPE, CLQL_CONSTRAINT_RANGE_TYPE, '', [], true, false],
		'imgPath' => ['img_path', CLQL_SRC_COMPUTED, CLQL_STRING_TYPE, CLQL_CONSTRAINT_INVALID,'', [], false, false],
		'overallScore' => ['overall_score', CLQL_SRC_FLAT_FILE, CLQL_INT_TYPE, CLQL_CONSTRAINT_RANGE_TYPE, '', [], true, false],
		'safety' => ['safety', CLQL_SRC_COMPUTED, CLQL_NUMERIC_TYPE, CLQL_CONSTRAINT_RANGE_TYPE, '', [], true, true],
		'reliability' => ['reliability', CLQL_SRC_COMPUTED, CLQL_NUMERIC_TYPE, CLQL_CONSTRAINT_RANGE_TYPE, '', [], true, true],
		'utility' => ['utility', CLQL_SRC_COMPUTED, CLQL_NUMERIC_TYPE, CLQL_CONSTRAINT_RANGE_TYPE, '', [], true, true],
		'envy' => ['envy', CLQL_SRC_COMPUTED, CLQL_NUMERIC_TYPE, CLQL_CONSTRAINT_RANGE_TYPE, '', [], true, true],
		'headroom' => ['headroom', CLQL_SRC_FLAT_FILE, CLQL_NUMERIC_TYPE, CLQL_CONSTRAINT_RANGE_TYPE, '', [], true, false],
		'Quababble' => ['make_name', CLQL_SRC_FLAT_FILE, CLQL_STRING_TYPE, CLQL_CONSTRAINT_LIST_TYPE, 'N/A', [], true, false],
	];

	// local constants

	const DEFAULT_PAD = '---*';
	const DEFAULT_DEBUG = false;
	const FORCE_DEFAULT_DEBUG = false;

	/**
	 * Class cons, do any init here!
	 */

    public function __construct($force_dbg = self::FORCE_DEFAULT_DEBUG)
    {
		$this->CLQL_req = [];
		$this->CLQL_req_valid = false;
		$this->CLQL_resp = [];
		$this->CLQL_resp_valid = false;

		$this->error_stack = new MsgList();
		$this->debug_stack = new MsgList();
		$this->plan_stack = new MsgList();
		$this->unique_field_list = [];
		$this->user_token = '';

		$this->debug_enabled = self::DEFAULT_DEBUG;
		$this->force_debug = $force_dbg;
		$this->verbose_enabled = CLQL_DEFAULT_VERBOSE;
		$this->plan_enabled = CLQL_DEFAULT_PLAN;

		// build the mapping table for internal (db, computed, etc) name to external (user specified)
		// these are just subsets and flipped fld_tbl

		foreach(self::$fld_tbl as $name => $value)
		{
			$this->intern_to_extern_map[$value[CLQL_SRC_FLD_NAME]] = ['external_name' => $name, 'data_src' => $value[CLQL_SOURCE]];	// flip table, and
			$this->extern_to_intern_map[$name] = $value[CLQL_SRC_FLD_NAME];
		}

		// now set up fld_tbl for any embedded statics

		self::$fld_tbl['bodyStyle'][CLQL_VALID_ELEMENTS] = self::$body_style_map;
		self::$fld_tbl['bodySize'][CLQL_VALID_ELEMENTS] = self::$body_size_map;
		self::$fld_tbl['bodyType'][CLQL_VALID_ELEMENTS] = self::$body_type_map;
    }

	/**
	 * Translates a CLQL status to a string
	 *
	 * @param int $id The CLQL Status ID
	 *
	 * @return string The translated error string. If ID is unknown,
	 *         then the OZOB Error string will be return with the offending id.
	 */

	public function xlateCQLStatus($id)
	{
		if(isset(self::$error_str_table[$id]))
			return self::$error_str_table[$id];
		return 'Error Code ' . $id . ' Unknown, ' . self::$error_str_table[CLQL_STATUS_OZOB_ERROR];
	}

	/**
	 * Pushes an String onto the debug stack if debug is enabled
	 *
	 * @param string $debug_str The string to stuff onto the stack
	 * @param int $level (just adds number of $pad's in front of $debug_str)
	 * @param string $pad string to pad, each $pad get added for each level specified
	 */

	public function pushDebugStr($debug_str, $level = 0, $pad = self::DEFAULT_PAD)
	{
		// Only log if enabled or forced

		if($this->debug_enabled || $this->force_debug)
			$this->debug_stack->append(str_repeat($pad, $level) . $debug_str);
	}

	/**
	 * Pushes an String onto the error stack
	 *
	 * @param string $debug_str The string to stuff onto the stack
	 * @param int $level (just adds number of $pad's in front of str)
	 * @param string $pad string to pad, each $pad get added for each level specified
	 */

	public function pushErrorStr($error_str, $level = 0, $pad = self::DEFAULT_PAD)
	{
		$this->error_stack->append(str_repeat($pad, $level) . $error_str);
	}

	/**
	 * Pushes an Error onto the global error stack
	 *
	 * @param int $error_id The CLQL Status ID
	 *
	 */

	public function pushErrorId($error_id)
	{
		$this->pushErrorStr($this->xlateCQLStatus($error_id));
	}

	/**
	* Pushes an String onto the plan stack
	*
	* @param string $debug_str The string to stuff onto the stack
	* @param int $level (just adds number of $pad's in front of str)
	* @param string $pad string to pad, each $pad get added for each level specified
	*/

	public function pushPlanStr($plan_str, $level = 0, $pad = self::DEFAULT_PAD)
	{
		$this->plan_stack->append(str_repeat($pad, $level) . $plan_str);
	}

	/**
	 * Returns number of error msgs.
	 */

	public function getErrorCnt()
	{
		return $this->error_stack->getCount();
	}

	/**
	 * Returns number of debug msgs.
	 */

	public function getDebugCnt()
	{
		return $this->debug_stack->getCount();
	}

	/**
	 * Returns number of debug msgs.
	 */

	public function getPlanCnt()
	{
		return $this->plan_stack->getCount();
	}

	/**
	 * Returns a formatted error string from the stack of collected Error messages.
	 *
	 * @param string $line_sep The string to use as a line seperator, can be HTML, etc.
	 * @param int $output_order Order of assembly, either NEW_FIRST (Newest First) or OLD_FIRST (Oldest First)
	 * @param bool $line_numbers True if line numbers to be prepended, false if no line numbers needed.
	 *
	 * @return string The assembled output
	 */

	public function getErrorString($line_sep = "\n", $output_order = MsgList::NEW_FIRST, $line_numbers = true)
	{
		$this->error_stack->setOutputOrder($output_order);
		$this->error_stack->setLineNumbers($line_numbers);
		$this->error_stack->setLineSep($line_sep);

		return $this->error_stack->stringify();
	}

	/**
	 * Returns a formatted error string from the stack of collected Debug messages.
	 *
	 * @param string $line_sep The string to use as a line seperator, can be HTML, etc.
	 * @param int $output_order Order of assembly, either NEW_FIRST (Newest First) or OLD_FIRST (Oldest First)
	 * @param bool $line_numbers True if line numbers to be prepended, false if no line numbers needed.
	 *
	 * @return string The assembled output
	 */

	public function getDebugString($line_sep = "\n", $output_order = MsgList::NEW_FIRST, $line_numbers = true)
	{
		$this->debug_stack->setOutputOrder($output_order);
		$this->debug_stack->setLineNumbers($line_numbers);
		$this->debug_stack->setLineSep($line_sep);

		return $this->debug_stack->stringify();
	}

	/**
	 * Returns a formatted error string from the stack of collected Plan messages.
	 *
	 * @param string $line_sep The string to use as a line seperator, can be HTML, etc.
	 * @param int $output_order Order of assembly, either NEW_FIRST (Newest First) or OLD_FIRST (Oldest First)
	 * @param bool $line_numbers True if line numbers to be prepended, false if no line numbers needed.
	 *
	 * @return string The assembled output
	 */

	public function getPlanString($line_sep = "\n", $output_order = MsgList::NEW_FIRST, $line_numbers = true)
	{
		$this->plan_stack->setOutputOrder($output_order);
		$this->plan_stack->setLineNumbers($line_numbers);
		$this->plan_stack->setLineSep($line_sep);

		return $this->plan_stack->stringify();
	}

	/**
	 * Returns an array of the UNIQUE fields found in the Requesting section. It's
	 * the FLAT list of all fields.
	 *
	 * @return array An array of 0 or more elements of field name strings
	 */

	public function getRequestingFields()
	{
		return $this->unique_field_list;
	}

	/**
	* Quick check to see if field exists in CLQL table
	*
	* @param string $clql_name The string name of the field being checked
	*
	* @return mixed array if a valid field name, NULL if request not in the list not in the list
	*/

	public function getCLQLFldData($clql_field_name)
	{
		$this->pushDebugStr(__METHOD__, 2);

		if(isset(self::$fld_tbl[$clql_field_name]))
			return self::$fld_tbl[$clql_field_name];

		return NULL;
	}

	/**
	 * Returns the array of response data. Must have been processed
	 * by mapCLQLRec() and have valid results or this will return an
	 * empty array.
	 *
	 * @return array The array of records after mapping. Empty array if mapCLQLRec() failed or not called
	 */

	public function getRespData()
	{
		if($this->CLQL_resp_valid)
			return $this->CLQL_resp;
		return [];
	}

	/**
	 * Returns the array of response data. Must have been processed
	 * by mapCLQLRec() and have valid results or this will return an
	 * empty array.
	 *
	 * @return string The JSON conveted array of records. Empty string if mapCLQLRec() failed or not called
	 */

	public function getRespDataJSON()
	{
		if($this->CLQL_resp_valid)
			return json_encode($this->CLQL_resp);	// add any flags to the encoding here
		return '';
	}

	/**
	 * This returns the user token from the request. If the request is invalid or
	 * for some reast the user token is empty, this returns false, otherwise the token.
	 *
	 * @return mixed Returns the user token string if a valid request, otherwise FALSE
	 */

	public function getUserToken()
	{
		if(!$this->CLQL_req_valid || empty($this->user_token))
			return false;

		return $this->user_token;
	}

	/**
	 * Helper to get the System section. If not set, not found, invalid then
	 * will return empty array. This implies that validateCLQLRequest() has been called.
	 *
	 * @return array The sections data that is being requested
	 */

	public function getSystemSection()
	{
		if($this->CLQL_req_valid)
		{
			if(isset($this->CLQL_req[CLQL_SECTION_SYSTEM]))
				return $this->CLQL_req[CLQL_SECTION_SYSTEM];
		}

		return [];
	}

	/**
	 * Helper to get the Constraints section. If not set, not found, invalid then
	 * will return empty array. This implies that validateCLQLRequest() has been called.
	 *
	 * @return array The sections data that is being requested
	 */

	public function getConstraintsSection()
	{
		if($this->CLQL_req_valid)
		{
			if(isset($this->CLQL_req[CLQL_SECTION_CONSTRAINTS]))
				return $this->CLQL_req[CLQL_SECTION_CONSTRAINTS];
		}

		return [];
	}

	/**
	 * Helper to get the Score section. If not set, not found, invalid then
	 * will return empty array. This implies that validateCLQLRequest() has been called.
	 *
	 * @return array The sections data that is being requested
	 */

	public function getScoreSection()
	{
		if($this->CLQL_req_valid)
		{
			if(isset($this->CLQL_req[CLQL_SECTION_SCORE]))
				return $this->CLQL_req[CLQL_SECTION_SCORE];
		}

		return [];
	}

	/**
	 * Helper to get the Constraints section. If not set, not found, invalid then
	 * will return empty array. This implies that validateCLQLRequest() has been called.
	 *
	 * @return array The sections data that is being requested
	 */

	public function getFetchSection()
	{
		if($this->CLQL_req_valid)
		{
			if(isset($this->CLQL_req[CLQL_SECTION_FETCH]))
				return $this->CLQL_req[CLQL_SECTION_FETCH];
		}

		return [];
	}

	/**
	 * Helper to get the Requesting section. If not set, not found, invalid then
	 * will return empty array. This implies that validateCLQLRequest() has been called.
	 *
	 * @return array The sections data that is being requested
	 */

	public function getRequestingSection()
	{
		if($this->CLQL_req_valid)
		{
			if(isset($this->CLQL_req[CLQL_SECTION_REQUESTING]))
				return $this->CLQL_req[CLQL_SECTION_REQUESTING];
		}

		return [];
	}

	/**
	 * Wrappers record accessors. May be overkill, but aids in debug
	 */


	/**
	 * Gets the CLQL Data Source locations, only call with VALID record
	 *
	 * @param array $clql_rec A record from the fld_tbl
	 *
	 * @return int CLQL_SRC_FLAT_FILE, or CLQL_SRC_COMPUTED (See CLQLConst.php)
	 */

	public function getCLQLSource($clql_rec)
	{
		$this->pushDebugStr(__METHOD__, 3);

		return $clql_rec[CLQL_SOURCE];
	}

	/**
	 * Gets the CLQL Data type, only call with VALID record
	 *
	 * @param array $clql_rec A record from the fld_tbl
	 *
	 * @return int const CLQL_BOOL_TYPE, CLQL_INT_TYPE, CLQL_NUMERIC_TYPE, CLQL_STRING_TYPE (See CLQLConst.php)
	 */

	public function getCLQLType($clql_rec)
	{
		$this->pushDebugStr(__METHOD__, 3);

		return $clql_rec[CLQL_TYPE];
	}

	/**
	 * Gets the CLQL Default Data string, only call with VALID record
	 *
	 * @param array $clql_rec A record from the fld_tbl
	 *
	 * @return string The default value as a string.
	 */

	public function getCLQLDefault($clql_rec)
	{
		$this->pushDebugStr(__METHOD__, 3);

		return $clql_rec[CLQL_DEFAULT];
	}

	/**
	 * Gets the CLQL Default value by field name. If not field found or
	 * an empty string is specified ('') for the default value then
	 * the field will be treated as NOT having a default value
	 *
	 * @param string $fld The field name of interest
	 *
	 * @return mixed Returns FALSE if no field match or field has an empty() value, otherwise the string
	 */

	public function getCLQLDefaultValue($fld)
	{
		$this->pushDebugStr(__METHOD__, 2);

		if(($rec = $this->getCLQLFldData($fld)) !== NULL)
		{
			$default_val = $this->getCLQLDefault($rec);

			if(!empty($default_val))
				return $default_val;
		}

		return false;
	}

	/**
	 * Gets the CLQL Valid Elements, only call with VALID record
	 *
	 * @param array $clql_rec A record from the fld_tbl
	 *
	 * @return array An array of valid elements, array elements can be numeric or string, empty array if NO values
	 *
	 * This is used to validate list types, if the array is empty no explict validation is done,
	 * ie Make field would allow any make and later on would have to be validated by the db or some other
	 * means. For simple and limited list such as body type it could be used.
	 */

	public function getCLQLValidElements($clql_rec)
	{
		$this->pushDebugStr(__METHOD__, 3);

		return $clql_rec[CLQL_VALID_ELEMENTS];
	}

	/**
	 * Gets the CLQL Sortable, only call with VALID record
	 *
	 * @param array $clql_rec A record from the fld_tbl
	 *
	 * @return bool True if the field is one that can be specified in the sort section, false if not
	 */

	public function getCLQLSortable($clql_rec)
	{
		$this->pushDebugStr(__METHOD__, 3);

		return (bool) $clql_rec[CLQL_SORTABLE]; // force bool return, data best be bool
	}

	/**
	 * Gets the CLQL Scoreable, only call with VALID record
	 *
	 * @param array $clql_rec A record from the fld_tbl
	 *
	 * @return bool True if the field is one that can be specified in the score(able) section, false if not
	 */

	public function getCLQLScoreable($clql_rec)
	{
		$this->pushDebugStr(__METHOD__, 3);

		return (bool) $clql_rec[CLQL_SCOREABLE]; // force bool return, data best be bool
	}

	/**
	 * Sortable Check by Field Name
	 *
	 * @param string $clql_field_name The name of the field we want to check
	 *
	 * @return bool True if the field is one that can be specified in the fetch:score element, false if not
	 */

	public function isSortableField($clql_field_name)
	{
		$this->pushDebugStr(__METHOD__, 2);

		if(($rec = $this->getCLQLFldData($clql_field_name)) === NULL)
			return false;
		return $this->getCLQLSortable($rec);   // returns bool
	}

	/**
	 * Scorable Check by Field Name
	 *
	 * @param string $clql_field_name The name of the field we want to check
	 *
	 * @return bool True if the field is one that can be specified in the score(able) section, false if not
	 */

	public function isScoreableField($clql_field_name)
	{
		$this->pushDebugStr(__METHOD__, 2);

		if(($rec = $this->getCLQLFldData($clql_field_name)) === NULL)
			return false;

		return $this->getCLQLScoreable($rec);   // returns bool
	}

	/**
	 * Checks to see if the record can be a member to the Constraint section
	 *
	 * @param array $clql_rec A record from the fld_tbl
	 *
	 * @return bool True if the field is one that can be specified in the Constraints section, false if not
	 */

	public function hasVaildConstraintType($clql_rec)
	{
		$this->pushDebugStr(__METHOD__, 2);

		return $clql_rec[CLQL_CONSTRAINT_TYPE] != CLQL_CONSTRAINT_INVALID;
	}

	/**
	 * Given a section name, will return it's section
	 *
	 * @param array $section The section to get the Id from
	 *
	 * @return array The section and it's content, NULL if invalid or not found
	 */

	public function getSection($req, $section_name)
	{
		$this->pushDebugStr(__METHOD__, 2);

		if(isset($req[$section_name]))
			return $req[$section_name];
		return NULL;
	}

	//////////////////////////////////////////////////////////
	/////////////// FIELD MAPPING HELPERS ////////////////////
	//////////////////////////////////////////////////////////

	/**
	 * Helper to dump the field mapping/validation table
	 *
	 * @return array The array of records (with possible embedded arrays). See definition
	 * for more information on the structure
	 */

	public function getFieldsTable()
	{
		return self::$fld_tbl;
	}

	/**
	 * Returns the entire external (user) to internal mapping table.
	 *
	 * @return string simple map of external name to internal (string)
	 */

	public function getExternalFieldMap()
	{
		return $this->extern_to_intern_map;
	}

	/**
	 * Returns the entire internal to external mapping table
	 *
	 * @return array simple map of internal to external [array of name and data source]
	 */

	public function getInternalFieldMap()
	{
		return $this->intern_to_extern_map;
	}

	/**
	 * Maps the external (user) field to internal field name
	 *
	 * @param string $external_fld_name
	 *
	 * @return string The internal field name. If not found returns FALSE
	 */

	public function mapExternalFldToInternal($external_fld_name)
	{
		if(isset($this->extern_to_intern_map[$external_fld_name]))
			return $this->extern_to_intern_map[$external_fld_name];
		return false;
	}

	/**
	 * Maps the internal field name to the external. Note this only
	 * does the field mapping, it does not return the data source.
	 *
	 * @param string $intern_fld_name The internal field name (db, etc)
	 *
	 * @return string The external field name only. If not found returns FALSE
	 */

	public function mapInternalFldToExternal($internal_fld_name)
	{
		if(isset($this->intern_to_extern_map[$internal_fld_name]['external_name']))
			return $this->intern_to_extern_map[$internal_fld_name]['external_name'];
		return false;
	}

	/**
	 * Gets the mapping of the users body type to internal body type
	 *
	 * @param string $body_type The user specified body type
	 *
	 * @return string Returns the name of the internally used body type or FALSE if not found
	 */

	public function mapExternalBodyType($body_type)
	{
		if(isset(self::$body_type_map[$body_type]))
			return self::$body_type_map[$body_type];
		return false;
	}

	/**
	 * Gets the mapping of the users body size to internal body size
	 *
	 * @param string $body_size The user specified body size
	 *
	 * @return string Returns the name of the internally used body size or FALSE if not found
	 */

	public function mapExternalBodySize($body_size)
	{
		if(isset(self::$body_size_map[$body_size]))
			return self::$body_size_map[$body_size];
		return false;
	}

	/**
	 * Gets the mapping of the users body style to internal body style
	 *
	 * @param string $body_style The user specified body style
	 *
	 * @return string Returns the name of the internally used body type or FALSE if not found
	 */

	public function mapExternalBodyStyle($body_style)
	{
		if(isset(self::$body_style_map[$body_style]))
			return self::$body_style_map[$body_style];
		return false;
	}

	//////////////////////////////////////////////////////////
	///////////////// SECTION VALIDATION /////////////////////
	//////////////////////////////////////////////////////////

	/**
	 * Validates the System Section element
	 *
	 * @param array $section The section we want to validate
	 *
	 * @return bool True if section is valid, false if not.
	 *
	 * This generates an error and debug data in respective objects.
	 *
	 * Note : 	The System section should be verified first so any needed
	 * 			system settings can be applied. The only tricky part is setting
	 * 			the Debug state, which either must be done programatically or
	 * 			this will not generate debug  info until the debug (or other flags) are set.
	 *
	 * Required System Data:
	 *
	 * 			A user_token MUST be present in the structure as a 32 character MD5 style Id.
	 * 			Authentication is not done here, left to the caller to do something. This is not
	 * 			really a way to do auth, but mainly tracking.
	 */

	public function isValidSystemSection($section)
	{
		$this->pushDebugStr(__METHOD__, 1);

		$found_token = false;

		if(count($section) == 0)
		{
			$this->pushErrorId(CLQL_STATUS_ERROR_BOOL_EXPECTED);
			$this->pushErrorId(CLQL_STATUS_INVALID_SYSTEM_FIELD);
			$this->pushErrorId(CLQL_STATUS_EMPTY_SECTION);
			return false; // empty section
		}

		foreach($section as $key => $value)
		{
			// check for array on $value

			if(is_array($value))
			{
				$this->pushErrorStr('-->`' . $key . '`  Value Can\'t be an array or structure');
				$this->pushErrorId(CLQL_STATUS_INVALID_SYSTEM_FIELD);
				return false;
			}

			// each case is an allowable element

			switch($key)
			{
				case CLQL_SYSTEM_VERBOSE:
					if(!is_bool($value))
					{
						$this->pushErrorStr('-->`'. $key .'` field is the offender');
						$this->pushErrorId(CLQL_STATUS_ERROR_BOOL_EXPECTED);
						$this->pushErrorId(CLQL_STATUS_INVALID_SYSTEM_FIELD);
						return false;
					}
					break;
				case CLQL_SYSTEM_PLAN:
					if(!is_bool($value))
					{
						$this->pushErrorStr('-->`'. $key .'` field is the offender');
						$this->pushErrorId(CLQL_STATUS_ERROR_BOOL_EXPECTED);
						$this->pushErrorId(CLQL_STATUS_INVALID_SYSTEM_FIELD);
						return false;
					}
					break;

				case CLQL_SYSTEM_DEBUG:
					if(!is_bool($value))
					{
						$this->pushErrorStr('-->`'. $key .'` field is the offender');
						$this->pushErrorId(CLQL_STATUS_ERROR_BOOL_EXPECTED);
						$this->pushErrorId(CLQL_STATUS_INVALID_SYSTEM_FIELD);
						return false;
					}

					$this->debug_enabled = $value;	// set the debug_state
					break;

				case CLQL_SYSTEM_USER_TOKEN:
					if(!is_string($value) || strlen($value) != 32)
					{
						$this->pushErrorStr('-->`'. $value .'` Bad Token Format');
						$this->pushErrorId(CLQL_STATUS_ERROR_STRING_EXPECTED);
						$this->pushErrorId(CLQL_STATUS_INVALID_USER_TOKEN_FORMAT);
						return false;
					}

					$this->user_token = $value;		// set it!
					$found_token = true;
					break;

				default:
					$this->pushErrorStr('-->`'. $key .'` field is the offender');
					$this->pushErrorId(CLQL_STATUS_INVALID_SYSTEM_FIELD);
					return false;   // invalid system element
			}
		}

		if($found_token)
			return true;
		else
		{
			// if no token specified you need to bounce them
			$this->pushErrorId(CLQL_STATUS_INVALID_USER_TOKEN);
			return false;
		}
	}

	/**
	 * Validates the Constraints Section element
	 *
	 * @param array $section The section we want to validate
	 *
	 * @return bool True if section is valid, false if not.
	 *
	 * This generates an error and debug data in respective objects.
	 */

	public function isValidConstraintsSection($section)
	{
		$this->pushDebugStr(__METHOD__, 1);

		if(count($section) == 0)
		{
			$this->pushErrorId(CLQL_STATUS_INVALID_CONSTRAINT_FIELD);
			$this->pushErrorId(CLQL_STATUS_EMPTY_SECTION);
			return false; // empty section
		}

		foreach($section as $key => $value)
		{
			// key is the name of the field

			if(!is_string($key))  // must only be a string
			{
				$this->pushErrorStr('--> Expecting String Field Name');
				$this->pushErrorId(CLQL_STATUS_INVALID_CONSTRAINT_STRUCTURE);
				return false;
			}

			// check for valid field names, get the CLQLRec which as a wealth of info

			if(($CLQLRec = $this->getCLQLFldData($key)) === NULL)
			{
				$this->pushErrorStr('-->`'. $key .'`');
				$this->pushErrorId(CLQL_STATUS_INVALID_CONSTRAINT_FIELD);
				return false;
			}

			// check to see if the CLQL record allows this in the `constraints` section
			if(!$this->hasVaildConstraintType($CLQLRec))
			{
				$this->pushErrorStr('-->`'. $key .'`');
				$this->pushErrorId(CLQL_STATUS_INVALID_CONSTRAINT_FIELD);
				return false;
			}

			// now based on the CLQL record validate the range/list specifier
			if($CLQLRec[CLQL_CONSTRAINT_TYPE] == CLQL_CONSTRAINT_LIST_TYPE)
			{
				$cnt = count($value);

				// must have some data
				if($cnt == 0)
				{
					$this->pushErrorStr('-->`'. $key .'`');
					$this->pushErrorId(CLQL_STATUS_ERROR_EMPTY_CONSTRAINT_LIST);
					return false;
				}

				// then again, not too much
				if($cnt > CLQL_DEFAULT_MAX_CONSTRAINT_LIST_LEN)
				{
					$this->pushErrorStr('-->`'. $key .'`');
					$this->pushErrorId(CLQL_STATUS_ERROR_MAX_CONSTRAINT_LIST);
					return false;
				}

				// get the valid list elements list, if nothing in it should be empty list
				// this ONLY applies to the CLQL_STRING_TYPE's. List should be ALL LOWER so
				// folding the user data to lower case can work. Think like SQL query

				$valid_list_elements = $CLQLRec[CLQL_VALID_ELEMENTS];

				// scan and validate type
				foreach ($value as $key1 => $value1)
				{
					if(is_array($value1))
					{
						$this->pushErrorStr('-->` Offset '  . $key1 . '`  Value Can\'t be an array or structure');
						$this->pushErrorId(CLQL_STATUS_INVALID_SYSTEM_FIELD);
						return false;
					}

					switch($CLQLRec[CLQL_TYPE])
					{

						case CLQL_INT_TYPE:
							if(!is_int($value1))
							{
								$this->pushErrorStr('-->`'. $value1 . '` ' . gettype($value1) . ' Found, Expecting Integer');
								$this->pushErrorStr('-->`'. $key .'` List');
								$this->pushErrorId(CLQL_STATUS_INVALID_CONSTRAINT_DATA_TYPE);
								return false;
							}
							break;

						case CLQL_NUMERIC_TYPE:   //  int or float will slide here
							if(!(is_int($value1) || is_float($value1)))
							{
								$this->pushErrorStr('-->`'. $value1 . '` ' . gettype($value1) . ' Found, Expecting Numeric');
								$this->pushErrorStr('-->`'. $key .'` List');
								$this->pushErrorId(CLQL_STATUS_INVALID_CONSTRAINT_DATA_TYPE);
								return false;
							}
							break;

						case CLQL_STRING_TYPE:
							if(!(is_string($value1)))
							{
								$this->pushErrorStr('-->`'. $value1 . '` ' . gettype($value1) . ' Found,  Expecting String');
								$this->pushErrorStr('-->`'. $key .'` List');
								$this->pushErrorId(CLQL_STATUS_INVALID_CONSTRAINT_DATA_TYPE);
								return false;
							}

							// strings MUST match known elements in the list for the CLQLRec IF
							// the list is not empty, otherwise skip for empty

							if(!empty($valid_list_elements))
								if(!isset($valid_list_elements[strtolower($value1)]))
								{
									$this->pushErrorStr('-->`'. $value1 . '` Unknown Element Value');
									$this->pushErrorStr('-->`'. $key .'` List');
									$this->pushErrorId(CLQL_STATUS_INVALID_LIST_ITEM);
									return false;
								}
							break;

						case CLQL_BOOL_TYPE:    // not currently supported
						default:
							$this->pushErrorStr('-->`'. $key1 .'`');
							$this->pushErrorStr(gettype($value1) . ' Type Not Supported');
							$this->pushErrorId(CLQL_STATUS_ERROR_CORRUPT_CONSTRAINT_LIST);
							return false;
					}
				}
			}
			else
			{
				$found_from = $found_to = false;

				foreach($value as $range_type => $range_val)
				{

					//echo "Range Type : " . $range_type . "<br>";
					//echo "Range Val : " . $range_val . "<br>";

					// check for array on range_type

					if(is_array($range_val))
					{
						$this->pushErrorStr('-->`' . $key . '` Range Value Can\'t be an array or structure');
						$this->pushErrorId(CLQL_STATUS_INVALID_CONSTRAINT_DATA_TYPE);
						return false;
					}

					switch($range_type)
					{
						case CLQL_CONSTRAINT_RANGE_FROM:
							$found_from = true;
						case CLQL_CONSTRAINT_RANGE_TO:
							$found_to = true;

							if(!(is_int($range_val) || is_float($range_val)))
							{
								$this->pushErrorStr('-->`'. $range_val  .'` ' . gettype($range_val) . ' Found, Expecting Numeric');
								$this->pushErrorStr('-->`' . $key . '` Range');
								$this->pushErrorId(CLQL_STATUS_INVALID_CONSTRAINT_DATA_TYPE);
								return false;
							}
							break;

						default:
							// Invalid range type (it's not `from` or `to`)
							$this->pushErrorStr('-->`' . $range_type . '` Unknown Range Specifier');
							$this->pushErrorStr('-->`' . $key . '` Range');
							$this->pushErrorId(CLQL_STATUS_INVALID_RANGE);
							return false;
					}

				}

				// also catch empty structure
				if(!($found_from || $found_to))
				{
					$this->pushErrorStr('-->`' . $key . '` Range has no `from` or `to` values');
					$this->pushErrorId(CLQL_STATUS_ERROR_EMPTY_CONSTRAINT_RANGE);
					return false;
				}

			}
		}
		return true;
	}

	/**
	 * Validates the Fetch Section element
	 *
	 * @param array $section The section we want to validate
	 *
	 * @return bool True if section is valid, false if not.
	 *
	 * This generates an error and debug data in respective objects.
	 */

	public function isValidFetchSection($section)
	{
		$this->pushDebugStr(__METHOD__, 1);

		if(count($section) == 0)
		{
			$this->pushErrorId(CLQL_STATUS_INVALID_FETCH_FIELD);
			$this->pushErrorId(CLQL_STATUS_EMPTY_SECTION);
			return false; // empty section
		}

		foreach($section as $key => $value)
		{
			// check here for array in value

			if(is_array($value))
			{
				$this->pushErrorStr('-->`' . $key . '` Field Value Can\'t be an array or structure');
				$this->pushErrorId(CLQL_STATUS_INVALID_SORT_FIELD);
				return false;
			}

			switch($key)
			{
				case CLQL_FETCH_SORT:
					// check for field name
					if(!$this->isSortableField($value))
					{
						$this->pushErrorStr('-->`'. $value .'`');
						$this->pushErrorId(CLQL_STATUS_INVALID_SORT_FIELD);
						return false;
					}
					break;

				case CLQL_FETCH_LIMIT:
				case CLQL_FETCH_OFFSET:
					// see if type is int, otherwise fail em'
					if(!is_int($value))
					{
						$this->pushErrorStr('-->`'. $value . '` ' . gettype($value) . ' Found, Expecting Integer');
						$this->pushErrorId(CLQL_STATUS_ERROR_INT_EXPECTED);
						$this->pushErrorId(CLQL_STATUS_INVALID_FETCH_FIELD);
						return false;
					}
					break;

				default:
					$this->pushErrorStr('-->`'. $key .'`');
					$this->pushErrorId(CLQL_STATUS_INVALID_FETCH_FIELD);
					return false;   // invalid system element
			}
		}

		return true;
	}

	/**
	 * Validates the Score Section element
	 *
	 * @param array $section The section we want to validate
	 *
	 * @return bool True if section is valid, false if not.
	 *
	 * This generates an error and debug data in respective objects.
	 */

	public function isValidScoreSection($section)
	{
		$this->pushDebugStr(__METHOD__, 1);

		if(count($section) == 0)
		{
			$this->pushErrorId(CLQL_STATUS_INVALID_SCORE_FIELD);
			$this->pushErrorId(CLQL_STATUS_EMPTY_SECTION);
			return false; // empty section
		}

		foreach($section as $key => $value)
		{
			// only field names with scalars here

			if(is_array($value))
			{
				$this->pushErrorStr('-->`' . $key . '` Score Value Can\'t be an array or structure');
				$this->pushErrorId(CLQL_STATUS_INVALID_SCORE_FIELD);
				return false;
			}


			if(!$this->isScoreableField($key))
			{
				$this->pushErrorStr('-->`'. $key .'`');
				$this->pushErrorId(CLQL_STATUS_INVALID_SCORE_FIELD);
				return false;
			}

			// add float type check if needed

			if(!is_int($value))
			{
				$this->pushErrorStr('-->`'. $value . '` ' . gettype($value) . ' Found, Expecting Integer');
				$this->pushErrorId(CLQL_STATUS_ERROR_INT_EXPECTED);
				$this->pushErrorId(CLQL_STATUS_INVALID_SCORE_FIELD);
				return false;
			}
		}

		return true;
	}

	/**
	 * A recursive function that will flatten out the requesting fields. This is
	 * just to validate fields. It has checks for depth limits and list limits. This is a
	 * helper for the isValidRequestingSection() call.
	 *
	 * @param array $section The section to crawl. Will recurse if nested array is found
	 * @param array &$fields An initialized list (typically empty array) that will hold unique list of fields
	 * @param int &$status passed in status with a start of CLQL_STATUS_OK on initial call. Used to short circuit recursion on error.
	 *
	 */

	private function recurseRequestingSection($section, &$fields, &$status)
	{
		static $recurse_cnt = 0;

		$this->pushDebugStr(__METHOD__, 2 + $recurse_cnt);

		// bad status, bust out until done. Means also status must be init
		// to CLQL_STATUS_OK on initial call if that's not obvious

		if($status !== CLQL_STATUS_OK)
		{
			$recurse_cnt = 0;   // make sure!
			return;
		}

		foreach($section as $key=>$value)
		 {
			if(is_array($value))
			{
				$skip_outer = array_shift($value);

				if($recurse_cnt > CLQL_DEFAULT_NESTING_LEVEL)
				{
					 $status = CLQL_STATUS_ERROR_NESTING_TOO_DEEP;
					 return;
				}

				$recurse_cnt++;
				$this->recurseRequestingSection($skip_outer, $fields, $status);
				$recurse_cnt--;
			}
			else
			{
				$fields[$value] = $value;   // cheat, save as key and value to make fast unique

				if(count($fields) > CLQL_DEFAULT_MAX_REQUEST_FIELDS)
				{
					$status = CLQL_STATUS_ERROR_TOO_MANY_FIELDS;
					return;
				}
			}
		}

	}

	/**
	 * Validates the Requesting Section element
	 *
	 * @param array $section The section we want to validate
	 *
	 * @return bool True if section is valid, false if not.
	 *
	 * This generates an error and debug data in respective objects.
	 *
	 * Note : This mutates the $unique_field_list member var. After this call it will have a list
	 * of UNIQUE field names in it. This DOES NOT RESET THE $field_list array, that's up to you
	 * if you need it clean. Since this is really a list of unique fields, it's all good.
	 */

	public function isValidRequestingSection($section)
	{
		$this->pushDebugStr(__METHOD__, 1);

		if(count($section) == 0)
		{
			$this->pushErrorId(CLQL_STATUS_INVALID_REQUEST_FIELD);
			$this->pushErrorId(CLQL_STATUS_EMPTY_SECTION);
			return false; // empty section
		}

		// flatten the section, can have multiple nesting

		$status = CLQL_STATUS_OK;	// MUST start with this status

		// caution as the name suggessts 'recursion', $this->field list set to [] in the constructor.

		$this->recurseRequestingSection($section, $this->unique_field_list, $status);

		if($status !== CLQL_STATUS_OK)
		{
			$this->pushErrorId($status);
			return false;
		}

		// now validate each F'n field in the list (remeber it's unique)

		foreach($this->unique_field_list as $value)
		{
			if($this->getCLQLFldData($value) === NULL)
			{
				$this->pushErrorStr('-->`'. $value .'`');
				$this->pushErrorId(CLQL_STATUS_INVALID_REQUESTING_FIELD);
				return false;
			}
		}

		return true;
	}

	//////////////////////////////////////////////////////////
	///////////////// REQUEST VALIDATION /////////////////////
	//////////////////////////////////////////////////////////

	/**
	 * This is the outermost JSON validation routine. It takes a JSON string
	 * and does all sorts of validation on it. Error messages and Debug Messages
	 * are tracked for each call. It's up to the user to reset these prior to calling
	 * if desired.
	 *
	 * @param string $clql_json_str The CLQL strucure for the request, again note it's a string!
	 */

	public function validateCLQLRequest($clql_json_str)
	{
		$this->pushDebugStr(__METHOD__, 0);

		$this->CLQL_req_valid = false;
		$found_constraint = false;
		$found_requesting = false;

		$this->CLQL_req = json_decode($clql_json_str, true);

		if($this->CLQL_req === NULL)
		{
			$this->pushErrorStr('-->`'. json_last_error_msg() .'`');
			$this->pushErrorId(CLQL_STATUS_MAL_FORMED_JSON);
			return false;
		}

		if(empty($this->CLQL_req))
		{
			$this->pushErrorId(CLQL_STATUS_MISSING_SECTION);  // well no sections!
			return false;
		}

		$section_cnt = count($this->CLQL_req);

		// can't have more then 5 section and MUST have at least 3 (system, constraints, requesting) or doesnt make sense

		if(($section_cnt > 5) || ($section_cnt < 3))
		{
			$this->pushErrorId(CLQL_STATUS_INVALID_SECTION);
			return false;
		}

		// pull the system section first as we may want to affect other stuff so
		// ensure these get loaded FIRST no matter where it is in the JSON struct
		// A valid formatted user_token MUST be specified. This will NOT verify the token,
		// just capture it.

		if(isset($this->CLQL_req[CLQL_SECTION_SYSTEM]))
		{
			if(!$this->isValidSystemSection($this->CLQL_req[CLQL_SECTION_SYSTEM]))   // can load system vars
			{
				$this->pushDebugStr(__METHOD__);
				$this->pushErrorId(CLQL_STATUS_INVALID_SYSTEM_SECTION);
				return false;
			}
		}

		// now loop the sections and check for invalid sections

		foreach($this->CLQL_req as $key => $value)
		{
			// maybe check if key is an array just for shagrins???

			switch($key)
			{
				case CLQL_SECTION_SYSTEM:   // already validated above, so skip
					break;

				case CLQL_SECTION_CONSTRAINTS:
					if(!$this->isValidConstraintsSection($value))
					{
						$this->pushErrorId(CLQL_STATUS_INVALID_CONSTRAINT_SECTION);
						return false;
					}
					$found_constraint = true;
					break;

				case CLQL_SECTION_SCORE:
					if(!$this->isValidScoreSection($value))
					{
						$this->pushErrorId(CLQL_STATUS_INVALID_SCORE_SECTION);
						return false;
					}
					break;

				case CLQL_SECTION_FETCH:
					if(!$this->isValidFetchSection($value))
					{
						$this->pushErrorId(CLQL_STATUS_INVALID_FETCH_SECTION);
						return false;
					}

					break;
				case CLQL_SECTION_REQUESTING:
					if(!$this->isValidRequestingSection($value))
					{
						$this->pushErrorId(CLQL_STATUS_INVALID_REQUESTING_SECTION);
						return false; // invalid system section
					}

					$found_requesting = true;
					break;

				default:
					$this->pushErrorStr('-->`'. $key .'`');
					$this->pushErrorId(CLQL_STATUS_UNKNOWN_SECTION);
					return false; // UNKNOW SECTION ERROR
			}
		}

		if($found_constraint && $found_requesting)
		{
			$this->CLQL_req_valid = true;
			return true;
		}
		else
		{
			$this->pushErrorStr('--> Missing Constraint or Requesting Section');
			$this->pushErrorId(CLQL_STATUS_MISSING_SECTION);
			return false;
		}
	}

	//////////////////////////////////////////////////////////
	/////////////////  Response Mapping  /////////////////////
	//////////////////////////////////////////////////////////

	/**
	 * This function will take the Requesting section (or parts of it) and map incoming data
	 * to the output array. The incoming $data is really a single record of query data that is
	 * processed against the Requesting section format.
	 *
	 * @param array $section The Requesting section array that is being processed. It will recurse this and
	 * treat any sub-section as a new sub-structure.
	 * @param array $data The query data to be mapped. This is a flat name=>value mapping.
	 * @param array &$output The entire record mapped (including sub-structures)
	 * @param int &$status passed in status with a start of CLQL_STATUS_OK on initial call. Used to short circuit recursion on error.
	 */

	private function recurseMapRequestingSection($section, $data, &$output, &$status)
	{
		static $recurse_cnt = 0;

		$this->pushDebugStr(__METHOD__, 1 + $recurse_cnt);

		// bad status, bust out until done. Means also status must be init
		// to CLQL_STATUS_OK on initial call if that's not obvious

		if($status !== CLQL_STATUS_OK)
		{
			$recurse_cnt = 0;   // make sure!
			return;
		}

		// roll through the Requesting section looking to nested structures
		// and fields.

		foreach($section as $key=>$value)
		{
			if(is_array($value))
			{
				// create a sub element named as found

				$nest_name = key($value);
				$output[$nest_name] =  [];

				// need to skip outer array to get name

				$skip_outer = array_shift($value);

				// paranoid check, should not be here if this is the case

				if($recurse_cnt > CLQL_DEFAULT_NESTING_LEVEL)
				{
					 $status = CLQL_STATUS_ERROR_NESTING_TOO_DEEP;
					 return;
				}

				$recurse_cnt++;
				$this->recurseMapRequestingSection($skip_outer, $data, $output[$nest_name], $status);
				$recurse_cnt--;
			}
			else
			{
				// value is the field name we need to map here
				// should only be mapping data for fields here
				//
				// NOTE : if the value for $data[$value] is empty it
				// this is where the look up into the $fld_tbl to see if it has one.
				// THIS IS A TBD if needed.

				if(!empty($data[$value]))
				{
					$output[$value] = $data[$value];	// save as 'key => value'
				}
				else
				{
					// see if in the default table

					if(($default_val = $this->getCLQLDefaultValue($value)) !== false)
						$output[$value] = $default_val;
				}
			}
		}
	}

	/**
	 * Will map a result set to the data format that is specified in the Resulting
	 * section. The mapping will omit any fields that are NOT found in the $query_results
	 * array. This must only be called if a validateCLQLRequest() has been done, and will
	 * check that the results that call were valid.
	 *
	 * @param array $query_results An array of records as provided by the query system. The
	 * array is a record with each element as a key=>value pair.
	 */

	public function mapCLQLRec($query_results)
	{
		$this->pushDebugStr(__METHOD__, 0);
		$this->CLQL_resp_valid = false;

		// If the requst is not valid, bounce them

		if(!$this->CLQL_req_valid)
		{
			$this->pushErrorId(CLQL_STATUS_ERROR_NO_VALID_REQUEST);
			return false;
		}

		if(!is_array($query_results))
		{
			// must be an array of records, if not error

			$this->pushErrorId(CLQL_STATUS_INVALID_DATA_RESPONSE);
			return false;
		}

		// if Query data is empty, just do a quick about-face and exit

		if(empty($query_results))
		{
			$this->CLQL_resp = [];	// return empty
			$this->CLQL_resp_valid = true;
			return true;
		}

		// if the requesting section is empty, bomb. Paranoid check
		// this might be relaxed to just return a flat record of all
		// fields in the query set, but only later if needed

		if(empty($this->CLQL_req[CLQL_SECTION_REQUESTING]))
		{
			$this->pushErrorId(CLQL_STATUS_INVALID_REQUESTING_SECTION);
			return false;
		}

		// recurse through the requesting section. If a nested struct is
		// found the recurseMapRestionSection() will handle it and
		// create the sub-element correctly. We are adding record by record here.

		$status = CLQL_STATUS_OK;	// MUST start with this status

		foreach($query_results as $query_rec)
		{
			$resp_rec = [];
			$this->recurseMapRequestingSection($this->CLQL_req[CLQL_SECTION_REQUESTING], $query_rec, $resp_rec, $status);
			$this->CLQL_resp[] = $resp_rec;	// Add record to list

			// if something went haywire in the recursion, cut the story short

			if($status !== CLQL_STATUS_OK)
			{
				$this->pushErrorId($status);
				return false;
			}
		}

		$this->CLQL_resp_valid = true;
		return true;
	}
}
