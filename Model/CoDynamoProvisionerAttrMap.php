<?php
/**
 * COmanage Registry DynamoDB Provisioner Plugin CoDynamoProvisionerAttrMap
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

class CoDynamoProvisionerAttrMap extends AppModel {
  // Define class name for cake
  public $name = "CoDynamoProvisionerAttrMap";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "DynamoProvisioner.CoDynamoProvisionerTarget" => array(
      'foreignKey' => 'co_dynamo_provisioner_target_id'
    )
  );
    
  // Default display field for cake generated views
  public $displayField = "attribute";
  
  // Validation rules for table elements
  public $validate = array(
    'co_dynamo_provisioner_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO Dynamo Provisioning Target ID must be provided'
    ),
    'attribute' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'attribute_template' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'attribute_constraint' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    )
  );

  /**
   * Marshall DynamoDB item attribute value from template for a CO Person.
   *
   * @since  COmanage Registry v4.3.1
   * @param  String $template template for attribute value
   * @param  String $constraint constraint to be met for value to be returned
   * @param  Array $coPerson array of provisioning data
   * @throws none
   * @return String item attribute value or null if cannot be computed
   */

  public function marshallAttributeValue($template, $constraint, $coPerson) {
    $coPersonId = $coPerson['CoPerson']['id'];
    $logPrefix = "DynamoProvisioner CoDynamoProvisionerAttrMap marshallAttributeValue CO Person ID $coPersonId ";

    // Determine if the CO Person record meets the constraint for the attribute
    // value to be set using the template. The constraint can be that one of the 
    // models listed below is present, or if the constraint string does not match
    // one of the listed models then it is interpreted to be an Identifier extended
    // type that must be present.
    $constraintModels = array(
      'CoTAndCAgreement',
      'CoPersonRole',
      'SshKey',
      'Url',
      'UnixClusterAccount'
    );

    if(!empty($constraint)) {
      if(in_array($constraint, $constraintModels)) {
        if(empty($coPerson[$constraint])) {
          $msg = $logPrefix . "constraint model $constraint not found";
          $this->log($msg);
          return null;
        }
      } else {
        $constraintIsMet = false;
        if(!empty($coPerson['Identifier'])) {
          foreach($coPerson['Identifier'] as $i) {
            if($i['type'] == $constraint) {
              $constraintIsMet = true;
            }
          }
        }

        if(!$constraintIsMet) {
          $msg = $logPrefix . "constraint Identifier type $constraint not found";
          $this->log($msg);
          return null;
        }
      }
    }

    // Find all extended types for Identifier.
    $args = array();
    $args['conditions']['CoExtendedType.attribute'] = 'Identifier.type';
    $args['contain'] = false;
   
    $types = $this->CoDynamoProvisionerTarget->CoProvisioningTarget->Co->CoExtendedType->find('all', $args);

    $value = $template;

    // Loop over the ExtendedType name and use it to populate
    // the attribute template.
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
        return null;
      }

      $value = str_replace($search, $replace, $value);
    }

    return $value;
  }
}
