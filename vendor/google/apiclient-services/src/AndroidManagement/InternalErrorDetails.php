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

namespace Google\Service\AndroidManagement;

class InternalErrorDetails extends \Google\Model
{
  /**
   * @var string
   */
  public $errorCode;
  /**
   * @var string
   */
  public $errorCodeDetail;
  /**
   * @var string
   */
  public $operationCode;
  /**
   * @var string
   */
  public $operationCodeDetail;

  /**
   * @param string
   */
  public function setErrorCode($errorCode)
  {
    $this->errorCode = $errorCode;
  }
  /**
   * @return string
   */
  public function getErrorCode()
  {
    return $this->errorCode;
  }
  /**
   * @param string
   */
  public function setErrorCodeDetail($errorCodeDetail)
  {
    $this->errorCodeDetail = $errorCodeDetail;
  }
  /**
   * @return string
   */
  public function getErrorCodeDetail()
  {
    return $this->errorCodeDetail;
  }
  /**
   * @param string
   */
  public function setOperationCode($operationCode)
  {
    $this->operationCode = $operationCode;
  }
  /**
   * @return string
   */
  public function getOperationCode()
  {
    return $this->operationCode;
  }
  /**
   * @param string
   */
  public function setOperationCodeDetail($operationCodeDetail)
  {
    $this->operationCodeDetail = $operationCodeDetail;
  }
  /**
   * @return string
   */
  public function getOperationCodeDetail()
  {
    return $this->operationCodeDetail;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(InternalErrorDetails::class, 'Google_Service_AndroidManagement_InternalErrorDetails');
