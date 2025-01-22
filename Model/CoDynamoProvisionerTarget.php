<?php
/**
 * COmanage Registry DynamoDB Provisioner Plugin Target
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

App::uses("CoProvisionerPluginTarget", "Model");

require_once LOCAL . "Plugin" . DS . "DynamoProvisioner" . DS . "Vendor" . DS . "autoload.php";

use Aws\Credentials\Credentials;
use Aws\DynamoDb\DynamoDbClient;

class CoDynamoProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoDynamoProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoProvisioningTarget"
  );

  public $hasMany = array(
      "DynamoProvisioner.CoDynamoProvisionerAttrMap" => array(
      'dependent' => true,
      'foreignKey' => 'co_dynamo_provisioner_target_id'
    ),
    "DynamoProvisioner.CoDynamoProvisionerKey" => array(
      'dependent' => true,
      'foreignKey' => 'co_dynamo_provisioner_target_id'
    )
  );

  // Default display field for cake generated views
  public $displayField = "table_name";

  // DynamoDB client
  protected $client = null;

  // DynamoDB table name
  protected $tableName = null;

  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'aws_region' => array(
        'content' => array(
          'rule' => array('validateAwsRegion'),
          'required' => true,
          'allowEmpty' => false
        )
    ),
    'aws_access_key_id' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'aws_secret_access_key' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'table_name' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'partition_key' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'partition_key_template' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'sort_key' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'sort_key_template' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'duplicate_item' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    )
  );

  /**
   * Create a DynamoDB API client.
   *
   * @since COmanage Registry v4.3.1
   * @param Array $coProvisioningTargetData provisioning target data
   * @throws none
   * @return null
   */

  protected function createClient($coProvisioningTargetData) {
    $awsAccessKeyId = $coProvisioningTargetData['CoDynamoProvisionerTarget']['aws_access_key_id'];
    $awsSecretAccessKey = $coProvisioningTargetData['CoDynamoProvisionerTarget']['aws_secret_access_key'];

    $credentials = new Credentials($awsAccessKeyId, $awsSecretAccessKey);

    $args = array();
    $args['credentials'] = $credentials;
    $args['region'] = $coProvisioningTargetData['CoDynamoProvisionerTarget']['aws_region'];

    $this->tableName = $coProvisioningTargetData['CoDynamoProvisionerTarget']['table_name'];

    $this->client = new DynamoDbClient($args);
  }

  /**
   * Delete item from DynamoDB table.
   *
   * @since COmanage Registry v4.3.1
   * @param Array $coProvisioningTargetData provisioning target data
   * @param Array $key CoDynamoProvisionerKey key for item to delete
   * @throws none
   * @return Boolean True on success
   */

  protected function deleteUserItem($coProvisioningTargetData, $key) {
    $args = array();

    // Marshall the partition and sort key. For now we assume they are 
    // DynamoDB strings ('S').
    $partitionKeyName = $coProvisioningTargetData['CoDynamoProvisionerTarget']['partition_key'];
    $partitionKeyValue = $key['CoDynamoProvisionerKey']['partition_key'];

    $args['Key'][$partitionKeyName]['S'] = $partitionKeyValue;

    $sortKeyName = $coProvisioningTargetData['CoDynamoProvisionerTarget']['partition_key'] ?? null;
    $sortKeyValue = $key['CoDynamoProvisionerKey']['partition_key'] ?? null;

    if(!empty($sortKeyName)) {
      $args['Key'][$sortKeyName]['S'] = $sortKeyValue;
    }

    $args['TableName'] = $this->tableName;

    try {
      $result = $this->client->deleteItem($args);
    } catch (Exception $e) {
      $this->log("DynamoProvisioner CoDynamoProvisionerTarget deleteUserItem deleteItem caught exception " . $e->getMessage());
      return false;
    }

    return true;
  }


  /**
   * Marshall CO Person provisioning data to DynamoDB item.
   *
   * @since COmanage Registry v4.3.1
   * @param Array $coProvisioningTargetData provisioning target data
   * @param Array $provisioningData CP Person provisioning data
   * @param Array $key CoDynamoProvisionerKey key for item
   * @throws none
   * @return Array DynamoDB item
   */

  protected function marshallUserItem($coProvisioningTargetData, $provisioningData, $key) {
    $item = array();

    // Marshall the partition and sort key. For now we assume they are 
    // DynamoDB strings ('S').
    $partitionKeyName = $coProvisioningTargetData['CoDynamoProvisionerTarget']['partition_key'];
    $partitionKeyValue = $key['CoDynamoProvisionerKey']['partition_key'];

    $item[$partitionKeyName]['S'] = $partitionKeyValue;

    $sortKeyName = $coProvisioningTargetData['CoDynamoProvisionerTarget']['partition_key'] ?? null;
    $sortKeyValue = $key['CoDynamoProvisionerKey']['partition_key'] ?? null;

    if(!empty($sortKeyName)) {
      $item[$sortKeyName]['S'] = $sortKeyValue;
    }

    // Marshall the CO Person record into attributes.
    
    // CO Person status
    $status = $provisioningData['CoPerson']['status'];
    $item['cm_status']['S'] = StatusEnum::$to_api[$status];

    // CO Group memberships
    $memberships = array();
    foreach($provisioningData['CoGroupMember'] as $m) {
      $map = array();

      $map['cm_name']['S'] = $m['CoGroup']['name'];

      $map['cm_owner']['BOOL'] = (bool) $m['owner'];

      if(!empty($m['valid_from'])) {
        $map['cm_valid_from']['S'] = $m['valid_from'];
      }

      if(!empty($m['valid_through'])) {
        $map['cm_valid_through']['S'] = $m['valid_through'];
      }

      $memberships[]['M'] = $map;
    }

    $item['cm_memberships']['L'] = $memberships;

    // EmailAddress
    $emails = array();
    foreach($provisioningData['EmailAddress'] as $e) {
      $map = array();

      $map['cm_address']['S'] = $e['mail'];
      $map['cm_type']['S'] = $e['type'];
      $map['cm_verified']['BOOL'] = (bool) $e['verified'];

      $emails[]['M'] = $map;
    }

    if(!empty($emails)) {
      $item['cm_emailAddresses']['L'] = $emails;
    }

    // CO Person Role
    $roles = array();
    foreach($provisioningData['CoPersonRole'] as $r) {
      $map = array();

      $map['cm_affiliation']['S'] = $r['affiliation'];
      $map['cm_status']['S'] = StatusEnum::$to_api[$r['status']];

      if(!empty($r['title'])) {
        $map['cm_title']['S'] = $r['title'];
      }

      if(!empty($r['o'])) {
        $map['cm_o']['S'] = $r['o'];
      }

      if(!empty($r['ou'])) {
        $map['cm_ou']['S'] = $r['ou'];
      }

      if(!empty($r['valid_from'])) {
        $map['cm_valid_from']['S'] = $r['valid_from'];
      }

      if(!empty($r['valid_through'])) {
        $map['cm_valid_through']['S'] = $r['valid_through'];
      }

      if(!empty($r['Cou'])) {
        $map['cm_cou']['S'] = $r['Cou']['name'];
      }

      $roles[]['M'] = $map;
    }

    if(!empty($roles)) {
      $item['cm_roles']['L'] = $roles;
    }

    // Identifier
    $identifiers = array();
    foreach($provisioningData['Identifier'] as $i) {
      $map = array();

      $map['cm_identifier']['S'] = $i['identifier'];
      $map['cm_type']['S'] = $i['type'];
      $map['cm_login']['BOOL'] = (bool) $i['login'];
      $map['cm_status']['S'] = StatusEnum::$to_api[$i['status']];

      $identifiers[]['M'] = $map;
    }

    if(!empty($identifiers)) {
      $item['cm_identifiers']['L'] = $identifiers;
    }

    // Name
    $names = array();
    foreach($provisioningData['Name'] as $n) {
      $map = array();

      if(!empty($n['honorific'])) {
        $map['cm_honorific']['S'] = $n['honorific'];
      }

      if(!empty($n['given'])) {
        $map['cm_given']['S'] = $n['given'];
      }

      if(!empty($n['middle'])) {
        $map['cm_middle']['S'] = $n['middle'];
      }

      if(!empty($n['family'])) {
        $map['cm_family']['S'] = $n['family'];
      }

      if(!empty($n['suffix'])) {
        $map['cm_suffix']['S'] = $n['suffix'];
      }

      $map['cm_type']['S'] = $n['type'];

      $map['cm_primary_name']['BOOL'] = (bool) $n['primary_name'];

      $names[]['M'] = $map;
    }

    $item['cm_names']['L'] = $names;

    // SshKey
    $keys = array();
    foreach($provisioningData['SshKey'] as $k) {
      $keys[] = $k['type'] . ' ' . $k['skey'] . ' ' . $k['comment'];
    }

    if(!empty($keys)) {
      $item['cm_sshkeys']['SS'] = $keys;
    }

    // UnixClusterAccount
    $accounts = array();
    if(!empty($provisioningData['UnixClusterAccount'])) {
      foreach($provisioningData['UnixClusterAccount'] as $a) {
        $map = array();

        $map['cm_description']['S'] = $a['UnixCluster']['Cluster']['description'];

        $map['cm_status']['S'] = StatusEnum::$to_api[$a['status']];

        $map['cm_username']['S'] = $a['username'];

        // DynamoDB will record the value as a number hence 'N', but during
        // transport it must be sent as a string.
        $map['cm_uid']['N'] = strval($a['uid']);

        $map['cm_gecos']['S'] = $a['gecos'];

        $map['cm_login_shell']['S'] = $a['login_shell'];

        $map['cm_home_directory']['S'] = $a['home_directory'];

        $accounts[]['M'] = $map;
      }
    }

    if(!empty($accounts)) {
      $item['cm_unix_cluster_accounts']['L'] = $accounts;
    }

    // Terms and Conditions
    $tandcs = array();

    if(!empty($provisioningData['CoTAndCAgreement'])) {
      foreach($provisioningData['CoTAndCAgreement'] as $a) {
        $map = array();

        if(!empty($a['CoTermsAndConditions']['description'])) {
          $map['cm_description']['S'] = $a['CoTermsAndConditions']['description'];
        }

        if(!empty($a['CoTermsAndConditions']['status'])) {
          $map['cm_status']['S'] = StatusEnum::$to_api[$a['CoTermsAndConditions']['status']];
        }

        // DynamoDB will record the value as a number hence 'N', but during
        // transport it must be sent as a string.
        $map['cm_agreement_time']['N'] = strval(strtotime($a['agreement_time']));

        $tandcs[]['M'] = $map;
      }
    }

    if(!empty($tandcs)) {
      $item['cm_tandc_agreements']['L'] = $tandcs;
    }

    // Marshall additional attributes as configured. For now we assume they
    // are to be marshalled as strings using the configured template that
    // substitutes values from Identifiers.
    $args = array();
    $args['conditions']['CoDynamoProvisionerAttrMap.co_dynamo_provisioner_target_id'] = $coProvisioningTargetData['CoDynamoProvisionerTarget']['id'];
    $args['contain'] = false;

    $attrMaps  = $this->CoDynamoProvisionerAttrMap->find('all', $args);

    foreach($attrMaps as $am) {
      $attributeName = $am['CoDynamoProvisionerAttrMap']['attribute'];
      $template = $am['CoDynamoProvisionerAttrMap']['attribute_template'];
      $constraint = $am['CoDynamoProvisionerAttrMap']['attribute_constraint'];
      $value = $this->CoDynamoProvisionerAttrMap->marshallAttributeValue($template, $constraint, $provisioningData);

      // The marshalled attribute value may be empty if the constraint is not met.
      if(!empty($value)) {
        $item[$attributeName]['S'] = $value;
      }
    }

    return $item;
  }

  /**
   * Provision for the specified CO Person.
   *
   * @since COmanage Registry v4.3.1
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @throws none
   * @return Boolean True on success
   */
  
  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    $deletePerson = false;

    switch($op) {
      case ProvisioningActionEnum::AuthenticatorUpdated:
      case ProvisioningActionEnum::CoPersonAdded:
      case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
      case ProvisioningActionEnum::CoPersonExpired:
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
      case ProvisioningActionEnum::CoPersonUnexpired:
      case ProvisioningActionEnum::CoPersonUpdated:
        break;
      case ProvisioningActionEnum::CoPersonDeleted:
        $deletePerson = true;
        break;
      default:
        // Ignore all other actions.
        return true;
        break;
    }

    $success = false;

    try {
      $success = $this->syncPerson($coProvisioningTargetData, $provisioningData, $deletePerson);
    } catch (Exception $e) {
      $this->log("DynamoProvisioner CoDynamoProvisionerTarget provision caught exception " . $e->getMessage());
      $success = false;
    }

    return $success;
  }

  /**
   * Put DynamoDB item into table.
   *
   * @since COmanage Registry v4.3.1
   * @param Array $coProvisioningTargetData provisioning target data
   * @param Array $provisioningData CP Person provisioning data
   * @param Array $key CoDynamoProvisionerKey key for item
   * @throws none
   * @return Boolean True on success
   */

  protected function putUserItem($coProvisioningTargetData, $provisioningData, $key) {

    $args = array();

    $args['Item'] = $this->marshallUserItem($coProvisioningTargetData, $provisioningData, $key);

    $args['TableName'] = $this->tableName;

    try {
      $result = $this->client->putItem($args);
    } catch (Exception $e) {
      $this->log("DynamoProvisioner CoDynamoProvisionerTarget putUserItem putItem caught exception " . $e->getMessage());
      return false;
    }

    return true;
  }

   /**
   * Determine the provisioning status of this target.
   *
   * @since  COmanage Registry v4.3.1
   * @param  Integer $coProvisioningTargetId CO Provisioning Target ID
   * @param  Model   $Model                  Model being queried for status (eg: CoPerson, CoGroup,
   *                                         CoEmailList, COService)
   * @param  Integer $id                     $Model ID to check status for
   * @throws none
   * @return Array ProvisioningStatusEnum, Timestamp of last update in epoch seconds, Comment
   */

  public function status($coProvisioningTargetId, $model, $id) {
    $ret = array();
    $ret['timestamp'] = null;
    $ret['comment'] = "";
    $ret['status'] = ProvisioningStatusEnum::NotProvisioned;

    // We only consider CO Person records at this time.
    if($model->name != 'CoPerson') {
      return $ret;
    }

    // Pull the configuration.
    $args = array();
    $args['conditions']['CoDynamoProvisionerTarget.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['contain'] = false;

    $coProvisioningTargetData = $this->find('first', $args);

    // See if we have recorded any item keys for this CO Person.
    $itemKeys = $this->CoDynamoProvisionerKey->getSavedKeysByCoPersonId($id, $coProvisioningTargetData['CoDynamoProvisionerTarget']['id']);

    if(empty($itemKeys)) {
      return $ret;
    }

    $ret['status'] = ProvisioningStatusEnum::Provisioned;

    // Get the last provision time from the parent status function
    $pstatus = parent::status($coProvisioningTargetId, $model, $id);
          
    if($pstatus['status'] == ProvisioningStatusEnum::Provisioned) {
      $ret['timestamp'] = $pstatus['timestamp'];
    }

    $comment = "";
    foreach($itemKeys as $itemKey) {
      $partitionKey = $itemKey['CoDynamoProvisionerKey']['partition_key'];
      $comment = $comment . $partitionKey;

      $sortKey = $itemKey['CoDynamoProvisionerKey']['sort_key'] ?? null;
      $comment = $comment . $sortKey;

      $comment = $comment. ",";
    }

    $comment = rtrim($comment, ",");

    $ret['comment'] = $comment;

    return $ret;
  }

  /**
   * Synchronize CO Person record with DynamoDB table item.
   *
   * @since COmanage Registry v4.3.1
   * @param Array $coProvisioningTargetData provisioning target data
   * @param Array $provisioningData CP Person provisioning data
   * @param Boolean $delete True if items should be deleted from table
   * @throws none
   * @return Boolean True on success
   */

  protected function syncPerson($coProvisioningTargetData, $provisioningData, $delete = false) {
    $coPersonId = $provisioningData['CoPerson']['id'];
    $logPrefix = "DynamoProvisioner CoDynamoProvisionerTarget syncPerson CO Person ID $coPersonId ";

    // Retrieve saved keys by CO Person ID if we have any.
    $savedKeys = $this->CoDynamoProvisionerKey->getSavedKeysByCoPersonId($coPersonId, $coProvisioningTargetData['CoDynamoProvisionerTarget']['id']);

    $msg = $logPrefix . "saved keys are " . print_r($savedKeys, true);
    $this->log($msg);

    // For provisioning actions other than CoPersonDeleted the CO Person status is factored into
    // the logic.
    $status = $provisioningData['CoPerson']['status'] ?? null;

    // If there are no saved keys and so this CO Person record has never been provisioned
    // and the record has any of these status values then do not provision. If the saved
    // key is not empty signal to delete the item and saved key.
    $noInitialProvisionStatus = array(
      StatusEnum::Confirmed,
      StatusEnum::Deleted,
      StatusEnum::Denied,
      StatusEnum::Duplicate,
      StatusEnum::Invited,
      StatusEnum::Pending,
      StatusEnum::PendingApproval,
      StatusEnum::PendingConfirmation,
      StatusEnum::PendingVetting,
      StatusEnum::Declined
    );

    if(in_array($status, $noInitialProvisionStatus)) {
      if(empty($savedKeys)) {
        $s = StatusEnum::$to_api[$status] ?? null;
        $msg = $logPrefix . "with no saved keys and status $s will not be provisioned";
        $this->log($msg);
        return true;
      } else {
        $delete = true;
      }
    }

    // Compute the keys based on current CoPerson record.
    try {
      $computedKeys = $this->CoDynamoProvisionerKey->getComputedKeysByCoPerson($coProvisioningTargetData, $provisioningData);
      $msg = $logPrefix . "computed keys are " . print_r($computedKeys, true);
      $this->log($msg);
    } catch (Exception $e) {
      $computedKeys = null;
      $msg = $logPrefix . "caught exception trying to compute keys";
      $this->log($msg);
      // If we cannot compute a key it is probably because the required Identifier
      // is not present for the CO Person record. It may have even been deleted
      // so there may be a saved key and we may need to delete the item.
      if(!empty($savedKeys)) {
        $msg = $logPrefix . "setting delete true";
        $this->log($msg);
        $delete = true;
      }
    }

    // Do not allow more than one computed key if we have not been configured
    // for duplicate items in the table.
    if(count($computedKeys) > 1 && !$coProvisioningTargetData['CoDynamoProvisionerTarget']['duplicate_item']) {
      $msg = $logPrefix . "more than one key computed but duplicate_item is configured false";
      $this->log($msg);
      return false;
    }

    // Create the DynamoDB client.
    try {
      $this->createClient($coProvisioningTargetData);
    } catch (Exception $e) {
      $msg = $logPrefix . "caught exception " . $e->getMessage();
      $this->log($msg);
      return false;
    }

    // Delete the items in DynamoDB and the saved keys when signalled.
    if($delete) {
      if(empty($savedKeys)) {
        $msg = $logPrefix . "has no saved keys so no delete required";
        $this->log($msg);
        return true;
      }

      $success = true;
      foreach($savedKeys as $savedKey) {
        $ret = $this->deleteUserItem($coProvisioningTargetData, $savedKey);
        if(!$ret) {
          // Log the error deleting the item from DynamoDB and do not attempt to delete the
          // saved key so that we still have a reference to the item in DynamoDB linked
          // to this CO Person record.
          $msg = $logPrefix . "failed deleting user item with saved key " . print_r($savedKey, true);
          $this->log($msg);
          $success = false;
          continue;
        }

        $ret = $this->CoDynamoProvisionerKey->delete($savedKey['CoDynamoProvisionerKey']['id']);
        if(!$ret) {
          $msg = $logPrefix . "failed deleting saved key " . print_r($savedKey, true);
          $this->log($msg);
          $success = false;
          continue;
        }

        $msg = $logPrefix . "deleted item with saved key " . print_r($savedKey, true);
        $this->log($msg);
      }

      return $success;
    }

    // We may not have any computed keys if the CO Person record does not have
    // the necessary Identifier(s).
    if(empty($computedKeys)) {
      $msg = $logPrefix . "no computed keys are available so will not provision";
      $this->log($msg);
      return true;
    }

    foreach($computedKeys as $computedKey) {
      // We always PUT the user item using a computed key thereby either adding
      // a new item or updating/replacing a current one in the table.
      $success = $this->putUserItem($coProvisioningTargetData, $provisioningData, $computedKey);

      // If we failed to PUT the user item then do no further work and signal failure.
      if(!$success) {
        $msg = $logPrefix . "failed to put item using computed key " . print_r($computedKey, true);
        $this->log($msg);
        return false;
      }

      $msg = $logPrefix . "put item with computed key " . print_r($computedKey, true);
      $this->log($msg);

      // If we had no saved keys then save the computed key now.
      if(empty($savedKeys)) {
        $this->CoDynamoProvisionerKey->clear();
        $this->CoDynamoProvisionerKey->save($computedKey);
      }
    }

    // If we had no saved keys then we are done now.
    if(empty($savedKeys)) {
      $msg = $logPrefix . "there were no previously saved keys so sync is complete now";
      $this->log($msg);
      return true;
    }

    // If the saved keys and computed keys are equal then we are done now.
    if($this->CoDynamoProvisionerKey->keySetsAreEqual($savedKeys, $computedKeys)) {
      $msg = $logPrefix . "previously saved keys and computed keys are equal so sync is complete now";
      $this->log($msg);
      return true;
    }

    // If we have gotten this far then the set of previously saved keys and computed
    // keys is different so process the differences now.
    // First, save any computed keys that were not previously saved.
    foreach($computedKeys as $computedKey) {
      if(!$this->CoDynamoProvisionerKey->keyInSet($computedKey, $savedKeys)) {
        $this->CoDynamoProvisionerKey->clear();
        $this->CoDynamoProvisionerKey->save($computedKey);
      }
    }

    // Next for any saved key that is not in the set of computed keys we must
    // delete the old item in the DynamoDB table and then delete the old saved key.
    foreach($savedKeys as $savedKey) {
      if(!$this->CoDynamoProvisionerKey->keyInSet($savedKey, $computedKeys)) {
        $success = $this->deleteUserItem($coProvisioningTargetData, $savedKey);
        if($success) {
          $msg = $logPrefix . "deleted item for previously saved key " . print_r($savedKey, true);
          $this->log($msg);
        }
        else {
          // Log the error deleting the saved item but return true since we did
          // provision the computed keys.
          $msg = $logPrefix . "failed deleting user item with saved key " . print_r($savedKey, true);
          $this->log($msg);
          return true;
        }

        $success = $this->CoDynamoProvisionerKey->delete($savedKey['CoDynamoProvisionerKey']['id']);
        if(!$success) {
          // Log the error deleting the saved key but return true since we did
          // provision the computed keys.
          $msg = $logPrefix . "failed deleting saved key " . print_r($savedKey, true);
          $this->log($msg);
          return true;
        }
      }
    }
    
    return true;
  }

  /**
   * Validate the aws_region field.
   *
   * @since COmanage Registry v4.3.1
   * @param Array $check the input data to check
   * @throws none
   * @return Boolean True if input data validates
   */

  public function validateAwsRegion($check) {
    return array_key_exists($check['aws_region'], AwsRegionEnum::$allAwsRegions);
  }
}
