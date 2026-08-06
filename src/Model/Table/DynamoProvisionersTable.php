<?php
/**
 * COmanage Registry Dynamo Provisioners Table
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
use App\Lib\Enum\ProvisioningEligibilityEnum;
use App\Lib\Enum\ProvisioningStatusEnum;
use App\Lib\Enum\StatusEnum;
use App\Lib\Enum\SuspendableStatusEnum;
use DynamoProvisioner\Lib\Enum\AwsRegionEnum;

// Load AWS SDK
require_once LOCAL . "plugins" . DS . "DynamoProvisioner" . DS . "vendor" . DS . "autoload.php";

use Aws\Credentials\Credentials;
use Aws\DynamoDb\DynamoDbClient;

class DynamoProvisionersTable extends Table {
  use \App\Lib\Traits\AutoViewVarsTrait;
  use \App\Lib\Traits\ChangelogBehaviorTrait;
  use \App\Lib\Traits\CoLinkTrait;
  use \App\Lib\Traits\HistoryTrait;
  use \App\Lib\Traits\LabeledLogTrait;
  use \App\Lib\Traits\PermissionsTrait;
  use \App\Lib\Traits\PrimaryLinkTrait;
  use \App\Lib\Traits\ProvisionerTrait;
  use \App\Lib\Traits\QueryModificationTrait;
  use \App\Lib\Traits\TableMetaTrait;
  use \App\Lib\Traits\ValidationTrait;
  
  // DynamoDB client
  protected $client = null;
  
  // DynamoDB table name
  protected $tableName = null;
  
  // Cache for enum value to constant name mappings
  protected $enumConstCache = [];
  
  /**
   * Perform Cake Model initialization.
   *
   * @since  COmanage Registry v5.0.0
   * @param  array  $config Configuration options passed to constructor
   */
  public function initialize(array $config): void
  {
    $this->addBehavior('Changelog');
    $this->addBehavior('Log');
    $this->addBehavior('Timestamp');
    
    $this->setTableType(\App\Lib\Enum\TableTypeEnum::Configuration);
    
    // Define associations
    $this->belongsTo('ProvisioningTargets');
    
    $this->hasMany('DynamoProvisioner.DynamoProvisionerKeys', [
      'dependent' => true,
      'foreignKey' => 'dynamo_provisioner_id'
    ]);
    
    $this->hasMany('DynamoProvisioner.DynamoProvisionerAttrMaps', [
      'dependent' => true,
      'foreignKey' => 'dynamo_provisioner_id'
    ]);
    
    $this->setDisplayField('table_name');
    
    $this->setPrimaryLink(['provisioning_target_id']);
    $this->setRequiresCO(true);
    
    // Load associated data when editing
    $this->setEditContains([
      'ProvisioningTargets',
      'DynamoProvisionerAttrMaps'
    ]);
    
    $this->setPermissions([
      'entity' => [
        'delete' => false, // Delete the pluggable object instead
        'edit' =>   ['platformAdmin', 'coAdmin'],
        'view' =>   ['platformAdmin', 'coAdmin']
      ],
      'table' => [
        'add' =>    false,
        'index' =>  ['platformAdmin', 'coAdmin']
      ]
    ]);
    
    $this->setProvisionableModels([
      'People'
    ]);
    
    // Set up auto view vars for the AWS regions enum
    $this->setAutoViewVars([
      'aws_regions' => [
        'type' => 'enum',
        'class' => 'DynamoProvisioner.AwsRegionEnum'
      ]
    ]);
  }
  
  /**
   * Create a DynamoDB API client.
   *
   * @since  COmanage Registry v5.0.0
   * @param  object $provisioningTarget Provisioning target configuration
   * @return void
   */
  protected function createClient(object $provisioningTarget): void
  {
    $credentials = new Credentials(
      $provisioningTarget->aws_access_key,
      $provisioningTarget->aws_secret_key
    );
    
    $args = [
      'credentials' => $credentials,
      'region' => $provisioningTarget->aws_region,
      'version' => 'latest'
    ];
    
    $this->tableName = $provisioningTarget->table_name;
    $this->client = new DynamoDbClient($args);
  }
  
  /**
   * Get constant name for enum value with caching.
   *
   * @since  COmanage Registry v5.0.0
   * @param  string $enumClass Fully qualified enum class name
   * @param  string $value     Enum value (e.g., 'A', 'S', 'PA')
   * @return string            Constant name (e.g., 'Active', 'Suspended', 'PendingApproval')
   */
  protected function getEnumConstName(string $enumClass, string $value): string
  {
    // Check if we've already cached this enum class
    if (!isset($this->enumConstCache[$enumClass])) {
      // Build the reverse lookup map once per enum class
      $reflect = new \ReflectionClass($enumClass);
      $constants = $reflect->getConstants();
      
      // Cache the reverse mapping: value => constant name
      $this->enumConstCache[$enumClass] = array_flip($constants);
    }
    
    // Fast lookup from cache
    return $this->enumConstCache[$enumClass][$value] ?? $value;
  }
  
  /**
   * Delete item from DynamoDB table.
   *
   * @since  COmanage Registry v5.0.0
   * @param  object $provisioningTarget Provisioning target configuration
   * @param  array  $key                Key for item to delete
   * @return bool                       True on success
   */
  protected function deleteUserItem(object $provisioningTarget, array $key): bool
  {
    $args = [];
    
    // Marshall the partition and sort key (DynamoDB strings)
    $partitionKeyName = $provisioningTarget->partition_key;
    $partitionKeyValue = $key['partition_key'];
    
    $args['Key'][$partitionKeyName] = ['S' => $partitionKeyValue];
    
    $sortKeyName = $provisioningTarget->sort_key ?? null;
    $sortKeyValue = $key['sort_key'] ?? null;
    
    if (!empty($sortKeyName) && !empty($sortKeyValue)) {
      $args['Key'][$sortKeyName] = ['S' => $sortKeyValue];
    }
    
    $args['TableName'] = $this->tableName;
    
    try {
      $result = $this->client->deleteItem($args);
    } catch (\Exception $e) {
      $this->llog('error', "DynamoProvisioner deleteUserItem caught exception: " . $e->getMessage());
      return false;
    }
    
    return true;
  }
  
  /**
   * Marshall Person provisioning data to DynamoDB item.
   *
   * @since  COmanage Registry v5.0.0
   * @param  object $provisioningTarget Provisioning target configuration
   * @param  object $person             Person entity with provisioning data
   * @param  array  $key                Key for item
   * @param  int    $coId               CO ID
   * @return array                      DynamoDB item
   */
  protected function marshallUserItem(
    object $provisioningTarget,
    object $person,
    array $key,
    int $coId
  ): array
  {
    $item = [];
    
    // Marshall the partition and sort key (DynamoDB strings)
    $partitionKeyName = $provisioningTarget->partition_key;
    $partitionKeyValue = $key['partition_key'];
    
    $item[$partitionKeyName] = ['S' => $partitionKeyValue];
    
    $sortKeyName = $provisioningTarget->sort_key ?? null;
    $sortKeyValue = $key['sort_key'] ?? null;
    
    if (!empty($sortKeyName) && !empty($sortKeyValue)) {
      $item[$sortKeyName] = ['S' => $sortKeyValue];
    }
    
    // Person status
    if (!empty($person->status)) {
      $item['cm_status'] = ['S' => $this->getEnumConstName(StatusEnum::class, $person->status)];
    }
    
    // Group memberships
    $memberships = [];
    if (!empty($person->group_members)) {
      foreach ($person->group_members as $m) {
        $map = [];
        
        if (!empty($m->group_id)) {
          $map['cm_group_id'] = ['N' => strval($m->group_id)];
        }
        
        if (!empty($m->group->name)) {
          $map['cm_name'] = ['S' => $m->group->name];
        }
        
        $map['cm_owner'] = ['BOOL' => (bool)($m->owner ?? false)];
        
        if (!empty($m->valid_from)) {
          $map['cm_valid_from'] = ['S' => $m->valid_from->toDateTimeString()];
        }
        
        if (!empty($m->valid_through)) {
          $map['cm_valid_through'] = ['S' => $m->valid_through->toDateTimeString()];
        }
        
        $memberships[] = ['M' => $map];
      }
    }
    
    if (!empty($memberships)) {
      $item['cm_memberships'] = ['L' => $memberships];
    }
    
    // Email addresses
    $emails = [];
    if (!empty($person->email_addresses)) {
      foreach ($person->email_addresses as $e) {
        $map = [];
        
        if (!empty($e->mail)) {
          $map['cm_address'] = ['S' => $e->mail];
        }
        if (!empty($e->type->value)) {
          $map['cm_type'] = ['S' => $e->type->value];
        }
        $map['cm_verified'] = ['BOOL' => (bool)($e->verified ?? false)];
        
        $emails[] = ['M' => $map];
      }
    }
    
    if (!empty($emails)) {
      $item['cm_emailAddresses'] = ['L' => $emails];
    }
    
    // Person Roles
    $roles = [];
    if (!empty($person->person_roles)) {
      foreach ($person->person_roles as $r) {
        $map = [];
        
        if (!empty($r->affiliation_type->value)) {
          $map['cm_affiliation'] = ['S' => $r->affiliation_type->value];
        }
        if (!empty($r->status)) {
          $map['cm_status'] = ['S' => $this->getEnumConstName(StatusEnum::class, $r->status)];
        }
        if (!empty($r->title)) {
          $map['cm_title'] = ['S' => $r->title];
        }
        if (!empty($r->o)) {
          $map['cm_o'] = ['S' => $r->o];
        }
        if (!empty($r->ou)) {
          $map['cm_ou'] = ['S' => $r->ou];
        }
        if (!empty($r->valid_from)) {
          $map['cm_valid_from'] = ['S' => $r->valid_from->toDateTimeString()];
        }
        if (!empty($r->valid_through)) {
          $map['cm_valid_through'] = ['S' => $r->valid_through->toDateTimeString()];
        }
        if (!empty($r->cou->name)) {
          $map['cm_cou'] = ['S' => $r->cou->name];
        }
        
        $roles[] = ['M' => $map];
      }
    }
    
    if (!empty($roles)) {
      $item['cm_roles'] = ['L' => $roles];
    }
    
    // Identifiers
    $identifiers = [];
    if (!empty($person->identifiers)) {
      foreach ($person->identifiers as $i) {
        $map = [];
        
        if (!empty($i->identifier)) {
          $map['cm_identifier'] = ['S' => $i->identifier];
        }
        if (!empty($i->type->value)) {
          $map['cm_type'] = ['S' => $i->type->value];
        }
        $map['cm_login'] = ['BOOL' => (bool)($i->login ?? false)];
        if (!empty($i->status)) {
          $map['cm_status'] = ['S' => $this->getEnumConstName(SuspendableStatusEnum::class, $i->status)];
        }
        
        $identifiers[] = ['M' => $map];
      }
    }
    
    if (!empty($identifiers)) {
      $item['cm_identifiers'] = ['L' => $identifiers];
    }
    
    // Names
    $names = [];
    if (!empty($person->names)) {
      foreach ($person->names as $n) {
        $map = [];
        
        if (!empty($n->honorific)) {
          $map['cm_honorific'] = ['S' => $n->honorific];
        }
        if (!empty($n->given)) {
          $map['cm_given'] = ['S' => $n->given];
        }
        if (!empty($n->middle)) {
          $map['cm_middle'] = ['S' => $n->middle];
        }
        if (!empty($n->family)) {
          $map['cm_family'] = ['S' => $n->family];
        }
        if (!empty($n->suffix)) {
          $map['cm_suffix'] = ['S' => $n->suffix];
        }
        if (!empty($n->type->value)) {
          $map['cm_type'] = ['S' => $n->type->value];
        }
        $map['cm_primary_name'] = ['BOOL' => (bool)($n->primary_name ?? false)];
        
        $names[] = ['M' => $map];
      }
    }
    
    if (!empty($names)) {
      $item['cm_names'] = ['L' => $names];
    }
    
    // SSH Keys
    // NOTE: SshKeys functionality (SshKey in v4) does not exist in v5 core yet.
    // This code is commented out until SSH Keys functionality is added to v5 or provided via plugin.
    /*
    $keys = [];
    if (!empty($person->ssh_keys)) {
      foreach ($person->ssh_keys as $k) {
        $keyString = ($k->type ?? '') . ' ' . ($k->skey ?? '') . ' ' . ($k->comment ?? '');
        $keys[] = trim($keyString);
      }
    }
    
    if (!empty($keys)) {
      $item['cm_sshkeys'] = ['SS' => $keys];
    }
    */
    
    // Unix Cluster Accounts
    // NOTE: UnixClusterAccount functionality (UnixClusterAccount in v4) does not exist in v5 core yet.
    // This code is commented out until Unix Cluster functionality is added to v5 or provided via plugin.
    /*
    $accounts = [];
    if (!empty($person->unix_cluster_accounts)) {
      foreach ($person->unix_cluster_accounts as $a) {
        $map = [];
        
        if (!empty($a->unix_cluster->cluster->description)) {
          $map['cm_description'] = ['S' => $a->unix_cluster->cluster->description];
        }
        if (!empty($a->status)) {
          $map['cm_status'] = ['S' => $this->getEnumConstName(StatusEnum::class, $a->status)];
        }
        if (!empty($a->username)) {
          $map['cm_username'] = ['S' => $a->username];
        }
        if (isset($a->uid)) {
          $map['cm_uid'] = ['N' => strval($a->uid)];
        }
        if (!empty($a->gecos)) {
          $map['cm_gecos'] = ['S' => $a->gecos];
        }
        if (!empty($a->login_shell)) {
          $map['cm_login_shell'] = ['S' => $a->login_shell];
        }
        if (!empty($a->home_directory)) {
          $map['cm_home_directory'] = ['S' => $a->home_directory];
        }
        
        $accounts[] = ['M' => $map];
      }
    }
    
    if (!empty($accounts)) {
      $item['cm_unix_cluster_accounts'] = ['L' => $accounts];
    }
    */
    
    // Terms and Conditions
    // NOTE: T&C functionality (CoTAndCAgreement in v4) does not exist in v5 core yet.
    // This code is commented out until T&C is added to v5 or provided via plugin.
    /*
    $tandcs = [];
    if (!empty($person->terms_and_conditions_agreements)) {
      foreach ($person->terms_and_conditions_agreements as $a) {
        $map = [];
        
        if (!empty($a->terms_and_conditions->description)) {
          $map['cm_description'] = ['S' => $a->terms_and_conditions->description];
        }
        if (!empty($a->terms_and_conditions->status)) {
          $map['cm_status'] = ['S' => $this->getEnumConstName(SuspendableStatusEnum::class, $a->terms_and_conditions->status)];
        }
        if (!empty($a->agreement_time)) {
          $map['cm_agreement_time'] = ['N' => strval($a->agreement_time->toUnixString())];
        }
        
        $tandcs[] = ['M' => $map];
      }
    }
    
    if (!empty($tandcs)) {
      $item['cm_tandc_agreements'] = ['L' => $tandcs];
    }
    */
    
    // Marshall additional attributes as configured
    $attrMaps = $this->DynamoProvisionerAttrMaps->find()
      ->where(['DynamoProvisionerAttrMaps.dynamo_provisioner_id' => $provisioningTarget->id])
      ->all();
      
    foreach ($attrMaps as $am) {
      $attributeName = $am->attribute;
      $template = $am->attribute_template;
      $constraint = $am->attribute_constraint;
      
      $value = $this->DynamoProvisionerAttrMaps->marshallAttributeValue(
        $template,
        $constraint,
        $person,
        $coId
      );
      
      // The marshalled attribute value may be empty if the constraint is not met
      if (!empty($value)) {
        $item[$attributeName] = ['S' => $value];
      }
    }
    
    return $item;
  }
  
  /**
   * Provision object data to the provisioning target.
   *
   * @since  COmanage Registry v5.0.0
   * @param  object $provisioningTarget DynamoProvisioner configuration
   * @param  string $entityName         Entity name (e.g., 'People')
   * @param  object $data               Provisioning data in Entity format
   * @param  string $eligibility        Provisioning Eligibility Enum
   * @return array                      Array of status, comment, and optional identifier
   */
  public function provision(
    object $provisioningTarget,
    string $entityName,
    object $data,
    string $eligibility
  ): array
  {
    $personId = $data->id;
    $logPrefix = "DynamoProvisioner provision Person ID $personId ";
    
    // Only provision People
    if ($entityName != 'People') {
      return [
        'status' => ProvisioningStatusEnum::NotProvisioned,
        'comment' => 'Only People are provisioned',
        'identifier' => null
      ];
    }
    
    // Determine if we should delete
    $deletePerson = ($eligibility == ProvisioningEligibilityEnum::Deleted);
    
    try {
      $success = $this->syncPerson($provisioningTarget, $data, $deletePerson);
      
      if ($success) {
        return [
          'status' => ProvisioningStatusEnum::Provisioned,
          'comment' => 'Successfully provisioned to DynamoDB',
          'identifier' => null
        ];
      } else {
        return [
          'status' => ProvisioningStatusEnum::Unknown,
          'comment' => 'Failed to provision to DynamoDB',
          'identifier' => null
        ];
      }
    } catch (\Exception $e) {
      $this->llog('info', $logPrefix . "caught exception: " . $e->getMessage());
      return [
        'status' => ProvisioningStatusEnum::Unknown,
        'comment' => $e->getMessage(),
        'identifier' => null
      ];
    }
  }
  
  /**
   * Put DynamoDB item into table.
   *
   * @since  COmanage Registry v5.0.0
   * @param  object $provisioningTarget Provisioning target configuration
   * @param  object $person             Person entity
   * @param  array  $key                Key for item
   * @param  int    $coId               CO ID
   * @return bool                       True on success
   */
  protected function putUserItem(
    object $provisioningTarget,
    object $person,
    array $key,
    int $coId
  ): bool
  {
    $args = [];
    
    $args['Item'] = $this->marshallUserItem($provisioningTarget, $person, $key, $coId);
    $args['TableName'] = $this->tableName;
    
    try {
      $result = $this->client->putItem($args);
    } catch (\Exception $e) {
      $this->llog('info', "DynamoProvisioner putUserItem caught exception: " . $e->getMessage());
      return false;
    }
    
    return true;
  }
  
  /**
   * Determine the provisioning status of this target.
   *
   * @since  COmanage Registry v5.0.0
   * @param  int  $provisioningTargetId Provisioning Target ID
   * @param  int  $groupId              Group ID (not currently supported)
   * @param  int  $personId             Person ID
   * @return array                      Status information
   */
  public function status(
    int $provisioningTargetId,
    ?int $groupId = null,
    ?int $personId = null
  ): array
  {
    $ret = [
      'timestamp' => null,
      'comment' => '',
      'status' => ProvisioningStatusEnum::NotProvisioned
    ];
    
    // We only provision People, not Groups
    if (empty($personId)) {
      return $ret;
    }
    
    // Pull the configuration
    $provisioningTarget = $this->find()
      ->where(['DynamoProvisioners.provisioning_target_id' => $provisioningTargetId])
      ->first();
      
    if (!$provisioningTarget) {
      return $ret;
    }
    
    // See if we have recorded any item keys for this Person
    $itemKeys = $this->DynamoProvisionerKeys->getSavedKeysByPersonId(
      $personId,
      $provisioningTarget->id
    );
    
    if (empty($itemKeys)) {
      return $ret;
    }
    
    $ret['status'] = ProvisioningStatusEnum::Provisioned;
    
    // Get the last provision time from the ProvisioningHistory
    $ProvisioningHistoryRecordsTable = \Cake\ORM\TableRegistry::getTableLocator()
      ->get('ProvisioningHistoryRecords');
      
    $historyRecord = $ProvisioningHistoryRecordsTable->find()
      ->where([
        'provisioning_target_id' => $provisioningTargetId,
        'person_id' => $personId
      ])
      ->order(['id' => 'DESC'])
      ->first();
    
    if (!empty($historyRecord)) {
      $ret['timestamp'] = $historyRecord->created;
    }
    
    $comment = "";
    foreach ($itemKeys as $itemKey) {
      $partitionKey = $itemKey['partition_key'];
      $comment .= $partitionKey;
      
      $sortKey = $itemKey['sort_key'] ?? null;
      if ($sortKey) {
        $comment .= $sortKey;
      }
      
      $comment .= ",";
    }
    
    $ret['comment'] = rtrim($comment, ",");
    
    return $ret;
  }
  
  /**
   * Synchronize Person record with DynamoDB table item.
   *
   * @since  COmanage Registry v5.0.0
   * @param  object $provisioningTarget Provisioning target configuration
   * @param  object $person             Person entity
   * @param  bool   $delete             True if items should be deleted from table
   * @return bool                       True on success
   */
  protected function syncPerson(
    object $provisioningTarget,
    object $person,
    bool $delete = false
  ): bool
  {
    $personId = $person->id;
    $logPrefix = "DynamoProvisioner syncPerson Person ID $personId ";
    
    // Get CO ID
    $coId = $person->co_id ?? null;
    if (!$coId) {
      $this->llog('info', $logPrefix . "CO ID not found");
      return false;
    }
    
    // Retrieve saved keys by Person ID if we have any
    $savedKeys = $this->DynamoProvisionerKeys->getSavedKeysByPersonId(
      $personId,
      $provisioningTarget->id
    );
    
    $this->llog('info', $logPrefix . "saved keys count: " . count($savedKeys));
    
    // For provisioning actions other than delete, the Person status is factored in
    $status = $person->status ?? null;
    
    // If there are no saved keys then this Person has never been provisioned
    // and if the record has any of these status values then do not provision
    $noInitialProvisionStatus = [
      StatusEnum::Confirmed,
      StatusEnum::Archived,
      StatusEnum::Denied,
      StatusEnum::Duplicate,
      StatusEnum::Invited,
      StatusEnum::Pending,
      StatusEnum::PendingActivation,
      StatusEnum::PendingApproval,
      StatusEnum::PendingConfirmation,
      StatusEnum::Declined
    ];
    
    if (in_array($status, $noInitialProvisionStatus)) {
      if (empty($savedKeys)) {
        $this->llog('info', $logPrefix . "with no saved keys and status $status will not be provisioned");
        return true;
      } else {
        $delete = true;
      }
    }
    
    // Compute the keys based on current Person record
    try {
      $computedKeys = $this->DynamoProvisionerKeys->getComputedKeysByPerson(
        $provisioningTarget,
        $person,
        $coId
      );
      $this->llog('info', $logPrefix . "computed keys count: " . count($computedKeys));
    } catch (\Exception $e) {
      $computedKeys = [];
      $this->llog('info', $logPrefix . "caught exception trying to compute keys: " . $e->getMessage());
      
      if (!empty($savedKeys)) {
        $this->llog('info', $logPrefix . "setting delete true");
        $delete = true;
      }
    }
    
    // Do not allow more than one computed key if not configured for duplicate items
    if (count($computedKeys) > 1 && !$provisioningTarget->duplicate_item) {
      $this->llog('info', $logPrefix . "more than one key computed but duplicate_item is false");
      return false;
    }
    
    // Create the DynamoDB client
    try {
      $this->createClient($provisioningTarget);
    } catch (\Exception $e) {
      $this->llog('info', $logPrefix . "caught exception creating client: " . $e->getMessage());
      return false;
    }
    
    // Delete the items in DynamoDB and the saved keys when signalled
    if ($delete) {
      if (empty($savedKeys)) {
        $this->llog('info', $logPrefix . "has no saved keys so no delete required");
        return true;
      }
      
      $success = true;
      foreach ($savedKeys as $savedKey) {
        $ret = $this->deleteUserItem($provisioningTarget, $savedKey);
        if (!$ret) {
          $this->llog('info', $logPrefix . "failed deleting user item");
          $success = false;
          continue;
        }
        
        $keyId = $savedKey['id'];
        $ret = $this->DynamoProvisionerKeys->delete($this->DynamoProvisionerKeys->get($keyId));
        if (!$ret) {
          $this->llog('info', $logPrefix . "failed deleting saved key");
          $success = false;
          continue;
        }
        
        $this->llog('info', $logPrefix . "deleted item with saved key");
      }
      
      return $success;
    }
    
    // We may not have any computed keys if the Person record does not have
    // the necessary Identifier(s)
    if (empty($computedKeys)) {
      $this->llog('info', $logPrefix . "no computed keys available so will not provision");
      return true;
    }
    
    foreach ($computedKeys as $computedKey) {
      // Always PUT the user item using a computed key
      $success = $this->putUserItem($provisioningTarget, $person, $computedKey, $coId);
      
      if (!$success) {
        $this->llog('info', $logPrefix . "failed to put item using computed key");
        return false;
      }
      
      $this->llog('info', $logPrefix . "put item with computed key");
      
      // If we had no saved keys then save the computed key now
      if (empty($savedKeys)) {
        $newKey = $this->DynamoProvisionerKeys->newEntity($computedKey);
        $this->DynamoProvisionerKeys->save($newKey);
      }
    }
    
    // If we had no saved keys then we are done now
    if (empty($savedKeys)) {
      $this->llog('info', $logPrefix . "no previously saved keys so sync complete");
      return true;
    }
    
    // If the saved keys and computed keys are equal then we are done
    if ($this->DynamoProvisionerKeys->keySetsAreEqual($savedKeys, $computedKeys)) {
      $this->llog('info', $logPrefix . "saved keys and computed keys are equal so sync complete");
      return true;
    }
    
    // Save any computed keys that were not previously saved
    foreach ($computedKeys as $computedKey) {
      if (!$this->DynamoProvisionerKeys->keyInSet($computedKey, $savedKeys)) {
        $newKey = $this->DynamoProvisionerKeys->newEntity($computedKey);
        $this->DynamoProvisionerKeys->save($newKey);
      }
    }
    
    // Delete old items and keys that are no longer in computed set
    foreach ($savedKeys as $savedKey) {
      if (!$this->DynamoProvisionerKeys->keyInSet($savedKey, $computedKeys)) {
        $success = $this->deleteUserItem($provisioningTarget, $savedKey);
        if ($success) {
          $this->llog('info', $logPrefix . "deleted item for previously saved key");
        } else {
          $this->llog('info', $logPrefix . "failed deleting user item with saved key");
          return true; // Still return true since we provisioned the new keys
        }
        
        $keyId = $savedKey['id'];
        $success = $this->DynamoProvisionerKeys->delete($this->DynamoProvisionerKeys->get($keyId));
        if (!$success) {
          $this->llog('info', $logPrefix . "failed deleting saved key");
          return true;
        }
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
    
    $validator->add('provisioning_target_id', [
      'content' => ['rule' => 'isInteger']
    ]);
    $validator->notEmptyString('provisioning_target_id');
    
    $validator->add('aws_region', [
      'content' => ['rule' => ['inList', AwsRegionEnum::getConstValues()]]
    ]);
    $validator->notEmptyString('aws_region');
    
    $this->registerStringValidation($validator, $schema, 'aws_access_key', true);
    $this->registerStringValidation($validator, $schema, 'aws_secret_key', true);
    $this->registerStringValidation($validator, $schema, 'table_name', true);
    $this->registerStringValidation($validator, $schema, 'partition_key', true);
    $this->registerStringValidation($validator, $schema, 'partition_key_template', true);
    $this->registerStringValidation($validator, $schema, 'sort_key', false);
    $this->registerStringValidation($validator, $schema, 'sort_key_template', false);
    
    $validator->add('duplicate_item', [
      'content' => ['rule' => 'boolean']
    ]);
    $validator->allowEmptyString('duplicate_item');
    
    // Allow associated data for attribute mappings
    $validator->allowEmptyArray('dynamo_provisioner_attr_maps');
    
    return $validator;
  }
}
