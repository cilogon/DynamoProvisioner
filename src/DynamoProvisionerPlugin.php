<?php
/**
 * COmanage Registry Dynamo Provisioner Plugin
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

namespace DynamoProvisioner;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Routing\RouteBuilder;

class DynamoProvisionerPlugin extends BasePlugin {
  /**
   * Plugin bootstrap hook.
   *
   * @param PluginApplicationInterface $app The host application
   * @return void
   */
  public function bootstrap(PluginApplicationInterface $app): void
  {
    parent::bootstrap($app);
  }
  
  /**
   * Add routes for the plugin.
   *
   * @param RouteBuilder $routes The route builder to update.
   * @return void
   */
  public function routes(RouteBuilder $routes): void
  {
    $routes->plugin(
      'DynamoProvisioner',
      ['path' => '/dynamo-provisioner'],
      function (RouteBuilder $builder) {
        $builder->fallbacks();
      }
    );
    parent::routes($routes);
  }
}
