<?php

App::uses("CoProvisionerPluginTarget", "Model");

class CoDynamoProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoDynamoProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoProvisioningTarget",
    "Server"
  );
  
  // Default display field for cake generated views
  public $displayField = "server_id";
  
  // Request HTTP servers
  public $cmServerType = ServerEnum::HttpServer;
  
  protected $Http = null;
  
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'server_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'allowEmpty' => false,
        'unfreeze' => 'CO'
      )
    )
  );

  /**
   * Provision for the specified CO Person.
   *
   * @since  COmanage Registry v4.1.0
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    $this->log("provision is called");
    $this->log("coProvisioningTargetData is " . print_r($coProvisioningTargetData, true));
    $this->log("op is " . print_r($op, true));
    $this->log("provisioningData is " . print_r($provisioningData, true));

    return true;
  }

   /**
   * Determine the provisioning status of this target.
   *
   * @since  COmanage Registry v4.1.0
   * @param  Integer $coProvisioningTargetId CO Provisioning Target ID
   * @param  Model   $Model                  Model being queried for status (eg: CoPerson, CoGroup,
   *                                         CoEmailList, COService)
   * @param  Integer $id                     $Model ID to check status for
   * @return Array ProvisioningStatusEnum, Timestamp of last update in epoch seconds, Comment
   * @throws InvalidArgumentException If $coPersonId not found
   * @throws RuntimeException For other errors
   */

  public function status($coProvisioningTargetId, $model, $id) {
    $ret = array();
    $ret['timestamp'] = null;
    $ret['comment'] = "";
    $ret['status'] = ProvisioningStatusEnum::NotProvisioned;

    return $ret;
  }

}
