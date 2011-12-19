<?php

/**
 * @file
 * Hooks provided by the Sassy module.
 */

/**
 * Allows you to alter a string of SASS or SCSS before or after it is processed
 * by the hamlP parser.
 *
 * @param &$data
 *   The SASS or SCSS string (file content of $file) that is going to be processed
 *   by the PhamlP parser.
 * @param $info
 *   An array of information for the file that is currently being processed
 *   containing the following properties:
 *   - file: The SASS or SCSS file that $data belongs to described by an array.
 *   - syntax: The syntax (SASS or SCSS) of the file contents.
 *   - iteration: The current iteration, can be SASS_PRECOMPILE or
 *   SASS_POSTCOMPILE.
 */
function hook_sassy_pre_alter(&$data, $info) {
  // Replaces all black colors defined by '#000' in the output CSS file with '#FFF'.
  $data = str_replace('#000', '#FFF', $data);
}

/**
 * @todo
 */
function hook_sassy_libraries_scss_alter(&$libraries) {
  
}

/**
 * @todo
 */
function hook_sassy_libraries_sass_alter(&$libraries) {

}