<?php
/**
 * COmanage Registry Language File for DynamoProvisioner
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.3.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
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
  'pl.dynamoprovisioner.region'                 => 'AWS Region',
  'pl.dynamoprovisioner.table_name'             => 'DynamoDB Table Name',
  'pl.dynamoprovisioner.aws_access_key_id'      => 'AWS Access Key ID',
  'pl.dynamoprovisioner.aws_secret_access_key'  => 'AWS Secret Access Key',
  'pl.dynamoprovisioner.partition_key'          => 'Partition Key Attribute Name',
  'pl.dynamoprovisioner.partition_key_template' => 'Partition Key Value Template',
  'pl.dynamoprovisioner.sort_key'               => 'Sort Key Attribute Name',
  'pl.dynamoprovisioner.sort_key_template'      => 'Sort Key Value Template',
  'pl.dynamoprovisioner.duplicate_item'         => 'Provision Duplicate Items',
  'pl.dynamoprovisioner.attribute'              => 'Additional Attribute Name',
  'pl.dynamoprovisioner.attribute_template'     => 'Additional Attribute Value Template',
  'pl.dynamoprovisioner.attribute_constraint'   => 'Additional Attribute Constraint',
);
