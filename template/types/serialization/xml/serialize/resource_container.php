<?php declare(strict_types=1);

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\NameUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

$localProperties = $type->getLocalProperties()->localPropertiesIterator();

ob_start(); ?>
    /**
     * @param null|\DOMElement $element
     * @param null|int $libxmlOpts
     * @return \DOMElement
     */
    public function xmlSerialize(null|\DOMElement $element = null, ?int $libxmlOpts = <?php echo  null === ($opts = $config->getLibxmlOpts()) ? 'null' : $opts; ?>): \DOMElement
    {
<?php foreach($localProperties as $property) : ?>
        if (null !== ($v = $this->get<?php echo $property->getGetterName(); ?>())) {
            return $v->xmlSerialize($element, $libxmlOpts);
        }
<?php endforeach; ?>
        if (null === $element) {
            $dom = new \DOMDocument();
            $dom->loadXML($this->_getFHIRXMLElementDefinition(<?php echo NameUtils::getTypeXMLElementName($type); ?>), $libxmlOpts);
            $element = $dom->documentElement;
        }
<?php
return ob_get_clean();