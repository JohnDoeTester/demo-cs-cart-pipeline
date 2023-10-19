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

namespace Tygh\Addons\TildaApi;


use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\SiteArea;
use Tygh\Http;

class TildaClient
{
    const MAX_ATTEMPTS = 3;

    const DELAY = 1000000;

    /** @var string */
    protected $public_api_key;
    /** @var string */
    protected $secret_api_key;
    /** @var string */
    protected $project_id;

    /**
     * Construct function
     *
     * @param string $public_api_key Publick api key
     * @param string $secret_api_key Secret api key
     * @param string $project_id     Project id
     *
     * @return void
     */
    public function __construct($public_api_key, $secret_api_key, $project_id)
    {
        $this->public_api_key = $public_api_key;
        $this->secret_api_key = $secret_api_key;
        $this->project_id = $project_id;
    }

    /**
     * Gets a list of pages in a Tilda project
     *
     * @return array
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getPagesList()
    {
        if (empty($this->public_api_key) || empty($this->secret_api_key) || empty($this->project_id)) {
            fn_set_notification(
                NotificationSeverity::ERROR,
                __('error'),
                __(
                    'tilda_pages.settings_error',
                    [
                        '[url]'  => fn_url('addons.update&addon=tilda_pages', SiteArea::ADMIN_PANEL)
                    ]
                )
            );
        }

        $result = $this->makeRequest($this->getUrl('getpageslist'), [
            'projectid' => $this->project_id,
            'publickey' => $this->public_api_key,
            'secretkey' => $this->secret_api_key
        ]);

        return isset($result['result']) ? $result['result'] : [];
    }

    /**
     * Gets a list of pages in a Tilda project
     *
     * @return array
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getProjectsList()
    {
        if (empty($this->public_api_key) || empty($this->secret_api_key)) {
            return [];
        }

        $result = $this->makeRequest($this->getUrl('getprojectslist'), [
            'publickey' => $this->public_api_key,
            'secretkey' => $this->secret_api_key
        ]);

        return isset($result['result']) ? $result['result'] : [];
    }

    /**
     * Retrieves data about a Tilda page
     *
     * @param int $id Page id
     *
     * @return array Page data
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getExportedPageById($id)
    {
        $result = $this->makeRequest($this->getUrl('getpageexport'), [
            'pageid'    => $id,
            'publickey' => $this->public_api_key,
            'secretkey' => $this->secret_api_key
        ]);

        return isset($result['result']) ? $result['result'] : [];
    }

    /**
     * Retrieves data about a Tilda page
     *
     * @param int $id Page id
     *
     * @return array Page data
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getFullExportedPageById($id)
    {
        $result = $this->makeRequest($this->getUrl('getpagefullexport'), [
            'pageid'    => $id,
            'publickey' => $this->public_api_key,
            'secretkey' => $this->secret_api_key
        ]);

        return isset($result['result']) ? $result['result'] : [];
    }

    /**
     * Makes a request to the Tilda api
     *
     * @param string $url    Url for request
     * @param array  $data   Request params
     * @param string $method Request method
     *
     * @return bool|array Request result
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    protected function makeRequest($url, array $data, $method = 'get')
    {
        $attempt = 0;

        $logging = Http::$logging;
        Http::$logging = false;

        while ($attempt < self::MAX_ATTEMPTS) {
            if ($method === 'post') {
                $response_raw = Http::post($url, $data);
            } else {
                $response_raw = Http::get($url, $data);
            }

            $status = Http::getStatus();

            if ($status === Http::STATUS_OK) {
                break;
            }

            usleep(self::DELAY);
            $attempt++;
        }

        Http::$logging = $logging;

        if (!empty($response_raw)) {
            $response = @json_decode($response_raw, true);
        } else {
            $response = false;
        }

        return $response;
    }

    /**
     * Returns a link for request to Tilda api
     *
     * @param string $action Action that will execute the request
     *
     * @return string Url for request
     */
    protected function getUrl($action)
    {
        return sprintf('http://api.tildacdn.info/v1/%s', $action);
    }
}
