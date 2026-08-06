<?php
/**
 * COmanage Registry Dynamo Provisioners Fields
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
?>

<script type="text/javascript">
  function addFirstAttrMapping() {
    $("#dynamo_provisioner_attr_maps_container").show();
    $("#dynamo_provisioner_attr_map_0").show();
    $("#dynamo_provisioner_attr_map_0").find("input[type=text]").attr("required", "required");
    $("#add_first_attr_mapping").hide();
    $("#add_another_attr_mapping").show();
  }

  function addAttrMappingInput() {
    $("#dynamo_provisioner_attr_maps_list").find("div.field-children-inline:hidden:first").find("input[type=text]").attr("required", "required");
    $("#dynamo_provisioner_attr_maps_list").find("div.field-children-inline:hidden:first").show();
  }

  function removeAttrMappingInput(e) {
    $(e).parent('div.field-children-inline').find(':text').val("").removeAttr("required");
    $(e).closest('div.field-children-inline').hide();

    var visible = $("#dynamo_provisioner_attr_maps_list").find("div.field-children-inline:visible").length;
    if (visible == 0) {
      $("#dynamo_provisioner_attr_maps_container").hide();
      $("#add_first_attr_mapping").show();
      $("#add_another_attr_mapping").hide();
    }
  }

  function js_local_onload() {
    // Any additional initialization can go here
  }
</script>

<?php
if ($vv_action == 'add' || $vv_action == 'edit') {
  print $this->element('form/listItem', [
    'arguments' => [
      'fieldName' => 'aws_region',
      'fieldType' => 'select',
      'fieldSelectOptions' => $vv_aws_regions,
      'fieldOptions' => [
        'required' => true,
        'empty' => true
      ]
    ]
  ]);

  print $this->element('form/listItem', [
    'arguments' => [
      'fieldName' => 'table_name'
    ]
  ]);

  print $this->element('form/listItem', [
    'arguments' => [
      'fieldName' => 'aws_access_key'
    ]
  ]);

  print $this->element('form/listItem', [
    'arguments' => [
      'fieldName' => 'aws_secret_key',
      'fieldOptions' => [
        'type' => 'password'
      ]
    ]
  ]);

  print $this->element('form/listItem', [
    'arguments' => [
      'fieldName' => 'partition_key'
    ]
  ]);

  print $this->element('form/listItem', [
    'arguments' => [
      'fieldName' => 'partition_key_template'
    ]
  ]);

  print $this->element('form/listItem', [
    'arguments' => [
      'fieldName' => 'sort_key'
    ]
  ]);

  print $this->element('form/listItem', [
    'arguments' => [
      'fieldName' => 'sort_key_template'
    ]
  ]);

  print $this->element('form/listItem', [
    'arguments' => [
      'fieldName' => 'duplicate_item'
    ]
  ]);

  // Additional attribute mappings section
  // Determine if any attribute mappings have existing data
  $hideAttrMaps = true;
  $existingMappings = [];
  
  // Check if we have the object and it has attr_maps
  if (isset($vv_obj) && is_object($vv_obj)) {
    if (isset($vv_obj->dynamo_provisioner_attr_maps)) {
      $attrMaps = $vv_obj->dynamo_provisioner_attr_maps;
      // Handle both array and collection
      if (is_array($attrMaps) || is_object($attrMaps)) {
        foreach ($attrMaps as $map) {
          if (is_object($map)) {
            if (!empty($map->attribute) || !empty($map->attribute_template) || !empty($map->attribute_constraint)) {
              $hideAttrMaps = false;
              $existingMappings[] = $map;
            }
          }
        }
      }
    }
  }

  // Check validation errors - in CakePHP 4, errors are on the entity
  if (isset($vv_obj) && is_object($vv_obj) && method_exists($vv_obj, 'hasErrors')) {
    if ($vv_obj->hasErrors('dynamo_provisioner_attr_maps')) {
      $hideAttrMaps = false;
    }
  }
?>

<li>
  <div class="field-name">
    <div class="field-title">
      Additional Attributes
    </div>
    <div class="field-desc">Map additional attributes to be provisioned to DynamoDB</div>
  </div>
  <div class="field-info">
    <?php
      print '<div>';
      $params = array();
      $params['class'] = 'addbutton';
      $params['onclick'] = 'addFirstAttrMapping()';
      $params['id'] = 'add_first_attr_mapping';
      if (!$hideAttrMaps) {
        $params['style'] = 'display:none';
      }
      print $this->Html->tag('a', 'Add Attribute Mapping', $params);
      print '</div>';
    ?>
  </div>

  <?php if ($hideAttrMaps): ?>
  <ul id="dynamo_provisioner_attr_maps_container" class="field-children" style="display: none;">
  <?php else: ?>
  <ul id="dynamo_provisioner_attr_maps_container" class="field-children">
  <?php endif; ?>
    <div id="dynamo_provisioner_attr_maps_list">
      <?php
        $displayAddButton = false;
        
        for ($i = 0; $i < 10; $i++) {
          $display = "display:none";
          $attribute = "";
          $attribute_template = "";
          $attribute_constraint = "";
          $id = null;
          
          // Check if we have existing data for this index
          if (isset($existingMappings[$i])) {
            $map = $existingMappings[$i];
            $attribute = filter_var($map->attribute ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
            $attribute_template = filter_var($map->attribute_template ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
            $attribute_constraint = filter_var($map->attribute_constraint ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
            $id = $map->id ?? null;
          }
          
          // Show this row if it has data or if it's the first row and we have no existing mappings
          if (!empty($attribute) || !empty($attribute_template) || !empty($attribute_constraint) || ($i == 0 && count($existingMappings) == 0 && !$hideAttrMaps)) {
            $display = "";
            $displayAddButton = true;
          }
          
          print '<div class="field-children-inline" style="' . $display . '" id="dynamo_provisioner_attr_map_' . $i . '">';
          print '<ul class="field-children field-attr-mapping">';
          
          // Hidden ID field for existing records
          if ($id) {
            print $this->Form->hidden("dynamo_provisioner_attr_maps.$i.id", ['value' => $id]);
          }
          
          // Attribute field
          print '  <li>';
          print '    <div class="field-name">';
          print '      <div class="field-title">';
          print $this->Form->label("dynamo_provisioner_attr_maps.$i.attribute", 'Attribute Name');
          print '       <span class="required">*</span>';
          print '     </div>';
          print '   </div>';
          print '   <div class="field-info">';
          $args = array();
          $args['label'] = false;
          $args['required'] = !empty($attribute);
          if (!empty($attribute)) {
            $args['value'] = $attribute;
          }
          print $this->Form->control("dynamo_provisioner_attr_maps.$i.attribute", $args);
          print '   </div>';
          print ' </li>';
          
          // Attribute Template field
          print '  <li>';
          print '    <div class="field-name">';
          print '      <div class="field-title">';
          print $this->Form->label("dynamo_provisioner_attr_maps.$i.attribute_template", 'Attribute Value Template');
          print '        <span class="required">*</span>';
          print '      </div>';
          print '    </div>';
          print '    <div class="field-info">';
          $args = array();
          $args['label'] = false;
          $args['required'] = !empty($attribute_template);
          if (!empty($attribute_template)) {
            $args['value'] = $attribute_template;
          }
          print $this->Form->control("dynamo_provisioner_attr_maps.$i.attribute_template", $args);
          print '    </div>';
          print '  </li>';
          
          // Attribute Constraint field
          print '  <li>';
          print '    <div class="field-name">';
          print '      <div class="field-title">';
          print $this->Form->label("dynamo_provisioner_attr_maps.$i.attribute_constraint", 'Attribute Constraint');
          print '      </div>';
          print '   </div>';
          print '   <div class="field-info">';
          $args = array();
          $args['label'] = false;
          $args['required'] = false;
          if (!empty($attribute_constraint)) {
            $args['value'] = $attribute_constraint;
          }
          print $this->Form->control("dynamo_provisioner_attr_maps.$i.attribute_constraint", $args);
          print '   </div>';
          print '  </li>';
          
          print '</ul>';
          
          // Delete button
          $params = array();
          $params['class'] = "deletebutton";
          $params['type'] = "button";
          $params['onclick'] = 'removeAttrMappingInput(this)';
          print $this->Html->tag('button', 'Delete', $params);
          print '</div>';
        }
        
        // Add another button
        print '<div>';
        $params = array();
        $params['class'] = 'addbutton';
        $params['onclick'] = 'addAttrMappingInput()';
        if (!$displayAddButton) {
          $params['style'] = 'display:none';
        }
        $params['id'] = 'add_another_attr_mapping';
        print $this->Html->tag('a', 'Add Another', $params);
        print '</div>';
      ?>
    </div>
  </ul>
</li>

<?php
}
?>
