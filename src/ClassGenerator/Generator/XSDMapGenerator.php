<?php namespace DCarbone\PHPFHIR\ClassGenerator\Generator;

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\ClassGenerator\Config;
use DCarbone\PHPFHIR\ClassGenerator\Enum\ElementTypeEnum;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\ClassTypeUtils;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NameUtils;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NSUtils;
use DCarbone\PHPFHIR\ClassGenerator\XSDMap;

/**
 * Class XSDMapGenerator
 * @package DCarbone\PHPFHIR\ClassGenerator\Generator
 */
abstract class XSDMapGenerator {
    /**
     * @param \DCarbone\PHPFHIR\ClassGenerator\Config $config
     * @return \DCarbone\PHPFHIR\ClassGenerator\XSDMap
     */
    public static function buildXSDMap(Config $config) {
        $config->getLogger()->info('Creating in-memory representation of FHIR XSD\'s..');

        $xsdMap = new XSDMap();

        $fhirBaseXSD = sprintf('%s/fhir-base.xsd', $config->getXSDPath());

        if (!file_exists($fhirBaseXSD)) {
            $msg = sprintf(
                'Unable to locate "fhir-base.xsd" at expected path "%s".',
                $fhirBaseXSD
            );
            $config->getLogger()->critical($msg);
            throw new \RuntimeException($msg);
        }

        // First get class references in fhir-base.xsd
        self::parseClassesFromXSD($fhirBaseXSD, $xsdMap, $config);

        // Then scoop up the rest
        foreach (glob(sprintf('%s/*.xsd', $config->getXSDPath()), GLOB_NOSORT) as $xsdFile) {
            $basename = basename($xsdFile);

            if (0 === strpos($basename, 'fhir-')) {
                $config->getLogger()->debug(sprintf('Skipping "aggregate" file "%s"', $xsdFile));
                continue;
            }

            if ('xml.xsd' === $basename) {
                $config->getLogger()->debug(sprintf('Skipping file "%s"', $xsdFile));
                continue;
            }

            self::parseClassesFromXSD($xsdFile, $xsdMap, $config);
        }

        return $xsdMap;
    }

    /**
     * @param string                                  $file
     * @param \DCarbone\PHPFHIR\ClassGenerator\XSDMap $xsdMap
     * @param \DCarbone\PHPFHIR\ClassGenerator\Config $config
     */
    public static function parseClassesFromXSD($file, XSDMap $xsdMap, Config $config) {
        $config->getLogger()->debug(sprintf('Parsing classes from file "%s"...', $file));

        $sxe = self::constructSXEWithFilePath($file, $config);
        foreach ($sxe->children('xs', true) as $child) {
            /** @var \SimpleXMLElement $child */
            $attributes = $child->attributes();
            $fhirElementName = (string)$attributes['name'];

            if ('' === $fhirElementName) {
                $attrArray = [];
                foreach ($attributes as $attribute) {
                    /** @var \SimpleXMLElement $attribute */
                    $attrArray[] = sprintf('%s : %s', $attribute->getName(), (string)$attribute);
                }
                $config->getLogger()
                    ->debug(sprintf('Unable to locate "name" attribute on element in file "%s" with attributes ["%s"]',
                        $file,
                        implode('", "', $attrArray)));
                continue;
            }

            if (ElementTypeEnum::COMPLEX_TYPE === strtolower($child->getName())) {
                $type = ClassTypeUtils::getComplexClassType($child);

                $xsdMap[$fhirElementName] = new XSDMap\XSDMapEntry(
                    $child,
                    $fhirElementName,
                    NSUtils::generateRootNamespace(
                        $config,
                        NSUtils::getComplexTypeNamespace($fhirElementName, $type)
                    ),
                    NameUtils::getComplexTypeClassName($fhirElementName)
                );

                $config->getLogger()->info(sprintf(
                    'Located "%s" class "%s\\%s" in file "%s"',
                    $type,
                    $xsdMap[$fhirElementName]->getNamespace(),
                    $xsdMap[$fhirElementName]->getClassName(),
                    basename($file)
                ));
            }
        }
    }

    /**
     * @param string                                  $filePath
     * @param \DCarbone\PHPFHIR\ClassGenerator\Config $config
     * @return \SimpleXMLElement
     */
    public static function constructSXEWithFilePath($filePath, Config $config) {
        $config->getLogger()->debug(sprintf('Parsing classes from file "%s"...', $filePath));

        $filename = basename($filePath);

        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $sxe = new \SimpleXMLElement(file_get_contents($filePath), LIBXML_COMPACT | LIBXML_NSCLEAN);
        libxml_use_internal_errors(false);

        if ($sxe instanceof \SimpleXMLElement) {
            $sxe->registerXPathNamespace('xs', 'http://www.w3.org/2001/XMLSchema');
            $sxe->registerXPathNamespace('', 'http://hl7.org/fhir');
            return $sxe;
        }

        $error = libxml_get_last_error();
        if ($error) {
            $msg = sprintf(
                'Error occurred while parsing file "%s": "%s"',
                $filename,
                $error->message
            );
            $config->getLogger()->critical($msg);
            throw new \RuntimeException($msg);
        }

        $msg = sprintf(
            'Unknown XML parsing error occurred while parsing "%s".',
            $filename);
        $config->getLogger()->critical($msg);
        throw new \RuntimeException($msg);
    }
}
