<!--
/**
 * COmanage Registry DynamoProvisioner Target Fields
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
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.3.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return false;
  
  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_provisioning_targets';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_provisioning_targets.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_provisioning_targets';
  $args['action'] = 'edit';
  $args[] = $vv_ptid;
  $this->Html->addCrumb($co_dynamo_provisioner_targets[0]['CoProvisioningTarget']['description'], $args);

  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  print $this->Form->hidden('co_provisioning_target_id', array('default' => $vv_ptid)) . "\n";

  if($e && !empty($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['id'])) {
    print $this->Form->hidden('id', array('default' => $co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['id']));
  }
?>

<ul id="<?php print $this->action; ?>_co_dynamo_provisioner_target" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('aws_region', _txt('pl.dynamoprovisioner.region')) : _txt('pl.dynamoprovisioner.region')); ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['aws_region']) 
                           ? $co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['aws_region']
                           : null);
        // Since required=true, this will render blank by default but not allow save until a server is selected
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('aws_region',
                                    $vv_aws_regions,
                                    $attrs);

          if($this->Form->isFieldError('aws_region')) {
            print $this->Form->error('aws_region');
          }
        } else {
          if(!empty($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['aws_region'])) {
            print filter_var($vv_servers[ $co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['aws_region'] ], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>

  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('table_name', _txt('pl.dynamoprovisioner.table_name')) : _txt('pl.dynamoprovisioner.table_name')); ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          print $this->Form->input('table_name');

          if($this->Form->isFieldError('table_name')) {
            print $this->Form->error('table_name');
          }
        } else {
          if(!empty($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['table_name'])) {
            print filter_var($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['table_name'], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>

  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('aws_access_key_id', _txt('pl.dynamoprovisioner.aws_access_key_id')) : _txt('pl.dynamoprovisioner.aws_access_key_id')); ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          print $this->Form->text('aws_access_key_id');

          if($this->Form->isFieldError('aws_access_key_id')) {
            print $this->Form->error('aws_access_key_id');
          }
        } else {
          if(!empty($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['aws_access_key_id'])) {
            print filter_var($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['aws_access_key_id'], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>

  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('aws_secret_access_key', _txt('pl.dynamoprovisioner.aws_secret_access_key')) : _txt('pl.dynamoprovisioner.aws_secret_access_key')); ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          print $this->Form->password('aws_secret_access_key');

          if($this->Form->isFieldError('aws_secret_access_key')) {
            print $this->Form->error('aws_secret_access_key');
          }
        } else {
          if(!empty($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['aws_secret_access_key'])) {
            print filter_var($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['aws_secret_access_key'], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>

  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('partition_key', _txt('pl.dynamoprovisioner.partition_key')) : _txt('pl.dynamoprovisioner.partition_key')); ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          print $this->Form->text('partition_key');

          if($this->Form->isFieldError('partition_key')) {
            print $this->Form->error('partition_key');
          }
        } else {
          if(!empty($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['partition_key'])) {
            print filter_var($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['partition_key'], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>

  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('partition_key_template', _txt('pl.dynamoprovisioner.partition_key_template')) : _txt('pl.dynamoprovisioner.partition_key_template')); ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          print $this->Form->text('partition_key_template');

          if($this->Form->isFieldError('partition_key_template')) {
            print $this->Form->error('partition_key_template');
          }
        } else {
          if(!empty($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['partition_key_template'])) {
            print filter_var($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['partition_key_template'], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>

  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('sort_key', _txt('pl.dynamoprovisioner.sort_key')) : _txt('pl.dynamoprovisioner.sort_key')); ?>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          $args = array();
          $args['required'] = false;
          print $this->Form->text('sort_key', $args);

          if($this->Form->isFieldError('sort_key')) {
            print $this->Form->error('sort_key');
          }
        } else {
          if(!empty($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['sort_key'])) {
            print filter_var($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['sort_key'], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>

  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('sort_key_template', _txt('pl.dynamoprovisioner.sort_key_template')) : _txt('pl.dynamoprovisioner.sort_key_template')); ?>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          $args = array();
          $args['required'] = false;
          print $this->Form->text('sort_key_template', $args);

          if($this->Form->isFieldError('sort_key_template')) {
            print $this->Form->error('sort_key_template');
          }
        } else {
          if(!empty($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['sort_key_template'])) {
            print filter_var($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerTarget']['sort_key_template'], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>

  <?php for($i = 0; $i < 10; $i++): ?>
    <?php 
        if($e && !empty($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerAttrMap'][$i]['id'])) {
        print $this->Form->hidden('CoDynamoProvisionerAttrMap.' . "$i" . '.id', array('default' => $co_dynamo_provisioner_targets[0]['CoDynamoProvisionerAttrMap'][$i]['id'])) . "\n";
      }
    ?>
    <?php print $this->Form->hidden('CoDynamoProvisionerAttrMap.' . "$i" . '.co_dynamo_provisioner_target_id', array('default' => $vv_ptid)) . "\n"; ?>
    <li>
      <div class="field-name">
        <?php print ($e ? $this->Form->label('CoDynamoProvisionerAttrMap.' . "$i" . '.attribute', _txt('pl.dynamoprovisioner.attribute')) : _txt('pl.dynamoprovisioner.attribute')); ?>
      </div>
      <div class="field-info">
        <?php
          if($e) {
            $args = array();
            $args['required'] = false;
            print $this->Form->text('CoDynamoProvisionerAttrMap.' . "$i" . '.attribute', $args);
          } else {
            if(!empty($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerAttrMap'][$i]['attribute'])) {
              print filter_var($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerAttrMap'][$i]['attribute'], FILTER_SANITIZE_SPECIAL_CHARS);
            }
          }
        ?>
      </div>
    </li>

    <li>
      <div class="field-name">
        <?php print ($e ? $this->Form->label('CoDynamoProvisionerAttrMap.' . "$i" . '.attribute_template', _txt('pl.dynamoprovisioner.attribute_template')) : _txt('pl.dynamoprovisioner.attribute_template')); ?>
      </div>
      <div class="field-info">
        <?php
          if($e) {
            $args = array();
            $args['required'] = false;
            print $this->Form->text('CoDynamoProvisionerAttrMap.' . "$i" . '.attribute_template', $args);
          } else {
            if(!empty($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerAttrMap'][$i]['attribute_template'])) {
              print filter_var($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerAttrMap'][$i]['attribute_template'], FILTER_SANITIZE_SPECIAL_CHARS);
            }
          }
        ?>
      </div>
    </li>

    <li>
      <div class="field-name">
        <?php print ($e ? $this->Form->label('CoDynamoProvisionerAttrMap.' . "$i" . '.attribute_constraint', _txt('pl.dynamoprovisioner.attribute_constraint')) : _txt('pl.dynamoprovisioner.attribute_constraint')); ?>
      </div>
      <div class="field-info">
        <?php
          if($e) {
            $args = array();
            $args['required'] = false;
            print $this->Form->text('CoDynamoProvisionerAttrMap.' . "$i" . '.attribute_constraint', $args);
          } else {
            if(!empty($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerAttrMap'][$i]['attribute_constraint'])) {
              print filter_var($co_dynamo_provisioner_targets[0]['CoDynamoProvisionerAttrMap'][$i]['attribute_constraint'], FILTER_SANITIZE_SPECIAL_CHARS);
            }
          }
        ?>
      </div>
    </li>
  <?php endfor; ?> 

  <?php if($e): ?>
  <li class="fields-submit">
    <div class="field-name">
      <span class="required"><?php print _txt('fd.req'); ?></span>
    </div>
    <div class="field-info">
      <?php print $this->Form->submit($submit_label); ?>
    </div>
  </li>
  <?php endif; ?>
</ul>
