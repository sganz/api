<?php

/*
 * Global consts for CLQL, no namespace
 */

const CLQL_STATUS_OK = 0;
const CLQL_STATUS_INVALID_REQUEST = 1;
const CLQL_STATUS_INVALID_LIST_ITEM = 2;
const CLQL_STATUS_INVALID_RANGE = 3;
const CLQL_STATUS_MISSING_SECTION = 4;
const CLQL_STATUS_INVALID_SECTION = 5;
const CLQL_STATUS_UNKNOWN_SECTION = 6;
const CLQL_STATUS_EMPTY_SECTION = 7;
const CLQL_STATUS_MAL_FORMED_JSON = 8;

const CLQL_STATUS_INVALID_SORT_FIELD = 9;
const CLQL_STATUS_INVALID_CONSTRAINT_FIELD = 10;
const CLQL_STATUS_INVALID_SYSTEM_FIELD = 11;
const CLQL_STATUS_INVALID_SCORE_FIELD = 12;
const CLQL_STATUS_INVALID_FETCH_FIELD = 13;
const CLQL_STATUS_INVALID_REQUESTING_FIELD = 14;
const CLQL_STATUS_INVALID_CONSTRAINT_STRUCTURE = 15;
const CLQL_STATUS_INVALID_CONSTRAINT_DATA_TYPE = 16;
const CLQL_STATUS_INVALID_SYSTEM_SECTION = 17;
const CLQL_STATUS_INVALID_CONSTRAINT_SECTION = 18;
const CLQL_STATUS_INVALID_SCORE_SECTION = 19;
const CLQL_STATUS_INVALID_FETCH_SECTION = 20;
const CLQL_STATUS_INVALID_REQUESTING_SECTION = 21;

const CLQL_STATUS_ERROR_BOOL_EXPECTED = 22;
const CLQL_STATUS_ERROR_INT_EXPECTED = 23;
const CLQL_STATUS_ERROR_NUMERIC_EXPECTED = 24;
const CLQL_STATUS_ERROR_STRING_EXPECTED = 25;
const CLQL_STATUS_ERROR_NESTING_TOO_DEEP = 26;
const CLQL_STATUS_ERROR_TOO_MANY_FIELDS = 27;
const CLQL_STATUS_ERROR_EMPTY_CONSTRAINT_LIST = 28;
const CLQL_STATUS_ERROR_EMPTY_CONSTRAINT_RANGE = 29;
const CLQL_STATUS_ERROR_MAX_CONSTRAINT_LIST = 30;
const CLQL_STATUS_ERROR_CORRUPT_CONSTRAINT_LIST = 31;
const CLQL_STATUS_MISSING_RANGE = 32;
const CLQL_STATUS_DUPE_CONSTRAINT_FIELD = 33;
const CLQL_STATUS_DUPE_REQUESTING_FIELD = 34;

const CLQL_STATUS_OZOB_ERROR = 99999;

// Data Sources
const CLQL_SRC_FLAT_FILE = 0;
const CLQL_SRC_COMPUTED = 1;

// Data Types (Not all curretly supported)
const CLQL_BOOL_TYPE = 0;
const CLQL_INT_TYPE = 1;
const CLQL_NUMERIC_TYPE = 2;
const CLQL_STRING_TYPE = 3;

// Constraint Types
const CLQL_CONSTRAINT_RANGE_TYPE = 0;
const CLQL_CONSTRAINT_LIST_TYPE = 1;
const CLQL_CONSTRAINT_INVALID = 2;  // can't query on it

// Range Type names
const CLQL_CONSTRAINT_RANGE_FROM = 'from';
const CLQL_CONSTRAINT_RANGE_TO = 'to';

// Field Offsets in array
const CLQL_SRC_FLD_NAME = 0;
const CLQL_SOURCE = 1;
const CLQL_TYPE = 2;
const CLQL_CONSTRAINT_TYPE = 3;
const CLQL_DEFAULT = 4;
const CLQL_VALID_ELEMENTS = 5;
const CLQL_SORTABLE = 6;
const CLQL_SCOREABLE = 7;

// Allowable Sections in a request,  which are the outmost keys in the request
// checks will be case SeNsItIvE so incoming JSON string must match these
const CLQL_SECTION_SYSTEM = 'system';
const CLQL_SECTION_CONSTRAINTS = 'constraints';
const CLQL_SECTION_SCORE = 'score';
const CLQL_SECTION_FETCH = 'fetch';
const CLQL_SECTION_REQUESTING = 'requesting';

// system section defined fixed elements/fields (all optional)
const CLQL_SYSTEM_VERBOSE = '_verbose';
const CLQL_SYSTEM_PLAN = '_plan';
const CLQL_SYSTEM_DEBUG = '_debug';

// fetch section defined fixed elements/fields (all optional)
const CLQL_FETCH_SORT = 'sort';
const CLQL_FETCH_LIMIT = 'limit';
const CLQL_FETCH_OFFSET = 'offset';

// defaults
const CLQL_DEFAULT_LIMIT = 10;
const CLQL_DEFAULT_OFFSET = 0;
const CLQL_DEFAULT_DEBUG = false;
const CLQL_DEFAULT_VERBOSE = false;
const CLQL_DEFAULT_PLAN = false;
const CLQL_DEFAULT_SORT = 'overall_score';  // not sure how to represent this...
const CLQL_DEFAULT_NESTING_LEVEL = 5;       // max number of nested structures
const CLQL_DEFAULT_MAX_REQUEST_FIELDS = 50; // total number (including nested structures)
const CLQL_DEFAULT_MAX_CONSTRAINT_LIST_LEN = 20;    // max length of list type of data

