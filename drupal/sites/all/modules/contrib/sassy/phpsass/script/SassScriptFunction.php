<?php
/**
 * SassScriptFunction class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 * @package      PHamlP
 * @subpackage  Sass.script
 */

require_once('SassScriptFunctions.php');

/**
 * SassScriptFunction class.
 * Preforms a SassScript function.
 * @package      PHamlP
 * @subpackage  Sass.script
 */
class SassScriptFunction {
  /**@#+
   * Regexes for matching and extracting functions and arguments
   */
  const MATCH = '/^(((-\w)|(\w))[-\w]*)\(/';
  const MATCH_FUNC = '/^((?:(?:-\w)|(?:\w))[-\w]*)\((.*)\)/';
  const SPLIT_ARGS = '/\s*((?:[\'"].*?["\'])|(?:.+?(?:\(.*\).*?)?))\s*(?:,|$)/';
  const NAME = 1;
  const ARGS = 2;

  private $name;
  private $args;

  public static $context;

  /**
   * SassScriptFunction constructor
   * @param string name of the function
   * @param array arguments for the function
   * @return SassScriptFunction
   */
  public function __construct($name, $args) {
    $this->name = $name;
    $this->args = $args;
  }

  private function process_arguments($input) {
    if (is_array($input)) {
      $output = array();
      foreach ($input as $token) {
        $output[] = trim($this->process_arguments($token), '\'"');
      }
      return $output;
    }

    $token = $input;
    if (is_null($token))
      return ' ';

    if (!is_object($token))
      return (string) $token;

    if (method_exists($token, 'toString'))
      return $token->toString();

    if (method_exists($token, '__toString'))
      return $token->__toString();

    if (method_exists($token, 'perform'))
      return $token->perform();

    return '';
  }

  /**
   * Evaluates the function.
   * Look for a user defined function first - this allows users to override
   * pre-defined functions, then try the pre-defined functions.
   * @return Function the value of this Function
   */
  public function perform() {
    self::$context = SassScriptParser::$context;

    $name = preg_replace('/[^a-z0-9_]/', '_', strtolower($this->name));
    $args = $this->process_arguments($this->args);


    try {
      if ($fn = SassScriptParser::$context->hasFunction($this->name)) {
        $return = SassScriptParser::$context->getFunction($this->name)->execute(SassScriptParser::$context, $this->args);
        return $return;
      }
      else if ($fn = SassScriptParser::$context->hasFunction($name)) {
        $return = SassScriptParser::$context->getFunction($name)->execute(SassScriptParser::$context, $this->args);
        return $return;
      }
    } catch (Exception $e) {
      throw $e;
    }

    if (isset(SassParser::$functions) && count(SassParser::$functions)) {
      foreach (SassParser::$functions as $fn => $callback) {
        if (($fn == $name || $fn == $this->name) && function_exists($callback)) {
          $result = call_user_func_array($callback, $args);
          if (is_string($result)) {
            $lexed = SassScriptLexer::$instance->lex($result, self::$context);
            // $parsed = SassScriptParser::$instance->parse($result, new SassContext());
            return new SassString(implode('', $this->process_arguments($lexed)));
          }
          return $result;
        }
      }
    }

    if (method_exists('SassScriptFunctions', $name)) {
      return call_user_func_array(array('SassScriptFunctions', $name), $this->args);
    }

    foreach ($this->args as $i => $arg) {
      if (is_object($arg) && isset($arg->quote)) {
        $args[$i] = $arg->quote . $args[$i] . $arg->quote;
      }
    }

    // CSS function: create a SassString that will emit the function into the CSS
    return new SassString($this->name . '(' . join(', ', $args) . ')');
  }

  /**
   * Imports files in the specified directory.
   * @param string path to directory to import
   * @return array filenames imported
   */
  private function import($dir) {
    $files = array();

    foreach (array_slice(scandir($dir), 2) as $file) {
      if (is_file($dir . DIRECTORY_SEPARATOR . $file)) {
        $files[] = $file;
        require_once($dir . DIRECTORY_SEPARATOR . $file);
      }
    } // foreach
    return $files;
  }

  /**
   * Returns a value indicating if a token of this type can be matched at
   * the start of the subject string.
   * @param string the subject string
   * @return mixed match at the start of the string or false if no match
   */
  public static function isa($subject) {
    if (!preg_match(self::MATCH, $subject, $matches))
      return false;

    $match = $matches[0];
    $paren = 1;
    $strpos = strlen($match);
    $strlen = strlen($subject);
    $subject_str = (string) $subject;

    while($paren && $strpos < $strlen) {
      $c = $subject_str[$strpos++];

      $match .= $c;
      if ($c === '(') {
        $paren += 1;
      }
      elseif ($c === ')') {
        $paren -= 1;
      }
    }
    return $match;
  }

  public static function extractArgs($string) {
    $args = array();
    $arg = '';
    $paren = 0;
    $strpos = 0;
    $strlen = strlen($string);

    while ($strpos < $strlen) {
      $c = $string[$strpos++];

      switch ($c) {
        case '(':
          $paren += 1;
          $arg .= $c;
          break;
        case ')':
          $paren -= 1;
          $arg .= $c;
          break;
        case '"':
        case "'":
          $arg .= $c;
          do {
            $_c = $string[$strpos++];
            $arg .= $_c;
          } while ($_c !== $c);
          break;
        case ',':
          if ($paren) {
            $arg .= $c;
            break;
          }
          $args[] = trim($arg);
          $arg = '';
          break;
        default:
          $arg .= $c;
          break;
      }
    }

    if ($arg!='') $args[] = trim($arg);
    return $args;
  }
}
