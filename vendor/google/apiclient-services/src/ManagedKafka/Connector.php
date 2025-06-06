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

namespace Google\Service\ManagedKafka;

class Connector extends \Google\Model
{
  /**
   * @var string[]
   */
  public $configs;
  /**
   * @var string
   */
  public $name;
  /**
   * @var string
   */
  public $state;
  protected $taskRestartPolicyType = TaskRetryPolicy::class;
  protected $taskRestartPolicyDataType = '';

  /**
   * @param string[]
   */
  public function setConfigs($configs)
  {
    $this->configs = $configs;
  }
  /**
   * @return string[]
   */
  public function getConfigs()
  {
    return $this->configs;
  }
  /**
   * @param string
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * @param string
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return string
   */
  public function getState()
  {
    return $this->state;
  }
  /**
   * @param TaskRetryPolicy
   */
  public function setTaskRestartPolicy(TaskRetryPolicy $taskRestartPolicy)
  {
    $this->taskRestartPolicy = $taskRestartPolicy;
  }
  /**
   * @return TaskRetryPolicy
   */
  public function getTaskRestartPolicy()
  {
    return $this->taskRestartPolicy;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Connector::class, 'Google_Service_ManagedKafka_Connector');
