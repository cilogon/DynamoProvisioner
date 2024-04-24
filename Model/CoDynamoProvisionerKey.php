<?php
/**
 * COmanage Registry DynamoDB Provisioner Plugin CoDynamoProvisionerKey
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
 * @since         COmanage Registry v4.3.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoDynamoProvisionerKey extends AppModel {
  // Define class name for cake
  public $name = "CoDynamoProvisionerKey";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "DynamoProvisioner.CoDynamoProvisionerTarget",
    "CoPerson"
  );
    
  // Default display field for cake generated views
  public $displayField = "partition_key";
  
  // Validation rules for table elements
  public $validate = array(
    'co_dynamo_provisioner_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO Dynamo Provisioning Target ID must be provided'
    ),
    'co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'partition_key' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'sort_key' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
  );

  /**
   * Compute item key for CO Person record.
   *
   * @since COmanage Registry v4.3.1
   * @param  Array CO Provisioning Target data
   * @param  Array Provisioning data for CoPerson
   * @throws none
   * @return Array CoDynamoProvisionerKey item key
   */

  public function getComputedKeyByCoPerson($coProvisioningTargetData, $provisioningData) {
    // Pull all the CO ExtendedType where the attribute is Identifier.type,
    // i.e. extended types for a CO Person (or CO Group) Identifier.
    $args = array();
    $args['conditions']['CoExtendedType.attribute'] = 'Identifier.type';
    $args['contain'] = false;

    $types = $this->CoDynamoProvisionerTarget->CoProvisioningTarget->Co->CoExtendedType->find('all', $args);
    
    $partitionKeyTemplate = $coProvisioningTargetData['CoDynamoProvisionerTarget']['partition_key_template'];
    $partitionKeyValue = $this->marshallKeyValue($partitionKeyTemplate, $types, $provisioningData);

    $sortKeyTemplate = $coProvisioningTargetData['CoDynamoProvisionerTarget']['sort_key_template'];
    $sortKeyValue = $this->marshallKeyValue($sortKeyTemplate, $types, $provisioningData);

    $key = array();
    $key['CoDynamoProvisionerKey']['co_dynamo_provisioner_target_id'] = $coProvisioningTargetData['CoDynamoProvisionerTarget']['id'];
    $key['CoDynamoProvisionerKey']['co_person_id'] = $provisioningData['CoPerson']['id'];
    $key['CoDynamoProvisionerKey']['partition_key'] = $partitionKeyValue;

    if(!empty($sortKeyValue)) {
      $key['CoDynamoProvisionerKey']['sort_key'] = $sortKeyValue;
    }

    return $key;
  }


  /**
   * Get saved item key using CO Person ID.
   *
   * @since COmanage Registry v4.3.1
   * @param  Integer $id CO Person ID
   * @throws none
   * @return Array CoDynamoProvisionerKey item key
   */

  public function getSavedKeyByCoPersonId($id) {
    $args = array();
    $args['conditions']['CoDynamoProvisionerKey.co_person_id'] = $id;
    $args['contain'] = false;

    $key = $this->find('first', $args);

    return $key;
  }

  /**
   * Determine if two item keys are functionally equal.
   *
   * @since COmanage Registry v4.3.1
   * @param  Array $key1 CoDynamoProvisionerKey
   * @param  Array $key2 CoDynamoProvisionerKey
   * @throws none
   * @return Boolean True if keys are functionally equal
   */

  public function keysAreEqual($key1, $key2) {
    if(empty($key1) || empty($key2)) {
      return false;
    }

    $fields = array();
    $fields[] = 'co_dynamo_provisioner_target_id';
    $fields[] = 'co_person_id';
    $fields[] = 'partition_key';
    $fields[] = 'sort_key';

    foreach($fields as $f) {
      if(!empty($key1['CoDynamoProvisionerKey'][$f]) && empty($key2['CoDynamoProvisionerKey'][$f])) {
        return false;
      }
      if(!empty($key1['CoDynamoProvisionerKey'][$f]) &&
         !empty($key2['CoDynamoProvisionerKey'][$f]) &&
         $key1['CoDynamoProvisionerKey'][$f] != $key2['CoDynamoProvisionerKey'][$f]) {
        return false;
      }
    }

    return true;
  }

  /**
   * Marshall partition or sort key value from template for a CO Person.
   *
   * @since COmanage Registry v4.3.1
   * @param  String $template template for key value
   * @param  Array $types array of CoExtentedType for Identifier
   * @param  Array $coPerson array of provisioning data
   * @throws RuntimeException
   * @return String key value
   */

  public function marshallKeyValue($template, $types, $coPerson) {
    $coPersonId = $coPerson['CoPerson']['id'];
    $logPrefix = "DynamoProvisioner CoDynamoProvisionerKey marshallKeyValue CO Person ID $coPersonId ";

    // A table may not use a sort key and so the passed sort key
    // template may be empty.
    if(empty($template)) {
      return null;
    }

    $value = $template;

    // Loop over the ExtendedType name and use it to populate
    // the key template.
    foreach ($types as $t) {
      $typeName = $t['CoExtendedType']['name'];

      if(!preg_match("~\(I/$typeName\)~", $template)) {
        continue;
      }

      $msg = $logPrefix . "searching for Identifier type $typeName";
      $this->log($msg);

      $search = '(I/' . $typeName . ')';
      $replace = "";

      foreach($coPerson['Identifier'] as $identifier) {
        if($identifier['type'] == $typeName) {
          $replace = $identifier['identifier'];
          break;
        }
      }

      if(empty($replace)) {
        $msg = $logPrefix . "did not find Identifier of type $typeName";
        $this->log($msg);
        throw new RuntimeException($msg);
      }

      $value = str_replace($search, $replace, $value);
    }

    return $value;
  }
}
