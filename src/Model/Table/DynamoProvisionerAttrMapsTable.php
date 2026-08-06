<?php
/**
 * COmanage Registry Dynamo Provisioner Attribute Maps Table
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

namespace DynamoProvisioner\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class DynamoProvisionerAttrMapsTable extends Table
{
  use \App\Lib\Traits\ChangelogBehaviorTrait;
  use \App\Lib\Traits\CoLinkTrait;
  use \App\Lib\Traits\LabeledLogTrait;
  use \App\Lib\Traits\PrimaryLinkTrait;
  use \App\Lib\Traits\TableMetaTrait;
  use \App\Lib\Traits\ValidationTrait;
  
  /**
   * Perform Cake Model initialization.
   *
   * @since  COmanage Registry v5.0.0
   * @param  array  $config Configuration options passed to constructor
   */
  public function initialize(array $config): void
  {
    $this->addBehavior('Changelog');
    $this->addBehavior('Timestamp');
    
    $this->setTableType(\App\Lib\Enum\TableTypeEnum::Configuration);
    
    // Define associations
    $this->belongsTo('DynamoProvisioner.DynamoProvisioners', [
      'foreignKey' => 'dynamo_provisioner_id'
    ]);
    
    $this->setDisplayField('attribute');
    
    $this->setPrimaryLink(['dynamo_provisioner_id']);
    $this->setRequiresCO(true);
  }
  
  /**
   * Marshall DynamoDB item attribute value from template for a Person.
   *
   * @since  COmanage Registry v5.0.0
   * @param  string $template   Template for attribute value
   * @param  string $constraint Constraint to be met for value to be returned
   * @param  object $person     Person entity with provisioning data
   * @param  int    $coId       CO ID
   * @return string|null        Item attribute value or null if cannot be computed
   */
  public function marshallAttributeValue(
    string $template,
    ?string $constraint,
    object $person,
    int $coId
  ): ?string
  {
    $logPrefix = "DynamoProvisioner marshallAttributeValue Person ID {$person->id} ";
    
    // Determine if the Person record meets the constraint for the attribute
    // value to be set using the template. The constraint can be that one of the 
    // models listed below is present, or if the constraint string does not match
    // one of the listed models then it is interpreted to be an Identifier type
    // that must be present.
    // NOTE: SshKeys and UnixClusterAccounts removed - not available in v5 core yet
    $constraintModels = [
      'PersonRoles',
      'Urls'
    ];
    
    if (!empty($constraint)) {
      if (in_array($constraint, $constraintModels)) {
        if (empty($person->$constraint)) {
          $this->llog('info', $logPrefix . "constraint model $constraint not found");
          return null;
        }
      } else {
        $constraintIsMet = false;
        if (!empty($person->identifiers)) {
          foreach ($person->identifiers as $i) {
            if ($i->type->value == $constraint) {
              $constraintIsMet = true;
              break;
            }
          }
        }
        
        if (!$constraintIsMet) {
          $this->llog('info', $logPrefix . "constraint Identifier type $constraint not found");
          return null;
        }
      }
    }
    
    // Get all identifier types for this CO
    $TypesTable = \Cake\ORM\TableRegistry::getTableLocator()->get('Types');
    $types = $TypesTable->find()
      ->where([
        'Types.co_id' => $coId,
        'Types.attribute' => 'Identifiers.type'
      ])
      ->all();
      
    $value = $template;
    
    // Loop over the Type values and use them to populate the attribute template
    foreach ($types as $t) {
      $typeName = $t->value;
      
      if (!preg_match("~\(I/$typeName\)~", $template)) {
        continue;
      }
      
      $this->llog('info', $logPrefix . "searching for Identifier type $typeName");
      
      $search = '(I/' . $typeName . ')';
      $replace = "";
      
      if (!empty($person->identifiers)) {
        foreach ($person->identifiers as $identifier) {
          if ($identifier->type->value == $typeName) {
            $replace = $identifier->identifier;
            break;
          }
        }
      }
      
      if (empty($replace)) {
        $this->llog('info', $logPrefix . "did not find Identifier of type $typeName");
        return null;
      }
      
      $value = str_replace($search, $replace, $value);
    }
    
    return $value;
  }
  
  /**
   * Set validation rules.
   * 
   * @since  COmanage Registry v5.0.0
   * @param  Validator $validator Validator
   * @return Validator            Validator
   */
  public function validationDefault(Validator $validator): Validator
  {
    $schema = $this->getSchema();
    
    // Foreign key - CakePHP auto-populates when saving associated records
    // We don't validate it since it's set by the ORM, not by user input
    $validator->add('dynamo_provisioner_id', [
      'content' => ['rule' => 'isInteger']
    ]);
    $validator->allowEmptyString('dynamo_provisioner_id');
    
    // Both attribute and attribute_template are required for a valid mapping
    // Empty rows are filtered out by beforeMarshal in the parent table
    $this->registerStringValidation($validator, $schema, 'attribute', true);
    $this->registerStringValidation($validator, $schema, 'attribute_template', true);
    // attribute_constraint is optional
    $this->registerStringValidation($validator, $schema, 'attribute_constraint', false);
    
    return $validator;
  }
  
}
