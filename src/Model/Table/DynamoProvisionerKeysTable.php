<?php
/**
 * COmanage Registry Dynamo Provisioner Keys Table
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

class DynamoProvisionerKeysTable extends Table
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
    
    $this->setTableType(\App\Lib\Enum\TableTypeEnum::Secondary);
    
    // Define associations
    $this->belongsTo('DynamoProvisioner.DynamoProvisioners', [
      'foreignKey' => 'dynamo_provisioner_id'
    ]);
    $this->belongsTo('People', [
      'foreignKey' => 'person_id'
    ]);
    
    $this->setDisplayField('partition_key');
    
    $this->setPrimaryLink(['dynamo_provisioner_id', 'person_id']);
    $this->setRequiresCO(true);
  }
  
  /**
   * Compute item keys for Person record.
   *
   * @since  COmanage Registry v5.0.0
   * @param  object $provisioningTarget DynamoProvisioner configuration
   * @param  object $person             Person entity with provisioning data
   * @param  int    $coId               CO ID
   * @return array                      Array of computed keys
   */
  public function getComputedKeysByPerson(
    object $provisioningTarget,
    object $person,
    int $coId
  ): array
  {
    $logPrefix = "DynamoProvisionerKeys getComputedKeysByPerson Person ID {$person->id} ";
    
    // Get all identifier types for this CO
    $TypesTable = \Cake\ORM\TableRegistry::getTableLocator()->get('Types');
    $types = $TypesTable->find()
      ->where([
        'Types.co_id' => $coId,
        'Types.attribute' => 'Identifiers.type'
      ])
      ->all();
      
    // Holds the current set of partition and sort key templates being processed
    $currentTemplates = [];
    
    // Holds the next set of partition and sort key templates
    $nextTemplates = [];
    
    // Holds the set of keys that have been created from templates
    $keys = [];
    
    // Use the partition and sort key templates to create an initial key template
    $template = [
      'partition_key' => $provisioningTarget->partition_key_template,
      'sort_key' => $provisioningTarget->sort_key_template ?? null
    ];
    
    $nextTemplates = [$template];
    
    // Loop over each Identifier type
    foreach ($types as $t) {
      $typeName = $t->value;
      
      $currentTemplates = $nextTemplates;
      $nextTemplates = [];
      
      foreach ($currentTemplates as $template) {
        // If this template does not have any substitutions for the current Identifier type
        // then go onto the next template
        if (!preg_match("~\(I/$typeName\)~", $template['partition_key']) &&
           !preg_match("~\(I/$typeName\)~", (string)$template['sort_key'])) {
          $nextTemplates[] = $template;
          continue;
        }
        
        $this->llog('info', $logPrefix . "searching for Identifier type $typeName");
        
        $search = '(I/' . $typeName . ')';
        
        // Loop over the Person Identifiers and try to replace the template with
        // an Identifier value
        if (!empty($person->identifiers)) {
          foreach ($person->identifiers as $identifier) {
            // In PE, identifier->type is a Type entity, not a string
            $identifierType = $identifier->type->value ?? null;
            if ($identifierType == $typeName) {
              $replace = $identifier->identifier;
              
              $partitionKey = str_replace($search, $replace, $template['partition_key'], $partitionKeyCount);
              $sortKey = str_replace($search, $replace, (string)$template['sort_key'], $sortKeyCount);
              
              // If either the template partition key or sort key were updated
              if ($partitionKeyCount || $sortKeyCount) {
                if (preg_match("~\(I/.+\)~", $partitionKey) ||
                   preg_match("~\(I/.+\)~", $sortKey)) {
                   $nextTemplates[] = [
                     'partition_key' => $partitionKey,
                     'sort_key' => $sortKey
                   ];
                } else {
                  $key = [
                    'dynamo_provisioner_id' => $provisioningTarget->id,
                    'person_id' => $person->id,
                    'partition_key' => $partitionKey,
                    'sort_key' => $sortKey ?: null
                  ];
                  $keys[] = $key;
                }
              } elseif ($partitionKeyCount == 0 && $sortKeyCount == 0) {
                $msg = $logPrefix . "did not find Identifier of type $typeName";
                $this->llog('info', $msg);
                throw new \RuntimeException($msg);
              }
            }
          }
        }
      } 
    }
    
    return $keys;
  }
  
  /**
   * Get saved item keys using Person ID.
   *
   * @since  COmanage Registry v5.0.0
   * @param  int $personId  Person ID
   * @param  int $targetId  DynamoProvisioner ID
   * @return array          Array of saved keys
   */
  public function getSavedKeysByPersonId(int $personId, int $targetId): array
  {
    $keys = $this->find()
      ->where([
        'DynamoProvisionerKeys.person_id' => $personId,
        'DynamoProvisionerKeys.dynamo_provisioner_id' => $targetId
      ])
      ->all()
      ->toArray();
      
    return $keys;
  }
  
  /**
   * Determine if two item keys are functionally equal.
   *
   * @since  COmanage Registry v5.0.0
   * @param  array $key1 First key (array or Entity with ArrayAccess)
   * @param  array $key2 Second key (array or Entity with ArrayAccess)
   * @return bool        True if keys are functionally equal
   */
  public function keysAreEqual($key1, $key2): bool
  {
    if (empty($key1) || empty($key2)) {
      return false;
    }
    
    $fields = [
      'dynamo_provisioner_id',
      'person_id',
      'partition_key',
      'sort_key'
    ];
    
    foreach ($fields as $f) {
      $v1 = $key1[$f] ?? null;
      $v2 = $key2[$f] ?? null;
      
      if (!empty($v1) && empty($v2)) {
        return false;
      }
      if (empty($v1) && !empty($v2)) {
        return false;
      }
      if (!empty($v1) && !empty($v2) && $v1 != $v2) {
        return false;
      }
    }
    
    return true;
  }
  
  /**
   * Determine if item key is in a set of item keys.
   *
   * @since  COmanage Registry v5.0.0
   * @param  array $key Key to search for (array or Entity with ArrayAccess)
   * @param  array $set Array of keys
   * @return bool       True if key is in the set
   */
  public function keyInSet($key, array $set): bool
  {
    foreach ($set as $s) {
      if ($this->keysAreEqual($key, $s)) {
        return true;
      }
    }
    
    return false;
  }
  
  /**
   * Determine if two sets of item keys are functionally equal.
   *
   * @since  COmanage Registry v5.0.0
   * @param  array $set1 First set of keys
   * @param  array $set2 Second set of keys
   * @return bool        True if key sets are functionally equal
   */
  public function keySetsAreEqual(array $set1, array $set2): bool
  {
    if (count($set1) != count($set2)) {
      return false;
    }
    
    foreach ($set1 as $key1) {
      if (!$this->keyInSet($key1, $set2)) {
        return false;
      }
    }
    
    foreach ($set2 as $key2) {
      if (!$this->keyInSet($key2, $set1)) {
        return false;
      }
    }
    
    return true;
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
    
    $validator->add('dynamo_provisioner_id', [
      'content' => ['rule' => 'isInteger']
    ]);
    $validator->notEmptyString('dynamo_provisioner_id');
    
    $validator->add('person_id', [
      'content' => ['rule' => 'isInteger']
    ]);
    $validator->allowEmptyString('person_id');
    
    $this->registerStringValidation($validator, $schema, 'partition_key', true);
    $this->registerStringValidation($validator, $schema, 'sort_key', false);
    
    return $validator;
  }
}
