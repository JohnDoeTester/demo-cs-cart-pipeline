<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

namespace Tygh\Backend\Cdn;

use Aws\CloudFront\CloudFrontClient;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;

class Cloudfront extends ABackend
{
    /**
     * @var CloudFrontClient
     */
    private $_cf;

    /**
     * Creates distribution
     * @param  string $host    host name for origin pull requests
     * @param  array  $options connection/authentication options
     * @return mixed  array with Id, host and status when success, boolean false otherwise
     */
    public function createDistribution($host, $options = array())
    {
        $origin_url = $host;
        $caller_reference = $origin_url . uniqid();

        $cacheBehavior = [
            'AllowedMethods' => [
                'CachedMethods' => [
                    'Items' => ['HEAD', 'GET'],
                    'Quantity' => 2,
                ],
                'Items' => ['HEAD', 'GET'],
                'Quantity' => 2,
            ],
            'Compress' => false,
            'DefaultTTL' => 86400,
            'FieldLevelEncryptionId' => '',
            'ForwardedValues' => [
                'Cookies' => [
                    'Forward' => 'none',
                ],
                'Headers' => [
                    'Quantity' => 0,
                ],
                'QueryString' => false,
                'QueryStringCacheKeys' => [
                    'Quantity' => 0,
                ],
            ],
            'LambdaFunctionAssociations' => ['Quantity' => 0],
            'MaxTTL' => 31536000,
            'MinTTL' => 0,
            'SmoothStreaming' => false,
            'TargetOriginId' => $origin_url,
            'TrustedSigners' => [
                'Enabled' => false,
                'Quantity' => 0,
            ],
            'ViewerProtocolPolicy' => 'allow-all',
        ];

        $origin = [
            'Items' => [
                [
                    'DomainName' => $origin_url,
                    'Id' => $origin_url,
                    'OriginPath' => '',
                    'CustomHeaders' => ['Quantity' => 0],
                    'CustomOriginConfig' => [
                        'HTTPPort' => 80,
                        'HTTPSPort' => 443,
                        'OriginProtocolPolicy' => 'http-only'
                    ]
                ],
            ],
            'Quantity' => 1,
        ];

        $distribution = [
            'CallerReference' => $caller_reference,
            'Comment' => '',
            'DefaultCacheBehavior' => $cacheBehavior,
            'Enabled' => true,
            'Origins' => $origin,
        ];

        try {
            $create_result = $this->_cf($options)->createDistribution([
                'DistributionConfig' => $distribution,
            ]);
        } catch (AwsException $e) {
            fn_set_notification('E', __('error'), (string) $e->getMessage());
        }

        if (!empty($create_result)) {
            return [
                'host' => $create_result['Distribution']['DomainName'],
                'id' => $create_result['Distribution']['Id'],
                'is_active' => $this->isDeployed($create_result['Distribution']['Status'])
            ];
        }

    }

    /**
     * Updates distribution
     * @param  string $host    host name for origin pull requests
     * @param  array  $options connection/authentication options
     * @return mixed  array with Id, host and status when new distribution is created or status when update distribution, boolean false on error
     */
    public function updateDistribution($host, $options)
    {
        if ($this->getOption('key') !== $options['key'] || $this->getOption('secret') !== $options['secret']) {
            $this->disableDistribution();

            return $this->createDistribution($host, $options);
        }

        if ($this->isActive()) {
            return [
                'is_enabled' => $options['is_enabled']
            ];
        }

        return false;
    }

    /**
     * Disables distribution
     * @return boolean true on success, false otherwise
     */
    public function disableDistribution()
    {
        return $this->updateConfig([
            'Enabled' => false
        ]);
    }

    /**
     * Checks if CDN active
     * @return boolean true if active, false - otherwise
     */
    public function isActive()
    {
        try {
            $distribution_result = $this->_cf()->getDistribution([
                'Id' => $this->getOption('id')
            ]);
        } catch (AwsException $e) {
            fn_set_notification('E', __('error'), (string) $e->getMessage());
        }

        if (!empty($distribution_result)) {
            return $this->isDeployed($distribution_result['Distribution']['Status']);
        }

        return false;
    }

    /**
     * Updates distribution config
     * @param  array   $data data to update
     * @return boolean true on success, false otherwise
     */
    private function updateConfig($data)
    {
        try {
            $distribution_result = $this->_cf()->getDistribution([
                'Id' => $this->getOption('id')
            ]);
        } catch (AwsException $e) {
            fn_set_notification('E', __('error'), (string) $e->getMessage());
        }

        if (!empty($distribution_result)) {

            $current_config = $distribution_result['Distribution']['DistributionConfig'];
            $e_tag = $distribution_result['ETag'];
            $enabled = $data['Enabled'];

            $distribution = [
                'CallerReference' => $current_config['CallerReference'], // REQUIRED
                'Comment' => $current_config['Comment'], // REQUIRED
                'DefaultCacheBehavior' => $current_config['DefaultCacheBehavior'], // REQUIRED
                'DefaultRootObject' => $current_config['DefaultRootObject'],
                'Enabled' => $enabled, // REQUIRED
                'Origins' => $current_config['Origins'], // REQUIRED
                'Aliases' => $current_config['Aliases'],
                'CustomErrorResponses' => $current_config['CustomErrorResponses'],
                'HttpVersion' => $current_config['HttpVersion'],
                'CacheBehaviors' => $current_config['CacheBehaviors'],
                'Logging' => $current_config['Logging'],
                'PriceClass' => $current_config['PriceClass'],
                'Restrictions' => $current_config['Restrictions'],
                'ViewerCertificate' => $current_config['ViewerCertificate'],
                'WebACLId' => $current_config['WebACLId'],
            ];

            try {
                $update_result = $this->_cf()->updateDistribution([
                    'DistributionConfig' => $distribution,
                    'Id' => $distribution_result['Distribution']['Id'],
                    'IfMatch' => $e_tag
                ]);
            } catch (AwsException $e) {
                fn_set_notification('E', __('error'), (string)$e->getMessage());
            }

            if (!empty($update_result)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets status from response and checks if distribution is deployed
     * @param  string $result
     * @return boolean true on status is deployed, false otherwise
     */
    private function isDeployed($result)
    {
        return (string) $result === 'Deployed';
    }

    /**
     * Gets CloudFront object
     *
     * @param  array     $options connection options
     * @return CloudFrontClient CloudFront object
     */
    private function _cf($options = array())
    {
        if (empty($this->_cf) || !empty($options)) {

            $key = !empty($options['key']) ? $options['key'] : $this->getOption('key');
            $secret = !empty($options['secret']) ? $options['secret'] : $this->getOption('secret');

            $credentials = new Credentials($key, $secret);

            $this->_cf = new CloudFrontClient([
                'region' => 'eu-west-1',
                'version' => 'latest',
                'credentials' => $credentials
            ]);
        }

        return $this->_cf;
    }
}
