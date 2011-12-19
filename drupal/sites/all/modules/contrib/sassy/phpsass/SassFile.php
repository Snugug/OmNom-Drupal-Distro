<?php
/* SVN FILE: $Id$ */
/**
 * SassFile class file.
 * File handling utilites.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 * @package      PHamlP
 * @subpackage  Sass
 */

/**
 * SassFile class.
 * @package      PHamlP
 * @subpackage  Sass
 */
class SassFile {
  const CSS  = 'css';
  const SASS = 'sass';
  const SCSS = 'scss';
  const SASSC = 'sassc';

  private static $extensions = array(self::SASS, self::SCSS);

  public static $path = FALSE;
  public static $parser = FALSE;

  /**
   * Returns the parse tree for a file.
   * If caching is enabled a cached version will be used if possible; if not the
   * parsed file will be cached.
   * @param string filename to parse
   * @param SassParser Sass parser
   * @return SassRootNode
   */
  public static function get_tree($filename, $parser) {
    if ($parser->cache) {
      $cached = self::get_cached_file($filename, $parser->cache_location);
      if ($cached !== false) {
        return $cached;
      }
    }

    $contents = file_get_contents($filename) . "\n\n "; #add some whitespace to fix bug
    SassFile::$parser = $parser;
    SassFile::$path = $filename;
    $contents = preg_replace_callback('/url\(\s*[\'"]?(?![a-z]+:|\/+)([^\'")]+)[\'"]?\s*\)/i', 'SassFile::resolve_paths', $contents);

    $options = array_merge($parser->options, array('line'=>1));

    # attempt at cross-syntax imports.
    $ext = substr($filename, strrpos($filename, '.') + 1);
    if ($ext == self::SASS || $ext == self::SCSS) {
      $options['syntax'] = $ext;
    }

    $sassParser = new SassParser($options);
    $tree = $sassParser->parse($contents, FALSE);
    if ($parser->cache) {
      self::set_cached_file($tree, $filename, $parser->cache_location);
    }
    return $tree;
  }

  public static function resolve_paths($matches) {
    // Resolve the path into something nicer...
    $path = self::$parser->basepath . self::$path;
    $path = substr($path, 0, strrpos($path, '/')) . '/';
    $path = $path . $matches[1];
    $last = '';
    while ($path != $last) {
      $last = $path;
      $path = preg_replace('`(^|/)(?!\.\./)([^/]+)/\.\./`', '$1', $path);
    }
    return 'url("' . $path . '")';
  }

  /**
   * Returns the full path to a file to parse.
   * The file is looked for recursively under the load_paths directories and
   * the template_location directory.
   * If the filename does not end in .sass or .scss try the current syntax first
   * then, if a file is not found, try the other syntax.
   * @param string filename to find
   * @param SassParser Sass parser
   * @return array of string path(s) to file(s) or FALSE if no such file
   */
  public static function get_file($filename, $parser) {
    $ext = substr($filename, strrpos($filename, '.') + 1);
    if (substr($filename, -1) != '*' && $ext !== self::SASS && $ext !== self::SCSS && $ext !== self::CSS) {
      $sass = self::get_file($filename . '.' . self::SASS, $parser);
      $scss = self::get_file($filename . '.' . self::SCSS, $parser);
      return $sass ? $sass : $scss;
    }
    if (file_exists($filename)) {
      return array($filename);
    }
    $paths = $parser->load_paths;
    if($path = dirname($parser->filename)) {
      $paths[] = $path;
    }
    foreach ($paths as $path) {
      $filepath = self::find_file($filename, realpath($path));
      if ($filepath !== false) {
        return array($filepath);
      }
    }
    foreach ($parser->load_path_functions as $function) {
      if (function_exists($function) && $paths = call_user_func($function, $filename, $parser)) {
        return $paths;
      }
    }
    if (!empty($parser->template_location)) {
      $path = self::find_file($_filename, realpath($parser->template_location));
      if ($path !== false) {
        return array($path);
      }
    }
    return FALSE;
  }

  /**
   * Looks for the file recursively in the specified directory.
   * This will also look for _filename to handle Sass partials.
   * @param string filename to look for
   * @param string path to directory to look in and under
   * @return mixed string: full path to file if found, false if not
   */
  public static function find_file($filename, $dir) {
    $partialname = dirname($filename).DIRECTORY_SEPARATOR.'_'.basename($filename);

    foreach (array($filename, $partialname) as $file) {
      if (file_exists($dir . DIRECTORY_SEPARATOR . $file)) {
        return realpath($dir . DIRECTORY_SEPARATOR . $file);
      }
    }

    if (is_dir($dir)) {
      $files = array_slice(scandir($dir), 2);

      foreach ($files as $file) {
        if (is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
          $path = self::find_file($filename, $dir . DIRECTORY_SEPARATOR . $file);
          if ($path !== false) {
            return $path;
          }
        }
      } // foreach
    }
      return false;
  }

  /**
   * Returns a cached version of the file if available.
   * @param string filename to fetch
   * @param string path to cache location
   * @return mixed the cached file if available or false if it is not
   */
  public static function get_cached_file($filename, $cacheLocation) {
    $cached = realpath($cacheLocation) . DIRECTORY_SEPARATOR .
      md5($filename) . '.'.self::SASSC;

    if ($cached && file_exists($cached) &&
        filemtime($cached) >= filemtime($filename)) {
      return unserialize(file_get_contents($cached));
    }
    return false;
  }

  /**
   * Saves a cached version of the file.
   * @param SassRootNode Sass tree to save
   * @param string filename to save
   * @param string path to cache location
   * @return mixed the cached file if available or false if it is not
   */
  public static function set_cached_file($sassc, $filename, $cacheLocation) {
    $cacheDir = realpath($cacheLocation);

    if (!$cacheDir) {
      mkdir($cacheLocation);
      @chmod($cacheLocation, 0777);
      $cacheDir = realpath($cacheLocation);
    }

    $cached = $cacheDir . DIRECTORY_SEPARATOR . md5($filename) . '.'.self::SASSC;

    return file_put_contents($cached, serialize($sassc));
  }

}
