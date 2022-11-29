<?php
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_dynamo_provisioner_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_dynamo_provisioner_targets.1'  => 'Dynamo Provisioner Target',
  'ct.co_dynamo_provisioner_targets.pl' => 'Dynamo Provisioner Targets',
  
  // Error messages
  'er.dynamoprovisioner.id.none'        => 'No identifier of type %1$s found for CO Person',
  
  // Plugin texts
);
