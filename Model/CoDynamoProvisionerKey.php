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
   * Compute item keys for CO Person record.
   *
   * @since COmanage Registry v4.3.1
   * @param  Array CO Provisioning Target data
   * @param  Array Provisioning data for CoPerson
   * @throws none
   * @return Array array of CoDynamoProvisionerKey item key
   */

  public function getComputedKeysByCoPerson($coProvisioningTargetData, $provisioningData) {
    $coPersonId = $provisioningData['CoPerson']['id'];
    $logPrefix = "CoDynamoProvisionerKey getComputedKeysByCoPerson CO Person ID $coPersonId ";

    // Pull all the CO ExtendedType where the attribute is Identifier.type,
    // i.e. extended types for a CO Person Identifier.
    $args = array();
    $args['conditions']['CoExtendedType.attribute'] = 'Identifier.type';
    $args['contain'] = false;

    $types = $this->CoDynamoProvisionerTarget->CoProvisioningTarget->Co->CoExtendedType->find('all', $args);

    // Holds the current set of partition and sort key templates being processed
    // for the current Identifier type.
    $currentTemplates = array();

    // Holds the next set of partition and sort key templates that will be processed
    // for the next Identifier type after the current Identifier type processing
    // is completed.
    $nextTemplates = array();

    // Holds the set of keys that have been created from templates while
    // looping over the Identifier types.
    $keys = array();

    // Use the partition and sort key templates to create an initial key template.
    $template['partition_key'] = $coProvisioningTargetData['CoDynamoProvisionerTarget']['partition_key_template'];
    $template['sort_key'] = $coProvisioningTargetData['CoDynamoProvisionerTarget']['sort_key_template'] ?? null;

    $nextTemplates = array($template);
    
    // Loop over each Identifier type. For each type loop through the set of current templates
    // and replace the template with an Identifier value.
    foreach ($types as $t) {
      $typeName = $t['CoExtendedType']['name'];

      $currentTemplates = $nextTemplates;
      $nextTemplates = array();

      foreach ($currentTemplates as $template) {
        // If this template does not have any substitutions for the current Identifier type
        // then go onto the next template.
        if(!preg_match("~\(I/$typeName\)~", $template['partition_key']) &&
           !preg_match("~\(I/$typeName\)~", $template['sort_key'])) {
          $nextTemplates[] = $template;
          continue;
        }

        $msg = $logPrefix . "searching for Identifier type $typeName";
        $this->log($msg);

        $search = '(I/' . $typeName . ')';
        $replace = "";

        // Loop over the CO Person Identifiers and try to replace the template with
        // an Identifier value.
        foreach($provisioningData['Identifier'] as $identifier) {
          if($identifier['type'] == $typeName) {
            $replace = $identifier['identifier'];

            $partitionKey = str_replace($search, $replace, $template['partition_key'], $partitionKeyCount);
            $sortKey = str_replace($search, $replace, $template['sort_key'], $sortKeyCount);

            // If either the template partition key or sort key were updated then
            // determine if the resulting string is still a template or is now
            // a computed key. If neither the partition key or sort key were updated
            // then throw an exception because a substitution should have happened.
            if($partitionKeyCount or $sortKeyCount) {
              if(preg_match("~\(I/.+\)~", $partitionKey) ||
                 preg_match("~\(I/.+\)~", $sortKey)) {
                 $nextTemplates[] = array('partition_key' => $partitionKey, 'sort_key'  => $sortKey);
              } else {
                $key['CoDynamoProvisionerKey']['co_dynamo_provisioner_target_id'] = $coProvisioningTargetData['CoDynamoProvisionerTarget']['id'];
                $key['CoDynamoProvisionerKey']['co_person_id'] = $coPersonId;
                $key['CoDynamoProvisionerKey']['partition_key'] = $partitionKey;
                $key['CoDynamoProvisionerKey']['sort_key'] = $sortKey ?? null;
                $keys[] = $key;
              }
            } elseif ($partitionKeyCount == 0 && $sortKeyCount == 0) {
              $msg = $logPrefix . "did not find Identifier of type $typeName";
              $this->log($msg);
              throw new RuntimeException($msg);
            }
          }
        }
      } 
    }

    return $keys;
  }


  /**
   * Get saved item keys using CO Person ID.
   *
   * @since COmanage Registry v4.3.1
   * @param  Integer $id CO Person ID
   * @param  Integer $targetId CoDynamoProviserTarget ID
   * @throws none
   * @return Array array of CoDynamoProvisionerKey item keys
   */

  public function getSavedKeysByCoPersonId($id, $targetId) {
    $args = array();
    $args['conditions']['CoDynamoProvisionerKey.co_person_id'] = $id;
    $args['conditions']['CoDynamoProvisionerKey.co_dynamo_provisioner_target_id'] = $targetId;
    $args['contain'] = false;

    $keys = $this->find('all', $args);

    return $keys;
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
      if(empty($key1['CoDynamoProvisionerKey'][$f]) && !empty($key2['CoDynamoProvisionerKey'][$f])) {
        return false;

      if(!empty($key1['CoDynamoProvisionerKey'][$f]) &&
         !empty($key2['CoDynamoProvisionerKey'][$f]) &&
         $key1['CoDynamoProvisionerKey'][$f] != $key2['CoDynamoProvisionerKey'][$f]) {
        return false;
      }
    }

    return true;
  }

  /**
   * Determine if item key is in a set of item keys.
   *
   * @since COmanage Registry v4.3.1
   * @param  Array $key CoDynamoProvisionerKey
   * @param  Array $set array of CoDynamoProvisionerKey
   * @throws none
   * @return Boolean True if key is in the set
   */

  public function keyInSet($key, $set) {
    foreach($set as $s) {
      if($this->keysAreEqual($key, $s)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Determine if two sets of item keys are functionally equal.
   *
   * @since COmanage Registry v4.3.1
   * @param  Array $set1 array of CoDynamoProvisionerKey
   * @param  Array $set2 array of CoDynamoProvisionerKey
   * @throws none
   * @return Boolean True if key sets are functionally equal
   */

  public function keySetsAreEqual($set1, $set2) {
    if(count($set1) != count($set2)) {
      return false;
    }

    foreach($set1 as $key1) {
      $found = false;
      foreach($set2 as $key2) {
        if($this->keysAreEqual($key1, $key2)) {
          $found = true;
          break;
        }
      }
      if(!$found) {
        return false;
      }
    }

    foreach($set2 as $key2) {
      $found = false;
      foreach($set1 as $key1) {
        if($this->keysAreEqual($key1, $key2)) {
          $found = true;
          break;
        }
      }
      if(!$found) {
        return false;
      }
    }

    return true;
  }
}
