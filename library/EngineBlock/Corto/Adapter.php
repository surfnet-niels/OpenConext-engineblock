<?php

define('ENGINEBLOCK_FOLDER_LIBRARY_CORTO', ENGINEBLOCK_FOLDER_LIBRARY . 'Corto/library/');
require ENGINEBLOCK_FOLDER_LIBRARY_CORTO . 'Corto/ProxyServer.php';

spl_autoload_register(array('EngineBlock_Corto_Adapter', 'cortoAutoLoad'));

class EngineBlock_Corto_Adapter 
{
    const DEFAULT_HOSTED_ENTITY = 'main';

    const IDENTIFYING_MACE_ATTRIBUTE = 'urn:mace:dir:attribute-def:uid';

    protected $_collaborationAttributes = array();

    /**
     * @var EngineBlock_Corto_CoreProxy
     */
    protected $_proxyServer;
    
    /**
     * @var String The name of the currently hosted Corto hosted entity.
     */
    protected $_hostedEntity;
    
    /**
     * @var String the name of the Virtual Organisation context (if any)
     */
    protected $_voContext = NULL;
    
    public function __construct($hostedEntity = null) {
        
        if ($hostedEntity == null) {
            $hostedEntity = self::DEFAULT_HOSTED_ENTITY;
        }
        
        $this->_hostedEntity = $hostedEntity;
        
    }

    /**
     * Simple autoloader for Corto, tries to autoload all classes with Corto_ from the Corto/library folder.
     *
     * @static
     * @param string $className Class name to autoload
     * @return bool Whether autoloading succeeded
     */
    public static function cortoAutoLoad($className)
    {
        if (strpos($className, 'Corto_') !== 0) {
            return false;
        }

        $classParts = explode('_', $className);
        $filePath = implode('/', $classParts) . '.php';

        include ENGINEBLOCK_FOLDER_LIBRARY_CORTO . $filePath;

        return true;
    }

    public function singleSignOn($idPProviderHash)
    {
        $this->_callCortoServiceUri('singleSignOnService', $idPProviderHash);
    }

    public function idPMetadata()
    {
        $this->_callCortoServiceUri('idPMetadataService');
    }

    public function sPMetadata()
    {
        $this->_callCortoServiceUri('sPMetadataService');
    }

    public function consumeAssertion()
    {
        $this->_callCortoServiceUri('assertionConsumerService');
    }

    public function idPsMetadata()
    {
        $this->_callCortoServiceUri('idPsMetaDataService');
    }

    public function processWayf()
    {
        $this->_callCortoServiceUri('continueToIdp');
    }

    public function processConsent()
    {
        $this->_callCortoServiceUri('processConsentService');
    }

    public function processedAssertionConsumer()
    {
        $this->_callCortoServiceUri('processedAssertionConsumerService');
    }
    
    public function setVirtualOrganisationContext($virtualOrganisation)
    {
        $this->_voContext = $virtualOrganisation;
    }

    protected function _callCortoServiceUri($serviceName, $idPProviderHash = "")
    {
        $cortoUri = $this->_getCortoUri($serviceName, $idPProviderHash);

        $this->_initProxy();

        $this->_proxyServer->serveRequest($cortoUri);
        $this->_processProxyServerResponse();

        unset($this->_proxyServer);
    }

    protected function _getCortoUri($cortoServiceName, $idPProviderHash = "")
    {
        $cortoHostedEntity  = $this->_getHostedEntity();
        $cortoIdPHash       = $idPProviderHash;
        $result =  '/' . $cortoHostedEntity . ($cortoIdPHash ? '_' . $cortoIdPHash : '') . '/' . $cortoServiceName;
        
        return $result;
        
    }

    protected function _initProxy()
    {
        if (isset($this->_proxyServer)) {
            return true;
        }

        $proxyServer = $this->_getCoreProxy();

        $this->_configureProxyServer($proxyServer);

        $this->_proxyServer = $proxyServer;
    }

    protected function _getCoreProxy()
    {
        return new EngineBlock_Corto_CoreProxy();
    }

    protected function _configureProxyServer(Corto_ProxyServer $proxyServer)
    {
        
        $application = EngineBlock_ApplicationSingleton::getInstance();
        
        if ($this->_voContext!=null) {
            $proxyServer->setVirtualOrganisationContext($this->_voContext);
        }
        

        $proxyServer->setConfigs(array(
            'debug' => $application->getConfigurationValue('debug', false),
            'trace' => $application->getConfigurationValue('debug', false),
        ));

        $attributes = array();
        require ENGINEBLOCK_FOLDER_LIBRARY_CORTO . '../configs/attributes.inc.php';
        $proxyServer->setAttributeMetadata($attributes);

        $proxyServer->setHostedEntities(array(
            $proxyServer->getHostedEntityUrl($this->_hostedEntity) => array(
                'certificates' => array(
                    'public'    => $application->getConfiguration()->encryption->key->public,
                    'private'   => $application->getConfiguration()->encryption->key->private,
                ),
                'infilter'  => array($this, 'filterInputAttributes'),
                //'outfilter' => array($this, 'filterOutputAttributes'),
                'Processing' => array(
                    'Consent' => array(
                        'Binding'  => 'INTERNAL',
                        'Location' => $proxyServer->getHostedEntityUrl($this->_hostedEntity, 'provideConsentService'),
                    ),
                ),
                'keepsession' => true,
            ),
        ));

        $proxyServer->setRemoteEntities($this->_getRemoteEntities() + array(
            $proxyServer->getHostedEntityUrl($this->_hostedEntity, 'idPMetadataService') => array(
                'certificates' => array(
                    'public'    => $application->getConfiguration()->encryption->key->public,
                    'private'   => $application->getConfiguration()->encryption->key->private,
                ),
            )
        ));

        $proxyServer->setTemplateSource(
            Corto_ProxyServer::TEMPLATE_SOURCE_FILESYSTEM,
            array('FilePath'=>ENGINEBLOCK_FOLDER_MODULES . 'Authentication/View/Proxy/')
        );

        $proxyServer->setSessionLogDefault(new Corto_Log_File('/tmp/corto_session'));
        
        $proxyServer->setBindingsModule(new EngineBlock_Corto_Module_Bindings($proxyServer));
        $proxyServer->setServicesModule(new EngineBlock_Corto_Module_Services($proxyServer));
    }

    /**
     * Called by Corto whenever it receives an Assertion with attributes from an Identity Provider
     *
     * @param  $entityMetaData
     * @param  $response
     * @param  $responseAttributes
     * @return void
     */
    public function filterInputAttributes(&$response, &$responseAttributes, $request, $spEntityMetadata, $idpEntityMetadata)
    {
        // In filter stage we need to take a look at the VO context      
        if (isset($request['__'][EngineBlock_Corto_CoreProxy::VO_CONTEXT_KEY])) {
            $vo = $request['__'][EngineBlock_Corto_CoreProxy::VO_CONTEXT_KEY];
            $this->setVirtualOrganisationContext($vo);
        }
        
        $responseAttributes = $this->_enrichAttributes($responseAttributes);

        $subjectId = $this->_provisionUser($responseAttributes, $idpEntityMetadata);
        $response['saml:Assertion']['saml:Subject']['saml:NameID']['_Format'] = 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent';
        $response['saml:Assertion']['saml:Subject']['saml:NameID']['__v'] = $subjectId;
    }

    /**
     * Enrich the attributes with attributes
     *
     * @param  $attributes
     * @return array
     */
    protected function _enrichAttributes(array $attributes)
    {
        $aggregator = $this->_getAttributeAggregator(
            $this->_getAttributeProviders()
        );
        $aggregatedAttributes = $aggregator->getAttributes(
            $attributes[self::IDENTIFYING_MACE_ATTRIBUTE][0]
        );
        return array_merge_recursive($attributes, $aggregatedAttributes);
    }

    protected function _provisionUser(array $attributes, $idpEntityMetadata)
    {
        return $this->_getProvisioning()->provisionUser($attributes, $idpEntityMetadata);
    }

    protected function _getRemoteEntities()
    {
        $serviceRegistry = new EngineBlock_Corto_ServiceRegistry_Adapter(
            new EngineBlock_ServiceRegistry_CacheProxy()
        );
        $metadata = $serviceRegistry->getRemoteMetaData();
        return $metadata;
    }

    protected function _processProxyServerResponse()
    {
        $response = EngineBlock_ApplicationSingleton::getInstance()->getHttpResponse();

        $this->_processProxyServerResponseHeaders($response);
        $this->_processProxyServerResponseBody($response);
    }

    protected function _processProxyServerResponseHeaders(EngineBlock_Http_Response $response)
    {
        $proxyHeaders = $this->_proxyServer->getHeaders();
        foreach ($proxyHeaders as $headerName => $headerValue) {
            if ($headerName === EngineBlock_Http_Response::HTTP_HEADER_RESPONSE_LOCATION) {
                $response->setRedirectUrl($headerValue);
            }
            else {
                $response->setHeader($headerName, $headerValue);
            }
        }
    }

    protected function _processProxyServerResponseBody(EngineBlock_Http_Response $response)
    {
        $proxyOutput = $this->_proxyServer->getOutput();
        $response->setBody($proxyOutput);
    }

    protected function _getAttributeProviders()
    {
        return array(new EngineBlock_AttributeProvider_Dummy());
    }

    protected function _getAttributeAggregator($providers)
    {
        return new EngineBlock_AttributeAggregator($providers);
    }

    protected function _getProvisioning()
    {
        return new EngineBlock_Provisioning();
    }
    
    protected function _getHostedEntity()
    {
        return $this->_hostedEntity;
    }
}
