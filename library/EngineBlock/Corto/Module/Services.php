<?php
 
class EngineBlock_Corto_Module_Services extends Corto_Module_Services
{
    public function idPsMetadataService()
    {
        $entitiesDescriptor = array(
            '_xmlns:md' => 'urn:oasis:names:tc:SAML:2.0:metadata',
            'md:EntityDescriptor' => array()
        );
        foreach ($this->_server->getRemoteEntities() as $entityID => $entity) {
            if (!isset($entity['SingleSignOnService'])) continue;

            $entityDescriptor = array(
                '_validUntil' => $this->_server->timeStamp(strtotime('tomorrow') - time()),
                '_entityID' => $entityID,
                'md:IDPSSODescriptor' => array(
                    '_protocolSupportEnumeration' => "urn:oasis:names:tc:SAML:2.0:protocol",
                    'md:NameIDFormat' => array('__v' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient'),
                    'md:SingleSignOnService' => array(
                        '_Binding' => self::DEFAULT_REQUEST_BINDING,
                        '_Location' => $this->_server->getCurrentEntityUrl('singleSignOnService', $entityID),
                    ),
                ),
            );

            if (isset($entity['certificates']['public'])) {
                $entityDescriptor['md:IDPSSODescriptor']['md:KeyDescriptor'] = array(
                    array(
                        '_xmlns:ds' => 'http://www.w3.org/2000/09/xmldsig#',
                        '_use' => 'signing',
                        'ds:KeyInfo' => array(
                            'ds:X509Data' => array(
                                'ds:X509Certificate' => array(
                                    '__v' => $entity['certificates']['public'],
                                ),
                            ),
                        ),
                    ),
                    array(
                        '_xmlns:ds' => 'http://www.w3.org/2000/09/xmldsig#',
                        '_use' => 'encryption',
                        'ds:KeyInfo' => array(
                            'ds:X509Data' => array(
                                'ds:X509Certificate' => array(
                                    '__v' => $entity['certificates']['public'],
                                ),
                            ),
                        ),
                    ),
                );
            }

            $entitiesDescriptor['md:EntityDescriptor'][] = $entityDescriptor;
        }

        $request = EngineBlock_ApplicationSingleton::getInstance()->getHttpRequest();
        $spEntityId = urldecode($request->getQueryParameter('sp-entity-id'));
        if ($spEntityId) {
            $entityDescriptor = $this->_getSpEntityDescriptor($spEntityId);
            if ($entityDescriptor) {
                $entitiesDescriptor['md:EntityDescriptor'][] = $entityDescriptor;
            }
        }

        $xml = Corto_XmlToArray::array2xml($entitiesDescriptor, 'md:EntitiesDescriptor', true);
        if ($this->_server->getConfig('debug', false)) {
            $dom = new DOMDocument();
            $dom->loadXML($xml);
            if (!$dom->schemaValidate('http://docs.oasis-open.org/security/saml/v2.0/saml-schema-metadata-2.0.xsd')) {
                echo '<pre>'.htmlentities(Corto_XmlToArray::formatXml($xml)).'</pre>';
                throw new Exception('Metadata XML doesnt validate against XSD at Oasis-open.org?!');
            }
        }
        header('Content-Type: application/xml');
        //header('Content-Type: application/samlmetadata+xml');
        print $xml;
    }

    protected function _getSpEntityDescriptor($spEntityId)
    {
        $entity = $this->_server->getRemoteEntity($spEntityId);
        if (!$entity) {
            return false;
        }

        if (!isset($entity['AssertionConsumerService'])) {
            return false;
        }

        $entityDescriptor = array(
            '_validUntil' => $this->_server->timeStamp(strtotime('tomorrow') - time()),
            '_entityID' => $spEntityId,
            'md:SPSSODescriptor' => array(
                '_protocolSupportEnumeration' => "urn:oasis:names:tc:SAML:2.0:protocol",
                'md:NameIDFormat' => array('__v' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient'),
                'md:AssertionConsumerService' => array(
                    '_Binding' => self::DEFAULT_RESPONSE_BINDING,
                    '_Location' => $this->_server->getCurrentEntityUrl('assertionConsumerService', $spEntityId),
                    '_index' => '1',
                ),
            ),
        );

        if (isset($entity['certificates']['public'])) {
            $entityDescriptor['md:IDPSSODescriptor']['md:KeyDescriptor'] = array(
                array(
                    '_xmlns:ds' => 'http://www.w3.org/2000/09/xmldsig#',
                    '_use' => 'signing',
                    'ds:KeyInfo' => array(
                        'ds:X509Data' => array(
                            'ds:X509Certificate' => array(
                                '__v' => $entity['certificates']['public'],
                            ),
                        ),
                    ),
                ),
                array(
                    '_xmlns:ds' => 'http://www.w3.org/2000/09/xmldsig#',
                    '_use' => 'encryption',
                    'ds:KeyInfo' => array(
                        'ds:X509Data' => array(
                            'ds:X509Certificate' => array(
                                '__v' => $entity['certificates']['public'],
                            ),
                        ),
                    ),
                ),
            );
        }

        return $entityDescriptor;
    }
}