<?php

function sassy_admin_form($form_id, &$form_state) {
  $form = array();
  $form['sassy'] = array(
    '#type' => 'fieldset',
    '#title' => t('Development settings for the SASSY module'),
  );
  $form['sassy']['sassy_devel'] = array(
    '#type' => 'checkbox',
    '#title' => t('Recompile all SASS / SCSS files on every page request.'),
    '#description' => t('Disables the caching of SASS / SCSS files. Useful for when regularly changing the stylesheets (during theme development).'),
    '#default_value' => variable_get('sassy_devel', FALSE),
  );
  $form['sassy']['sassy_debug'] = array(
    '#type' => 'checkbox',
    '#title' => t('Include debug information in output'),
    '#description' => t('Should the compiler include debugging text, ' . l('usable by Firebug', 'https://github.com/nex3/firesass') . ' for enhanced debugging.'),
    '#default_value' => variable_get('sassy_debug', FALSE),
  );


  $form['sassy']['sassy_style'] = array(
    '#type' => 'select',
    '#title' => 'Output style',
    '#description' => t('What style of output should Sassy use.'),
    '#options' => array(
      'nested' => 'Nested: Each property+selector takes up 1 line, selector indentation reflects nesting depth.',
      'expanded' => 'Expanded: Each property+selector takes up 1 line, no selector indentation.',
      'compact' => 'Compact: Each selector takes up 1 line with properties on the same line. No indentation.',
      'compressed' => 'Compressed: Almost no whitespace, designed to be as space-efficient as possible.',
    ),
    '#default_value' => variable_get('sassy_style', 'nested'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Save Settings',
  );

  return $form;
}

function sassy_admin_form_submit($form, $form_state) {
  extract($form_state['values']);
  foreach (array('sassy_devel', 'sassy_debug', 'sassy_style') as $var) {
    variable_set($var, $$var);
  }

  drupal_set_message('Sassy settings have been saved');
}
