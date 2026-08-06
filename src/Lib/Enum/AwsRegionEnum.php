<?php
/**
 * COmanage Registry AWS Region Enum
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

namespace DynamoProvisioner\Lib\Enum;

use App\Lib\Enum\StandardEnum;
use Cake\Utility\Inflector;

class AwsRegionEnum extends StandardEnum {
  const AfricaCapeTown = 'af-south-1';
  
  const AsiaPacificHongKong  = 'ap-east-1';
  const AsiaPacificHyderabad = 'ap-south-2';
  const AsiaPacificJakarta   = 'ap-southeast-3';
  const AsiaPacificMelbourne = 'ap-southeast-4';
  const AsiaPacificMumbai    = 'ap-south-1';
  const AsiaPacificOsaka     = 'ap-northeast-3';
  const AsiaPacificSeoul     = 'ap-northeast-2';
  const AsiaPacificSingapore = 'ap-southeast-1';
  const AsiaPacificSydney    = 'ap-southeast-2';
  const AsiaPacificTokyo     = 'ap-northeast-1';
  
  const CanadaCalgary        = 'ca-west-1';
  const CanadaCentral        = 'ca-central-1';
  
  const EuropeFrankfurt      = 'eu-central-1';
  const EuropeIreland        = 'eu-west-1';
  const EuropeLondon         = 'eu-west-2';
  const EuropeMilan          = 'eu-south-1';
  const EuropeParis          = 'eu-west-3';
  const EuropeSpain          = 'eu-south-2';
  const EuropeStockholm      = 'eu-north-1';
  const EuropeZurich         = 'eu-central-2';
  
  const IsraelTelAviv        = 'il-central-1';
  
  const MiddleEastBahrain    = 'me-south-1';
  const MiddleEastUAE        = 'me-central-1';
  
  const SouthAmericaSaoPaulo = 'sa-east-1';
  
  const USEastVirginia       = 'us-east-1';
  const USEastOhio           = 'us-east-2';
  const USWestCalifornia     = 'us-west-1';
  const USWestOregon         = 'us-west-2';
  
  /**
   * Get regions formatted with descriptive name and region code.
   * Returns array with region code as key and "Description (code)" as value.
   *
   * @since  COmanage Registry v5.0.0
   * @return array Array of region codes and their formatted descriptions
   */
  public static function getDescriptiveRegions(): array
  {
    $reflect = new \ReflectionClass(get_called_class());
    $consts = $reflect->getConstants();
    
    $ret = [];
    foreach ($consts as $name => $code) {
      // Convert constant name like "AsiaPacificTokyo" to "Asia Pacific (Tokyo)"
      $humanized = Inflector::humanize(Inflector::underscore($name));
      // Format as "Asia Pacific Tokyo (ap-northeast-1)"
      $ret[$code] = $humanized . ' (' . $code . ')';
    }
    
    return $ret;
  }
}
