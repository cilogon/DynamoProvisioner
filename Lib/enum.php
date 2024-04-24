<?php
/**
 * COmanage Registry DynamoDB Provisioner Plugin enums
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

class AwsRegionEnum
{
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

  public static $allAwsRegions = array(
    AwsRegionEnum::AfricaCapeTown       => 'Africa (Cape Town) af-south-1',
    AwsRegionEnum::AsiaPacificHongKong  => 'Asia Pacific (Hong Kong) ap-east-1',
    AwsRegionEnum::AsiaPacificHyderabad => 'Asia Pacific (Hyderbad) ap-south-2',
    AwsRegionEnum::AsiaPacificJakarta   => 'Asia Pacific (Jakart) ap-southeast-3',
    AwsRegionEnum::AsiaPacificMelbourne => 'Asia Pacific (Melbourne) ap-southeast-4',
    AwsRegionEnum::AsiaPacificMumbai    => 'Asia Pacific (Mumbai) ap-south-1',
    AwsRegionEnum::AsiaPacificOsaka     => 'Asia Pacific (Osaka) ap-northeast-3',
    AwsRegionEnum::AsiaPacificSeoul     => 'Asia Pacific (Seoul) ap-norhteast-2',
    AwsRegionEnum::AsiaPacificSingapore => 'Asia Pacific (Singapore) ap-southeast-1',
    AwsRegionEnum::AsiaPacificSydney    => 'Asia Pacific (Sydney) ap-southeast-2',
    AwsRegionEnum::AsiaPacificTokyo     => 'Asia Pacific (Tokyo) ap-northeast-1',
    AwsRegionEnum::CanadaCalgary        => 'Canada (Calgary) ca-west-1',
    AwsRegionEnum::CanadaCentral        => 'Canada (Central) ca-central-1',
    AwsRegionEnum::EuropeFrankfurt      => 'Europe (Frankfurt) eu-central-1',
    AwsRegionEnum::EuropeIreland        => 'Europe (Ireland) eu-west-1',
    AwsRegionEnum::EuropeLondon         => 'Europe (London) eu-west-2',
    AwsRegionEnum::EuropeMilan          => 'Europe (Milan) eu-south-1',
    AwsRegionEnum::EuropeParis          => 'Europe (Paris) eu-west-3',
    AwsRegionEnum::EuropeSpain          => 'Europe (Spain) eu-south-2',
    AwsRegionEnum::EuropeStockholm      => 'Europe (Stockholm) eu-north-1',
    AwsRegionEnum::EuropeZurich         => 'Europe (Zurich) eu-central-2',
    AwsRegionEnum::IsraelTelAviv        => 'Israel (Tel Aviv) il-central-1',
    AwsRegionEnum::MiddleEastBahrain    => 'Middle East (Bahrain) me-south-1',
    AwsRegionEnum::MiddleEastUAE        => 'Middle East (UAE) me-central-1',
    AwsRegionEnum::SouthAmericaSaoPaulo => 'South America (Sao Paulo) sa-east-1',
    AwsRegionEnum::USEastVirginia       => 'US East (N. Virginia) us-east-1',
    AwsRegionEnum::USEastOhio           => 'US East (Ohio) us-east-2',
    AwsRegionEnum::USWestCalifornia     => 'US West (N. California) us-west-1',
    AwsRegionEnum::USWestOregon         => 'US West (Oregon) us-west-2'
  );
}
