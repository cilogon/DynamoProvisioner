<?php
/**
 * COmanage Registry DynamoDB Provisioner Plugin Controller
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

App::uses("SPTController", "Controller");

class CoDynamoProvisionerTargetsController extends SPTController {
  // Class name, used by Cake
  public $name = "CoDynamoProvisionerTargets";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'server_id' => 'asc'
    )
  );

  public $requires_co = true;

  public $edit_contains = array(
    'CoProvisioningTarget',
    'CoDynamoProvisionerAttrMap'
  );

  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v4.3.1
   * @throws none
   */

  function beforeRender() {
    parent::beforeRender();

    if(!$this->request->is('restful')) {
      $this->set('vv_aws_regions', AwsRegionEnum::$allAwsRegions);
    }
  }

  /**
   * Edit action for CoDynamoProvisionerTarget.
   *
   * @since COmanage Registry v4.3.1
   * @param Integer $id ID for target to edit
   * @throws none
   * @return Boolean True on success
   */

  function edit($id) {
    // Pull the current data.
    $args = array();
    $args['conditions']['CoDynamoProvisionerTarget.id'] = $id;
    $args['contain'] = $this->edit_contains;
    
    $curdata = $this->CoDynamoProvisionerTarget->find('first', $args);

    // PUT OR POST
    if($this->request->is(array('post', 'put'))) {
      $data = & $this->request->data;

      // Remove any empty attribute maps in the input data.
      foreach($data['CoDynamoProvisionerAttrMap'] as $i => $map) {
        if(empty($map['attribute'])) {
          unset($data['CoDynamoProvisionerAttrMap'][$i]);
        }
      }

      // Validate the provisioner target input data.
      $this->CoDynamoProvisionerTarget->set($data);
      if(!$this->CoDynamoProvisionerTarget->validates()) {
        $this->Flash->set(_txt('er.fields'), array('key' => 'error'));
        $this->request->data = $curdata;
        $this->set('co_dynamo_provisioner_targets', array(0 => $curdata));
        $this->set('title_for_layout',  _txt('op.edit-a', array(_txt('ct.co_dynamo_provisioner_targets.1'))));
        return false;
      }

      // Validate the attribute map (if any) input data.
      foreach($data['CoDynamoProvisionerAttrMap'] as $dam) {
        //$this->CoDynamoProvisionerTarget->CoDynamoProvisionerAttrMap->set(array('CoDynamoProvisionerAttrMap' => $dam));
        $this->CoDynamoProvisionerTarget->CoDynamoProvisionerAttrMap->set($dam);
        if(!$this->CoDynamoProvisionerTarget->CoDynamoProvisionerAttrMap->validates()) {
          $this->Flash->set(_txt('er.fields'), array('key' => 'error'));
          $this->request->data = $curdata;
          $this->set('co_dynamo_provisioner_targets', array(0 => $curdata));
          $this->set('title_for_layout',  _txt('op.edit-a', array(_txt('ct.co_dynamo_provisioner_targets.1'))));
          return false;
        }
      }

      // saveAssociated will not delete an attribute map that is no longer in
      // the submitted form data but is in the current data so delete it
      // directly.
      foreach($curdata['CoDynamoProvisionerAttrMap'] as $cam) {
        $delete = true;
        foreach($data['CoDynamoProvisionerAttrMap'] as $dam) {
          if(!empty($dam['id']) && ($dam['id'] == $cam['id'])) {
            $delete = false;
          }
        }
        if($delete) {
          $this->CoDynamoProvisionerTarget->CoDynamoProvisionerAttrMap->delete($cam['id']);
        }
      }

      $args = array();
      $args['validate'] = false;
      $args['deep'] = true;

      $ret = $this->CoDynamoProvisionerTarget->saveAssociated($this->request->data, $args);
      if($ret) {
        // Success so set flash for success and redirect to index view.
        $name = $curdata['CoProvisioningTarget']['description'];
        $this->Flash->set(_txt('rs.updated', array(filter_var($name,FILTER_SANITIZE_SPECIAL_CHARS))), array('key' => 'success'));

        $args = array();
        $args['plugin'] = null;
        $args['controller'] = 'co_provisioning_targets';
        $args['action'] = 'index';
        $args['co'] = $this->cur_co['Co']['id'];
        $this->redirect($args);
      } else {
        // Set error flash and fall through to GET.
        $this->Flash->set(_txt('er.fields'), array('key' => 'error'));
      }
    } 

    // GET request.
    $this->request->data = $curdata;
    $this->set('co_dynamo_provisioner_targets', array(0 => $curdata));
    $this->set('title_for_layout',  _txt('op.edit-a', array(_txt('ct.co_dynamo_provisioner_targets.1'))));
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.3.1
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Delete an existing CO Provisioning Target?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Provisioning Target?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Provisioning Targets?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO Provisioning Target?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
