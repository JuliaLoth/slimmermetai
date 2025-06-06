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

namespace Google\Service\AlertCenter;

class VaultAcceleratedDeletion extends \Google\Model
{
  /**
   * @var string
   */
  public $actionType;
  /**
   * @var string
   */
  public $appType;
  /**
   * @var string
   */
  public $createTime;
  /**
   * @var string
   */
  public $deletionRequestId;
  /**
   * @var string
   */
  public $matterId;

  /**
   * @param string
   */
  public function setActionType($actionType)
  {
    $this->actionType = $actionType;
  }
  /**
   * @return string
   */
  public function getActionType()
  {
    return $this->actionType;
  }
  /**
   * @param string
   */
  public function setAppType($appType)
  {
    $this->appType = $appType;
  }
  /**
   * @return string
   */
  public function getAppType()
  {
    return $this->appType;
  }
  /**
   * @param string
   */
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  /**
   * @return string
   */
  public function getCreateTime()
  {
    return $this->createTime;
  }
  /**
   * @param string
   */
  public function setDeletionRequestId($deletionRequestId)
  {
    $this->deletionRequestId = $deletionRequestId;
  }
  /**
   * @return string
   */
  public function getDeletionRequestId()
  {
    return $this->deletionRequestId;
  }
  /**
   * @param string
   */
  public function setMatterId($matterId)
  {
    $this->matterId = $matterId;
  }
  /**
   * @return string
   */
  public function getMatterId()
  {
    return $this->matterId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(VaultAcceleratedDeletion::class, 'Google_Service_AlertCenter_VaultAcceleratedDeletion');
