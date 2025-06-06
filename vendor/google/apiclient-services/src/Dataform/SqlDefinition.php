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

namespace Google\Service\Dataform;

class SqlDefinition extends \Google\Model
{
  protected $errorTableType = ErrorTable::class;
  protected $errorTableDataType = '';
  protected $loadType = LoadConfig::class;
  protected $loadDataType = '';
  /**
   * @var string
   */
  public $query;

  /**
   * @param ErrorTable
   */
  public function setErrorTable(ErrorTable $errorTable)
  {
    $this->errorTable = $errorTable;
  }
  /**
   * @return ErrorTable
   */
  public function getErrorTable()
  {
    return $this->errorTable;
  }
  /**
   * @param LoadConfig
   */
  public function setLoad(LoadConfig $load)
  {
    $this->load = $load;
  }
  /**
   * @return LoadConfig
   */
  public function getLoad()
  {
    return $this->load;
  }
  /**
   * @param string
   */
  public function setQuery($query)
  {
    $this->query = $query;
  }
  /**
   * @return string
   */
  public function getQuery()
  {
    return $this->query;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SqlDefinition::class, 'Google_Service_Dataform_SqlDefinition');
