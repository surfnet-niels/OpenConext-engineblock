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

class Authentication_Controller_Proxy extends EngineBlock_Controller_Abstract
{    
    /**
     * 
     *
     * @param string $encodedIdPEntityId
     * @return void
     */
    public function idPsMetaDataAction()
    {
        $this->setNoRender();

        $application = EngineBlock_ApplicationSingleton::getInstance();

        $queryString = EngineBlock_ApplicationSingleton::getInstance()->getHttpRequest()->getQueryString();
        $proxyServer = new EngineBlock_Corto_Adapter();
        try {
            $proxyServer->idPsMetadata($queryString);
        } catch(Corto_ProxyServer_UnknownRemoteEntityException $e) {
            $application->getLogInstance()->warn('Unknown SP entity id used in idpsMetadata: ' . $queryString);
            $application->getHttpResponse()->setRedirectUrl(
                '/authentication/feedback/unknown-service-provider?entity-id=' . urlencode($e->getEntityId())
            );
        }
    }

    public function processedAssertionAction()
    {
        $this->setNoRender();
        $application = EngineBlock_ApplicationSingleton::getInstance();
        try {
            $proxyServer = new EngineBlock_Corto_Adapter();
            $proxyServer->processedAssertionConsumer();
        }
        catch (EngineBlock_Corto_Exception_UserNotMember $e) {
            $application->getLogInstance()->warn('User not a member error');
            $application->getHttpResponse()->setRedirectUrl('/authentication/feedback/vomembershiprequired');
        }
    }
}
