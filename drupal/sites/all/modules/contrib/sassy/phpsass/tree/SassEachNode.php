<?php
/* SVN FILE: $Id$ */
/**
 * SassEachNode class file.
 * The syntax is:
 * <pre>@each <var> in <list><pre>.
 *
 * <list> is comma+space separated
 * <var> is available to the rest of the script following evaluation
 * and has the value that terminated the loop.
 *
 * @author  Pavol (Lopo) Hluchy <lopo@losys.eu>
 * @copyright  Copyright (c) 2011 Lopo
 * @license  http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 * @package  PHamlP
 * @subpackage  Sass.tree
 */

/**
 * SassEachNode class.
 * Represents a Sass @each loop.
 * @package  PHamlP
 * @subpackage  Sass.tree
 */
class SassEachNode extends SassNode {
  const MATCH = '/@each\s+[!\$](.+?)in\s+(.+)$/i';

  const VARIABLE = 1;
  const IN = 2;

  /**
   * @var string variable name for the loop
   */
  private $variable;
  /**
   * @var string expression that provides the loop values
   */
  private $in;

  /**
   * SassEachNode constructor.
   * @param object source token
   * @return SassEachNode
   */
  public function __construct($token) {
    parent::__construct($token);
    if (!preg_match(self::MATCH, $token->source, $matches)) {
      if ($GLOBALS['SassParser_debug']) {
        throw new SassEachNodeException('Invalid @each directive', $this);
      }
    }
    else {
      $this->variable = trim($matches[self::VARIABLE]);

      if (count($bits = explode(',', $this->variable)) > 1) {
        $this->variable = trim(array_pop($bits), ' $,');
        $this->index_name = trim($bits[0], ' $,');
      }
      else {
        $this->index_name = 'i';
      }

      $this->in = $matches[self::IN];
    }
  }

  public function getIndex_name() {
    return (isset($this->index_name) ? $this->index_name : 'i');
  }
  public function setIndex_name($value) {
    $this->index_name = $value;
  }

  /**
   * Parse this node.
   * @param SassContext the context in which this node is parsed
   * @return array parsed child nodes
   */
  public function parse($context) {
    $children = array();

    if ($this->variable && $this->in) {
      $context = new SassContext($context);

      try {
        $eval_in = $this->evaluate($this->in, $context->parent)->value;
      } catch (Exception $e) {
        $eval_in = $this->in;
      }
      $eval_in = $this->parse_in($eval_in);

      foreach ($eval_in as $i => $in) {
        $context->setVariable($this->index_name, new SassNumber($i));
        $context->setVariable($this->variable, new SassString(trim($in)));
        $children = array_merge($children, $this->parseChildren($context));
      }
    }
    return $children;
  }

  private function parse_in($string) {
    $current = '';
    $in_brace = FALSE;
    $list = array();

    if (strpos($string, '(') === FALSE) {
      return explode(',', $string);
    }

    for ($i = 0; $i < strlen($string); $i++) {
      $char = $string{$i};

      if ($in_brace) {
        if ($char == ')') {
          $list[] = trim($current);
          if (strlen($string) < $i +1 && $string{$i + 1} == ',') {
            $i++; # skip the comma
          }
          $current = '';
          $in_brace =  FALSE;
        }
        else {
          $current .= $char;
        }
        continue;
      }

      if ($char == '(') {
        $in_brace = TRUE;
        continue;
      }

      if ($char == ',') {
        $list[] = trim($current);
        $current = '';
        continue;
      }

      $current .= $char;
    }
    $list[] = trim($current);
    $real_list = array();
    foreach ($list as $k => $v) {
      if (strlen(trim($v))) {
        $real_list[] = $v;
      }
    }

    return $real_list;
  }
}