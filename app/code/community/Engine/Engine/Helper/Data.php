<?php
/**
 * E-ngine Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com and you will be sent a copy immediately.
 *
 * @category   Engine
 * @package    Engine_Engine
 * @author     Michiel van de Vis
 * @copyright  Copyright (c) 2014 Engine BV (www.e-ngine.nl)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Engine_Engine_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @const CACHE_TAG General cache tag
     */
    const CACHE_TAG = 'ENGINE_CACHE';
    const CACHE_ID = 'engine_template';
    
    private $_cache = null;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->_isEnabled = Mage::app()->useCache(self::CACHE_ID);
    }

    /**
     * Check if E-ngine cache is enabled
     *
     * @return bool
     */
    public function isCacheEnabled() {
        return (bool) $this->_isEnabled;
    }

    /**
     * Save data to cache
     *
     * @param string $data Data to be cached
     * @param string $cacheId
     * @return bool
     */
    public function saveCacheData($data, $cacheRecordID) {
        if (!$this->isCacheEnabled()) {
            return false;
        }
        
        if (!isset($this->_cache[$cacheRecordID]) || $this->_cache[$cacheRecordID] != $data) {
            if (!is_array($this->_cache)) {
                $this->_cache = array();
            }
            $this->_cache[$cacheRecordID] = $data;

            $result = Mage::app()->saveCache(json_encode($this->_cache), self::CACHE_ID, array(self::CACHE_TAG));
            return $result;
        }
        
        return false;
    }

    /**
     * Retrieve data from Cache
     *
     * @param string $cacheRecordID Cache ID
     * @return mixed Cache data
     */
    public function loadCacheData($cacheRecordID) {
        if (!$this->isCacheEnabled()) {
            return false;
        }

        if (is_null($this->_cache)) {
            $cacheData = Mage::app()->loadCache(self::CACHE_ID);
            $this->_cache = json_decode($cacheData, true);
        }
        
        if (isset($this->_cache[$cacheRecordID])) {
            return $this->_cache[$cacheRecordID];
        }
        return false;
    }

    /**
     * Remove data from Cache
     *
     * @return int
     */
    public function removeCacheData() {
        if (!$this->isCacheEnabled()) {
            return false;
        }

        Mage::app()->removeCache(self::CACHE_ID);

        return $this;
    }

    /**
     * Invalidate E-ngine cache
     *
     * @return void
     */
    public function invalidateCache() {
        Mage::app()->getCacheInstance()->invalidateType(self::CACHE_ID);
    }
    
    
}