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

class ListTopicsResponse extends \Google\Collection
{
  protected $collection_key = 'topics';
  /**
   * @var string
   */
  public $nextPageToken;
  protected $topicsType = Topic::class;
  protected $topicsDataType = 'array';

  /**
   * @param string
   */
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  /**
   * @return string
   */
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  /**
   * @param Topic[]
   */
  public function setTopics($topics)
  {
    $this->topics = $topics;
  }
  /**
   * @return Topic[]
   */
  public function getTopics()
  {
    return $this->topics;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ListTopicsResponse::class, 'Google_Service_ManagedKafka_ListTopicsResponse');
