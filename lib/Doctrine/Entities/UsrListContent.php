<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Entities;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class UsrListContent
{

  /**
   * @var integer $id
   */
  private $id;

  /**
   * @var integer $usr_id
   */
  private $usr_id;

  /**
   * @var datetime $created
   */
  private $created;

  /**
   * @var datetime $updated
   */
  private $updated;

  /**
   * @var Entities\UsrList
   */
  private $list;

  /**
   * Get id
   *
   * @return integer 
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set usr_id
   *
   * @param integer $usrId
   */
  public function setUsrId($usrId)
  {
    $this->usr_id = $usrId;
  }

  /**
   * Get usr_id
   *
   * @return integer 
   */
  public function getUsrId()
  {
    return $this->usr_id;
  }

  /**
   * Set created
   *
   * @param datetime $created
   */
  public function setCreated($created)
  {
    $this->created = $created;
  }

  /**
   * Get created
   *
   * @return datetime 
   */
  public function getCreated()
  {
    return $this->created;
  }

  /**
   * Set updated
   *
   * @param datetime $updated
   */
  public function setUpdated($updated)
  {
    $this->updated = $updated;
  }

  /**
   * Get updated
   *
   * @return datetime 
   */
  public function getUpdated()
  {
    return $this->updated;
  }

  /**
   * Set list
   *
   * @param Entities\UsrList $list
   */
  public function setList(\Entities\UsrList $list)
  {
    $this->list = $list;
  }

  /**
   * Get list
   *
   * @return Entities\UsrList 
   */
  public function getList()
  {
    return $this->list;
  }

  public function getUser()
  {
    return \User_Adapter::getInstance($this->getUsrId(), \appbox::get_instance());
  }

}