<?php
/**
 * COmanage Registry Dynamo Provisioner Attribute Maps Element
 *
 * Rendered after Form->create() so fields are registered with FormProtection.
 *
 * @link          https://github.com/cilogon/DynamoProvisioner
 * @package       registry-plugin
 * @since         COmanage Registry v5.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

declare(strict_types=1);

// Dynamic nested fields are added/removed via JS
$this->Form->unlockField('dynamo_provisioner_attr_maps');

$hideAttrMaps = $hideAttrMaps ?? true;
$existingMappings = $existingMappings ?? [];
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
</script>
<div class="field-name">
  <div class="field-title">
    Additional Attributes
  </div>
  <div class="field-desc">Map additional attributes to be provisioned to DynamoDB</div>
</div>
<div class="field-info">
  <?php
    $params = ['class' => 'addbutton', 'onclick' => 'addFirstAttrMapping()', 'id' => 'add_first_attr_mapping'];
    if(!$hideAttrMaps) {
      $params['style'] = 'display:none';
    }
    print '<div>' . $this->Html->tag('a', 'Add Attribute Mapping', $params) . '</div>';
  ?>
</div>

<ul id="dynamo_provisioner_attr_maps_container" class="field-children"<?= $hideAttrMaps ? ' style="display: none;"' : '' ?>>
  <div id="dynamo_provisioner_attr_maps_list">
    <?php
      $displayAddButton = false;

      for($i = 0; $i < 10; $i++) {
        $display = "display:none";
        $attribute = "";
        $attribute_template = "";
        $attribute_constraint = "";
        $id = null;

        if(isset($existingMappings[$i])) {
          $map = $existingMappings[$i];
          $attribute = filter_var($map->attribute ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
          $attribute_template = filter_var($map->attribute_template ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
          $attribute_constraint = filter_var($map->attribute_constraint ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
          $id = $map->id ?? null;
        }

        if(!empty($attribute) || !empty($attribute_template) || !empty($attribute_constraint)
           || ($i == 0 && count($existingMappings) == 0 && !$hideAttrMaps)) {
          $display = "";
          $displayAddButton = true;
        }

        print '<div class="field-children-inline" style="' . $display . '" id="dynamo_provisioner_attr_map_' . $i . '">';
        print '<ul class="field-children field-attr-mapping">';

        if($id) {
          print $this->Form->hidden("dynamo_provisioner_attr_maps.$i.id", ['value' => $id]);
        }

        print '  <li>';
        print '    <div class="field-name"><div class="field-title">';
        print $this->Form->label("dynamo_provisioner_attr_maps.$i.attribute", 'Attribute Name');
        print '       <span class="required">*</span></div></div>';
        print '   <div class="field-info">';
        $args = ['label' => false, 'required' => !empty($attribute)];
        if(!empty($attribute)) {
          $args['value'] = $attribute;
        }
        print $this->Form->control("dynamo_provisioner_attr_maps.$i.attribute", $args);
        print '   </div></li>';

        print '  <li>';
        print '    <div class="field-name"><div class="field-title">';
        print $this->Form->label("dynamo_provisioner_attr_maps.$i.attribute_template", 'Attribute Value Template');
        print '        <span class="required">*</span></div></div>';
        print '    <div class="field-info">';
        $args = ['label' => false, 'required' => !empty($attribute_template)];
        if(!empty($attribute_template)) {
          $args['value'] = $attribute_template;
        }
        print $this->Form->control("dynamo_provisioner_attr_maps.$i.attribute_template", $args);
        print '    </div></li>';

        print '  <li>';
        print '    <div class="field-name"><div class="field-title">';
        print $this->Form->label("dynamo_provisioner_attr_maps.$i.attribute_constraint", 'Attribute Constraint');
        print '      </div></div>';
        print '   <div class="field-info">';
        $args = ['label' => false, 'required' => false];
        if(!empty($attribute_constraint)) {
          $args['value'] = $attribute_constraint;
        }
        print $this->Form->control("dynamo_provisioner_attr_maps.$i.attribute_constraint", $args);
        print '   </div></li>';

        print '</ul>';
        print $this->Html->tag('button', 'Delete', [
          'class' => 'deletebutton',
          'type' => 'button',
          'onclick' => 'removeAttrMappingInput(this)'
        ]);
        print '</div>';
      }

      $params = ['class' => 'addbutton', 'onclick' => 'addAttrMappingInput()', 'id' => 'add_another_attr_mapping'];
      if(!$displayAddButton) {
        $params['style'] = 'display:none';
      }
      print '<div>' . $this->Html->tag('a', 'Add Another', $params) . '</div>';
    ?>
  </div>
</ul>
