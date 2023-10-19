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

namespace Tygh\Api\Entities;

use Exception;
use Tygh\Addons\GraphqlApi\Context;
use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Tygh;

class Graphql extends AEntity
{
    /**
     * @inheritDoc
     */
    public function index($id = 0, $params = [])
    {
        $query = $this->getQuery($params);
        $variables = $this->getVariables($params);

        if (!$query) {
            return ['status' => Response::STATUS_BAD_REQUEST];
        }

        /** @var \Tygh\Addons\GraphqlApi\Api $api */
        $api = Tygh::$app['graphql_api'];

        try {
            $context = new Context(Tygh::$app, $this->auth, static::getLanguageCode($params), static::getCurrencyCode($params));

            $result = $api->execute($query, $variables, $context);

            $result->setErrorsHandler(function ($errors, callable $formatter) {
                return array_column($errors, 'message');
            });
            $result = $result->toArray();

            return [
                'status' => Response::STATUS_OK,
                'data'   => $result,
            ];
        } catch (Exception $exception) {
            return ['status' => Response::STATUS_INTERNAL_SERVER_ERROR];
        }
    }

    /**
     * @inheritDoc
     */
    public function create($params)
    {
        return $this->index(0, $params);
    }

    /**
     * @inheritDoc
     */
    public function update($id, $params)
    {
        return $this->index(0, $params);
    }

    /**
     * @inheritDoc
     */
    public function delete($id)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
    }

    /**
     * @inheritDoc
     */
    public function privileges()
    {
        return [
            'index'  => true,
            'create' => true,
            'update' => true,
        ];
    }

    /**
     * @inheritDoc
     */
    public function privilegesCustomer()
    {
        return [
            'index'  => true,
            'create' => true,
            'update' => true,
        ];
    }

    /**
     * Extracts query from the request itself or from the operations details when using multipart request.
     *
     * @param array $params Request parameters
     *
     * @return string|null
     */
    protected function getQuery(array $params): string
    {
        $query = $this->safeGet($params, 'query', null);

        if (isset($params['operations'])) {
            $operations = json_decode($params['operations'], true);
            if (isset($operations['query'])) {
                $query = $operations['query'];
            }
        }

        return $query;
    }

    /**
     * Extracts variables from the request itself or from the operations details when using multipart request.
     *
     * @param array $params Request parameters
     *
     * @return array
     */
    protected function getVariables(array $params): array
    {
        $variables = $this->safeGet($params, 'variables', []);

        if (isset($params['operations'])) {
            $operations = json_decode($params['operations'], true);
            if (isset($operations['variables'])) {
                $variables = $operations['variables'];
            }
        }

        if (isset($params['map'])) {
            $files_to_variables_map = json_decode($params['map'], true);
            if (is_array($files_to_variables_map)) {
                // FIXME: Remove $_FILES superglobal dependency
                $variables = $this->populateFileUploads($files_to_variables_map, $_FILES, $variables);
            }
        }

        return $variables;
    }

    /**
     * Populates variables with the uploaded files.
     *
     * @param string $files_to_variables_map Uploaded files map
     * @param array  $variables              Operation variables
     *
     * @return array
     */
    protected function populateFileUploads($files_to_variables_map, $files, $variables): array
    {
        foreach ($files as $file_id => $file) {
            if (!isset($files_to_variables_map[$file_id])) {
                continue;
            }

            $file = $this->normalizeFileStructure($file);

            $mapped_variable_path = explode('.', reset($files_to_variables_map[$file_id]));
            if ($mapped_variable_path[0] !== 'variables') {
                continue;
            }
            array_shift($mapped_variable_path);

            $target = &$variables;

            foreach ($mapped_variable_path as $key) {
                if (!isset($target[$key])) {
                    $target[$key] = [];
                }
                $target = &$target[$key];
            }

            $target = $file;
        }

        return $variables;
    }

    /**
     * Normalizes $_FILES item to be used with core file uploading functions.
     *
     * $_FILES structure differs when using array syntax in form.
     * Without array syntax (<input type="file" name="simple">):
     * [
     *   0 => [
     *     'name'     => 'foo.png',
     *     'error'    => 0,
     *     'size'     => 1,
     *     'tmp_name' => '/tmp/foo',
     *     'type'     => 'image/png',
     *   ]
     * ]
     *
     * With array syntax (<input type="file" name="array[0]">):
     * [
     *   0 => [
     *     'name'     => ['foo.png',   'bar.png'],
     *     'error'    => [0,           0],
     *     'size'     => [1,           2],
     *     'tmp_name' => ['/tmp/foo',  '/tmp/bar'],
     *     'type'     => ['image/png', 'image/png']
     *   ]
     * ]
     *
     * @param array $file
     *
     * @return mixed
     */
    protected function normalizeFileStructure(array $file): array
    {
        reset($file);
        if (!is_numeric(key($file))) {
            array_walk($file, function (&$value) {
                $value = [$value];
            });
        }

        return $file;
    }

}
