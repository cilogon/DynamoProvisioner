<?php
/**
 * COmanage Registry Dynamo Provisioners Controller
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

namespace DynamoProvisioner\Controller;

use App\Controller\StandardPluginController;
use DynamoProvisioner\Lib\Enum\AwsRegionEnum;

class DynamoProvisionersController extends StandardPluginController {
  public $paginate = [
    'order' => [
      'DynamoProvisioners.id' => 'asc'
    ]
  ];
  
  /**
   * Override edit to handle attribute map deletions like CakePHP 2 version.
   *
   * @since  COmanage Registry v5.0.0
   * @param  string $id Object ID
   * @return \Cake\Http\Response|null|void
   */
  public function edit(string $id)
  {
    $table = $this->DynamoProvisioners;
    
    // Get current data from database
    $curdata = $table->get($id, ['contain' => ['DynamoProvisionerAttrMaps']]);
    
    if ($this->request->is(['post', 'put'])) {
      $data = $this->request->getData();
      
      // Remove any empty attribute maps from the request data
      if (isset($data['dynamo_provisioner_attr_maps'])) {
        foreach ($data['dynamo_provisioner_attr_maps'] as $i => $map) {
          if (empty($map['attribute'])) {
            unset($data['dynamo_provisioner_attr_maps'][$i]);
          }
        }
      }
      
      // Patch the entity with the cleaned data
      $entity = $table->patchEntity($curdata, $data);
      
      // Delete attribute maps that are in the database but not in the submission
      if (!empty($curdata->dynamo_provisioner_attr_maps)) {
        $submittedIds = [];
        if (!empty($data['dynamo_provisioner_attr_maps'])) {
          foreach ($data['dynamo_provisioner_attr_maps'] as $map) {
            if (!empty($map['id'])) {
              $submittedIds[] = $map['id'];
            }
          }
        }
        
        $AttrMaps = $this->getTableLocator()->get('DynamoProvisioner.DynamoProvisionerAttrMaps');
        foreach ($curdata->dynamo_provisioner_attr_maps as $existing) {
          if (!in_array($existing->id, $submittedIds)) {
            $AttrMaps->delete($existing);
          }
        }
      }
      
      // Save the entity
      if ($table->save($entity)) {
        $this->Flash->success(__d('result', 'saved'));
        return $this->redirect(['action' => 'edit', $id]);
      } else {
        $this->Flash->error(__d('error', 'save', ['DynamoProvisioners']));
      }
    }
    
    // Fall through to parent for GET requests and failed saves
    return parent::edit($id);
  }
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v5.0.0
   * @param  \Cake\Event\EventInterface $event Event
   * @return \Cake\Http\Response|null|void
   */
  public function beforeRender(\Cake\Event\EventInterface $event)
  {
    parent::beforeRender($event);
    
    // Make AWS regions available to the view with descriptive names
    $this->set('vv_aws_regions', AwsRegionEnum::getDescriptiveRegions());
  }
}
