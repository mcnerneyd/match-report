<?php
if (!defined("JSON_HEX_TAG")) {
   define("JSON_HEX_TAG", 1);
   define("JSON_HEX_AMP", 2);
   define("JSON_HEX_APOS", 4);
   define("JSON_HEX_QUOT", 8);
   define("JSON_FORCE_OBJECT", 16);
 }
if (!defined("JSON_NUMERIC_CHECK")) {
   define("JSON_NUMERIC_CHECK", 32);      // 5.3.3
 }
if (!defined("JSON_UNESCAPED_SLASHES")) {
   define("JSON_UNESCAPED_SLASHES", 64);  // 5.4.0
   define("JSON_PRETTY_PRINT", 128);      // 5.4.0
   define("JSON_UNESCAPED_UNICODE", 256); // 5.4.0
 }
   function xjson_encode($var, $options=0, $_indent="") {
      global ${'.json_last_error'};
      ${'.json_last_error'} = JSON_ERROR_NONE;

      #-- prepare JSON string
      $obj = ($options & JSON_FORCE_OBJECT);
      list($_space, $_tab, $_nl) = ($options & JSON_PRETTY_PRINT) ? array(" ", "    $_indent", "\n") : array("", "", "");
      $json = "$_indent";
      
      if ($options & JSON_NUMERIC_CHECK and is_string($var) and is_numeric($var)) {
          $var = (strpos($var, ".") || strpos($var, "e")) ? floatval($var) : intval($var);
      }
      
      #-- add array entries
      if (is_array($var) || ($obj=is_object($var))) {

         #-- check if array is associative
         if (!$obj) {
            $keys = array_keys((array)$var);
            $obj = !($keys == array_keys($keys));   // keys must be in 0,1,2,3, ordering, but PHP treats integers==strings otherwise
         }

         #-- concat individual entries
         $empty = 0; $json = "";
         foreach ((array)$var as $i=>$v) {
            $json .= ($empty++ ? ",$_nl" : "")    // comma separators
                   . $_tab . ($obj ? (xjson_encode($i, $options & ~JSON_NUMERIC_CHECK, $_tab) . ":$_space") : "")   // assoc prefix
                   . (xjson_encode($v, $options, $_tab));    // value
         }

         #-- enclose into braces or brackets
         $json = $obj ? "{"."$_nl$json$_nl$_indent}" : "[$_nl$json$_nl$_indent]";
      }

      #-- strings need some care
      elseif (is_string($var)) {

         if (!utf8_decode($var)) {
            trigger_error("json_encode: invalid UTF-8 encoding in string, cannot proceed.", E_USER_WARNING);
            $var = NULL;
         }
         $rewrite = array(
             "\\" => "\\\\",
             "\"" => "\\\"",
           "\010" => "\\b",
             "\f" => "\\f",
             "\n" => "\\n",
             "\r" => "\\r", 
             "\t" => "\\t",
             "/"  => $options & JSON_UNESCAPED_SLASHES ? "/" : "\\/",
             "<"  => $options & JSON_HEX_TAG  ? "\\u003C" : "<",
             ">"  => $options & JSON_HEX_TAG  ? "\\u003E" : ">",
             "'"  => $options & JSON_HEX_APOS ? "\\u0027" : "'",
             "\"" => $options & JSON_HEX_QUOT ? "\\u0022" : "\"",
             "&"  => $options & JSON_HEX_AMP  ? "\\u0026" : "&",
         );
         $var = strtr($var, $rewrite);
         //@COMPAT control chars should probably be stripped beforehand, not escaped as here
         if (function_exists("iconv") && ($options & JSON_UNESCAPED_UNICODE) == 0) {
            $var = preg_replace("/[^\\x{0020}-\\x{007F}]/ue", "'\\u'.current(unpack('H*', iconv('UTF-8', 'UCS-2BE', '$0')))", $var);
         }
         $json = '"' . $var . '"';
      }

      #-- basic types
      elseif (is_bool($var)) {
         $json = $var ? "true" : "false";
      }
      elseif ($var === NULL) {
         $json = "null";
      }
      elseif (is_int($var)) {
         $json = "$var";
      }
      elseif (is_float($var)) {
         if (is_nan($var) || is_infinite($var)) {
            ${'.json_last_error'} = JSON_ERROR_INF_OR_NAN;
            return;
         }
         else {
            $json = "$var";
         }
      }

      #-- something went wrong
      else {
         trigger_error("json_encode: don't know what a '" .gettype($var). "' is.", E_USER_WARNING);
         ${'.json_last_error'} = JSON_ERROR_UNSUPPORTED_TYPE;
         return;
      }
      
      #-- done
      return($json);
   }
