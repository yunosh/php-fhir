<?php

namespace DCarbone\PHPFHIR;

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use DCarbone\PHPFHIR\Config\AbstractConfig;
use Psr\Log\LoggerInterface;

if (80000 <= PHP_VERSION_ID) {
    /**
     * Class Config
     * @package DCarbone\PHPFHIR
     */
    class Config extends AbstractConfig
    {
        /**
         * @param \Psr\Log\LoggerInterface $logger
         */
        public function setLogger(LoggerInterface $logger): void
        {
            $this->_log = new Logger($logger);
        }
    }
} else {
    /**
     * Class Config
     * @package DCarbone\PHPFHIR
     */
    class Config extends AbstractConfig
    {
        /**
         * @param \Psr\Log\LoggerInterface $logger
         */
        public function setLogger(LoggerInterface $logger)
        {
            $this->_log = new Logger($logger);
        }
    }
}