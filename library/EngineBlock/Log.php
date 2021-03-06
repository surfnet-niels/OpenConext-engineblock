<?php
/**
 * SURFconext EngineBlock
 *
 * LICENSE
 *
 * Copyright 2011 SURFnet bv, The Netherlands
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and limitations under the License.
 *
 * @category  SURFconext EngineBlock
 * @package
 * @copyright Copyright © 2010-2011 SURFnet SURFnet bv, The Netherlands (http://www.surfnet.nl)
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */

class EngineBlock_Log extends Zend_Log
{
    /**
     * Factory to construct the logger and one or more writers
     * based on the configuration array
     *
     * @param array $config
     *
     * @internal param array|\Zend_Config $Array or instance of Zend_Config
     * @return Zend_Log
     */
    static public function factory($config = array())
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }

        if (!is_array($config) || empty($config)) {
            /** @see Zend_Log_Exception */
            require_once 'Zend/Log/Exception.php';
            throw new Zend_Log_Exception('Configuration must be an array or instance of Zend_Config');
        }

        $log = new EngineBlock_Log();

        if (!is_array(current($config))) {
            $log->addWriter(current($config));
        } else {
            foreach ($config as $writer) {
                $log->addWriter($writer);
            }
        }

        return $log;
    }

    /**
     * Prio 0: Emergency: system is unusable
     *
     * @param string $msg
     * @param EngineBlock_Log_Message_AdditionalInfo $additionalInfo
     * @return void
     */
    public function emerg($msg, $additionalInfo = null)
    {
        $this->_setAdditionalEventItems($additionalInfo);
        parent::emerg($msg);
    }

    /**
     * Prio 1: Alert: action must be taken immediately
     *
     * @param string $msg
     * @param EngineBlock_Log_Message_AdditionalInfo $additionalInfo
     * @return void
     */
    public function alert($msg, $additionalInfo = null)
    {
        $this->_setAdditionalEventItems($additionalInfo);
        parent::alert($msg);
    }

    /**
     * Prio 2: Critical: critical conditions
     *
     * @param string $msg
     * @param EngineBlock_Log_Message_AdditionalInfo $additionalInfo
     * @return void
     */
    public function critical($msg, $additionalInfo = null)
    {
        $this->_setAdditionalEventItems($additionalInfo);
        parent::crit($msg);
    }

    /**
     * Prio 3: Error: error conditions
     *
     * Alias for err
     *
     * @param string $msg
     * @param EngineBlock_Log_Message_AdditionalInfo $additionalInfo
     * @return void
     */
    public function error($msg, $additionalInfo = null)
    {
        $this->_setAdditionalEventItems($additionalInfo);
        $this->err($msg, $additionalInfo);
    }

    /**
     * Prio 3: Error: error conditions
     *
     * Has an alias called 'error'
     *
     * @param string $msg
     * @param EngineBlock_Log_Message_AdditionalInfo $additionalInfo
     * @return void
     */
    public function err($msg, $additionalInfo = null)
    {
        $this->_setAdditionalEventItems($additionalInfo);
        parent::err($msg);
    }

    /**
     * Prio 4: Warning: warning conditions
     *
     * @param string $msg
     * @param EngineBlock_Log_Message_AdditionalInfo $additionalInfo
     * @return void
     */
    public function warn($msg, $additionalInfo = null)
    {
        $this->_setAdditionalEventItems($additionalInfo);
        parent::warn($msg);
    }

    /**
     * Prio 5: Notice: normal but significant condition
     *
     * @param string $msg
     * @param EngineBlock_Log_Message_AdditionalInfo $additionalInfo
     * @return void
     */
    public function notice($msg, $additionalInfo = null)
    {
        $this->_setAdditionalEventItems($additionalInfo);
        parent::notice($msg);
    }

    /**
     * Prio 6: Informational: informational messages
     *
     * @param string $msg
     * @param EngineBlock_Log_Message_AdditionalInfo $additionalInfo
     * @return void
     */
    public function info($msg, $additionalInfo = null)
    {
        $this->_setAdditionalEventItems($additionalInfo);
        parent::info($msg);
    }

    /**
     * Prio 7: Debug: debug messages
     *
     * @param string $msg
     * @param EngineBlock_Log_Message_AdditionalInfo $additionalInfo
     * @return void
     */
    public function debug($msg, $additionalInfo = null)
    {
        $this->_setAdditionalEventItems($additionalInfo);
        parent::debug($msg);
    }

    protected function _setAdditionalEventItems(EngineBlock_Log_Message_AdditionalInfo $additionalInfo = null)
    {
        if ($additionalInfo) {
            $additionalEvents = $additionalInfo->toArray();
            foreach ($additionalEvents as $key => $value) {
                $this->setEventItem($key, $value);
            }
        }
    }
}