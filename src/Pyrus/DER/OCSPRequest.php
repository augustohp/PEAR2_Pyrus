<?php
/**
 * \pear2\Pyrus\DER\Schema
 *
 * PHP version 5
 *
 * @category  PEAR2
 * @package   PEAR2_Pyrus
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 2008 The PEAR Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   SVN: $Id$
 * @link      http://svn.pear.php.net/wsvn/PEARSVN/Pyrus/
 */

/**
 * Represents a Distinguished Encoding Rule IASN.1 schema
 *
 * This is used to name components and to retrieve context-specific types
 * 
 * @category  PEAR2
 * @package   PEAR2_Pyrus
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 2008 The PEAR Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://svn.pear.php.net/wsvn/PEARSVN/Pyrus/
 */
namespace pear2\Pyrus\DER;
class OCSPRequest extends \pear2\Pyrus\DER
{
    protected function __construct($developerCert)
    {
        $this->setSchema($this->getSchema());
        $ocsprequest = $this->OCSPRequest->tbsRequest->requestListSeq->reqCert;
        $ocsprequest->hashAlgorithm->algorithm = '1.3.14.3.2.26'; // SHA-1
        $ocsprequest->hashAlgorithm->parameters = null;

        if (!file_exists($developerCert)) {
            throw new \pear2\Pyrus\DER\Exception('Developer certificate ' . $developerCert . ' does not exist');
        }
        $info = openssl_x509_parse(file_get_contents($developerCert));
        if (!isset($info['serialNumber']) || !isset($info['issuer']) || !isset($info['issuer']['OU'])) {
            throw new \pear2\Pyrus\DER\Exception('Cannot process developer Certificate ' . $developerCert .
                                                ', missing key fields');
        }
        if ($info['issuer']['OU'] != 'http://www.cacert.org') {
            // other issuers are picky about who they allow to verify,
            // so we only accept certs from cacert
            throw new \pear2\Pyrus\DER\Exception('Cannot verify certificate, ' .
                                                'it is not from cacert.org');
        }
        $ocsprequest->issuerNameHash =
            pack('C*', '8ba4c9cb172919453ebb8e730991b925f2832265');
        $ocsprequest->issuerKeyHash =
            pack('C*', '16b5321bd4c7f3e0e68ef3bdd2b03aeeb23918d1');
        $ocsprequest->serialNumber = $info['serialNumber'];

        $this->requestExtensions->Inner->Extension->extnID = '1.3.6.1.5.5.7.48.1.2'; // OCSP nonce
        $this->requestExtensions->Inner->Extension->extnValue = md5($info['serialNumber'] . time(), true);
        echo $this;
    }

    protected function getSchema()
    {
        if (isset($types['ocsprequest'])) {
            return $types['OCSPRequest'];
        }

        $types = \pear2\Pyrus\DER\Schema::types();

        if (!isset($types['anothername'])) {
            \pear2\Pyrus\DER\Schema::addType('AnotherName',
                \pear2\Pyrus\DER\Schema::factory()
                ->sequence('AnotherName')
                    ->objectIdentifier('type-id')
                    ->any('value', 0));
        }

        if (!isset($types['generalname'])) {
            \pear2\Pyrus\DER\Schema::addType('GeneralName',
                \pear2\Pyrus\DER\Schema::factory()
                ->choice('GeneralName')
                    ->option('otherName', 'AnotherName')
                    ->option('rfc822Name', 'IA5String')
                    ->option('dNSName', 'IA5String')
                    ->option('x400Address', 'AnotherName') // ORaddress, I'm lazy
                    ->option('dNSName', 'IA5String'));
        }

        if (!isset($types['algorithmidentifier'])) {
            \pear2\Pyrus\DER\Schema::addType('AlgorithmIdentifier',
                \pear2\Pyrus\DER\Schema::factory()
                ->sequence('AlgorithmIdentifier')
                    ->objectIdentifier('algorithm')
                    ->any('parameters'));
        }

        if (!isset($types['certid'])) {
            \pear2\Pyrus\DER\Schema::addType('CertID',
                \pear2\Pyrus\DER\Schema::factory()
                ->sequence('CertID')
                    ->algorithmIdentifier('hashAlgorithm')
                    ->octetString('issuerNameHash')
                    ->octetString('issuerKeyHash')
                    ->integer('serialNumber'));
        }

        if (!isset($types['extensions'])) {
            \pear2\Pyrus\DER\Schema::addType('Extensions',
                $extensions = \pear2\Pyrus\DER\Schema::factory()
                ->sequence('Extensions')
                    ->sequence('Inner')
                        ->sequence('Extension')->setMultiple()
                            ->objectIdentifier('extnID')
                            ->boolean('critical')
                            ->octetString('extnValue')
                        ->end()
                    ->end());
            $extensions->Inner->Extension->critical->setOptional();
        }

        if (!isset($types['request'])) {
            \pear2\Pyrus\DER\Schema::addType('Request',
                $request = \pear2\Pyrus\DER\Schema::factory()
                ->sequence('Request')
                    ->certID('reqCert')
                    ->extensions('singleRequestExtensions', 0));
            $request->singleRequestExtensions->setOptional();
        }

        if (!isset($types['tbsrequest'])) {
            \pear2\Pyrus\DER\Schema::addType('TBSRequest',
                $tbs = \pear2\Pyrus\DER\Schema::factory()
                ->sequence('TBSRequest')
                    ->integer('version', 0)
                    ->generalName('requestorName', 1)
                    ->sequence('requestListSeq')
                        ->request('requestList')
                    ->end()
                    ->extensions('requestExtensions', 2)
                );
            $tbs->version->setOptional();
            $tbs->requestorName->setOptional();
            $tbs->test->requestList->setMultiple();
            $tbs->requestExtensions->setOptional();
        }

        $schema = new \pear2\Pyrus\DER\Schema;
        $schema
                ->sequence('OCSPRequest')
                    ->TBSRequest('tbsRequest');
        \pear2\Pyrus\DER\Schema::addType('OCSPRequest', $schema);
        return $schema;
    }
}
