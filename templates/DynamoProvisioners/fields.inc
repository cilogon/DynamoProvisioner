<?php
/**
 * COmanage Registry Dynamo Provisioners Fields
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
 * @link          https://github.com/cilogon/DynamoProvisioner
 * @package       registry-plugin
 * @since         COmanage Registry v5.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

declare(strict_types=1);

use DynamoProvisioner\Lib\Enum\AwsRegionEnum;

// Use region codes as labels so the select matches other field widths
$regionCodes = AwsRegionEnum::getConstValues();
$awsRegions = array_combine($regionCodes, $regionCodes);

$fields = [
  'aws_region' => [
    'type' => 'select',
    'options' => $awsRegions,
    'empty' => true,
    'required' => true
  ],
  'table_name',
  'aws_access_key',
  'aws_secret_key' => [
    'type' => 'password'
  ],
  'partition_key',
  'partition_key_template',
  'sort_key',
  'sort_key_template',
  'duplicate_item'
];

$subnav = [
  'tabs' => ['ProvisioningTargets', 'DynamoProvisioner.DynamoProvisioners'],
  'action' => [
    'ProvisioningTargets' => ['edit'],
    'DynamoProvisioner.DynamoProvisioners' => ['edit']
  ]
];

// Additional attribute mapping UI — rendered after Form->create() via element
$hideAttrMaps = true;
$existingMappings = [];

if(isset($vv_obj) && is_object($vv_obj) && !empty($vv_obj->dynamo_provisioner_attr_maps)) {
  foreach($vv_obj->dynamo_provisioner_attr_maps as $map) {
    if(is_object($map)
       && (!empty($map->attribute) || !empty($map->attribute_template) || !empty($map->attribute_constraint))) {
      $hideAttrMaps = false;
      $existingMappings[] = $map;
    }
  }
}

if(isset($vv_obj) && is_object($vv_obj) && method_exists($vv_obj, 'getError')
   && !empty($vv_obj->getError('dynamo_provisioner_attr_maps'))) {
  $hideAttrMaps = false;
}

$fields['HTML'] = [
  'html' => [
    'element' => 'DynamoProvisioner.attr_maps',
    'params' => [
      'hideAttrMaps' => $hideAttrMaps,
      'existingMappings' => $existingMappings
    ]
  ]
];
