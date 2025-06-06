<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace Google\Service\DiscoveryEngine;

class GoogleCloudDiscoveryengineV1alphaActionConfig extends \Google\Model
{
  /**
   * @var array[]
   */
  public $actionParams;
  /**
   * @var bool
   */
  public $isActionConfigured;
  /**
   * @var string
   */
  public $serviceName;

  /**
   * @param array[]
   */
  public function setActionParams($actionParams)
  {
    $this->actionParams = $actionParams;
  }
  /**
   * @return array[]
   */
  public function getActionParams()
  {
    return $this->actionParams;
  }
  /**
   * @param bool
   */
  public function setIsActionConfigured($isActionConfigured)
  {
    $this->isActionConfigured = $isActionConfigured;
  }
  /**
   * @return bool
   */
  public function getIsActionConfigured()
  {
    return $this->isActionConfigured;
  }
  /**
   * @param string
   */
  public function setServiceName($serviceName)
  {
    $this->serviceName = $serviceName;
  }
  /**
   * @return string
   */
  public function getServiceName()
  {
    return $this->serviceName;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1alphaActionConfig::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1alphaActionConfig');
