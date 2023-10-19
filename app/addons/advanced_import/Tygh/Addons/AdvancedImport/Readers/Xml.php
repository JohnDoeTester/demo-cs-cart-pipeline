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

namespace Tygh\Addons\AdvancedImport\Readers;

use DOMDocument;
use Tygh\Common\OperationResult;
use XMLReader;

class Xml implements IReader
{
    /**
     * @var int When the filesize exceeds this value, it won't be read fully to determine its structure
     *          (100 Mb)
     */
    const FULLSCAN_MAX_FILESIZE = 104857600;

    /**
     * @var int Amount of rows to read from a file file when determining its structure
     * @todo: reduce to a sane value
     */
    const SCHEMA_MAX_PROBE_SIZE = -1;

    /**
     * @var int Amount of rows to read from a big file file when determining its structure
     * @see \Tygh\Addons\AdvancedImport\Readers\Xml::FULLSCAN_MAX_FILESIZE
     */
    const SCHEMA_MAX_PROBE_SIZE_BIG_FILE = 3000;

    /** @var string Legacy target node path delimiter */
    const PATH_DELIMITER_LEGACY = '->';

    /** @var string Target node path delimiter */
    const PATH_DELIMITER = '/';

    /** @var string Symbol to open attribute definition with */
    const ATTR_OPEN = '@';

    /** @var string Array key to store single node value */
    const VAL = '.value';

    /** @var string Array key to store multiple node values */
    const VALS = '.values';

    /** @var string Array key to store node attributes */
    const ATTS = '.attributes';

    /** @var string Node values delimiter */
    protected $value_delimiter = ',';

    /** @var string $path Path to target file */
    protected $path;

    /** @var  XMLReader $reader Reader instance */
    protected $reader;

    /** @var array $current_path Array that contains current path of parsing file */
    protected $current_path = array();

    /** @var array $result Xml converted to an array */
    protected $result = array();

    /** @var array $options Array of options */
    protected $options = array();

    /** @inheritdoc */
    public function __construct($path, array $options = array())
    {
        $this->path = $path;
        $this->reader = new XMLReader();
        $this->options = $options;
        if (!isset($options['fields_delimiter'])) {
            return;
        }
        $this->value_delimiter = $options['fields_delimiter'];
    }

    /** @inheritdoc */
    public function getSchema()
    {
        $result = new OperationResult(false, array());

        $probe_size = self::SCHEMA_MAX_PROBE_SIZE;
        if (filesize($this->path) > self::FULLSCAN_MAX_FILESIZE) {
            $probe_size = self::SCHEMA_MAX_PROBE_SIZE_BIG_FILE;
        }

        $contents = $this->getContents($probe_size);
        $contents = $contents->getData();

        if (!empty($contents)) {
            $schema = reset($contents);
            $schema = array_keys($schema);
            $result->setData($schema);
        }
        unset($contents);

        if (!empty($schema)) {
            $result->setSuccess(true);
        } else {
            $result->setWarnings(array(
                'check_target_node' => __('advanced_import.fetching_schema_failed_check_file')
            ));
        }

        return $result;
    }

    /** @inheritdoc */
    public function getContents($count = null, array $schema = null)
    {
        $result = new OperationResult();
        $contents = array();
        $counter = 0;
        $node = $this->getTargetNode((int) $count);

        if ($node) {

            if (isset($node[self::VALS])) {
                $node = $node[self::VALS];
            } else {
                $node = array($node);
            }

            // iterate trough parent nodes
            foreach ($node as $item) {
                $content_counter = $schema === null ? 0 : $counter;

                if ($count === $counter) {
                    break;
                }

                if (!isset($contents[$content_counter])) {
                    $contents[$content_counter] = array_fill_keys(
                        array_values((array) $schema),
                        null
                    );
                }

                if (isset($item[self::ATTS])) {

                    foreach ($item[self::ATTS] as $attr_name => $attr_value) {

                        $path = $this->getAtrributeSelector($attr_name);

                        if ($schema && !in_array($path, $schema)) {
                            continue;
                        }

                        $contents[$content_counter][$path] = $attr_value;
                    }

                    unset($item[self::ATTS]);
                }

                $item = $this->flattenNode($item);

                // iterate trough parent node's elements
                foreach ($item as $element_name => $elements) {

                    if (isset($elements[self::VALS])) {
                        $elements = $elements[self::VALS];
                    } else {
                        $elements = array($elements);
                    }

                    foreach ($elements as $element) {
                        $path = $element_name;

                        $original_value = $value = isset($element[self::VAL])
                            ? $element[self::VAL]
                            : '';

                        $current_value = isset($contents[$counter][$path])
                            ? $contents[$counter][$path]
                            : '';

                        if ($current_value && $value) {
                            $value =  $current_value . $this->value_delimiter . $value;
                        }

                        $contents[$content_counter][$path] = $value;

                        // inject attributes as fields
                        if (!empty($element[self::ATTS])) {
                            foreach ($element[self::ATTS] as $attr_name => $attr_value) {
                                $attr_paths = array(
                                    $this->getAtrributeSelector($attr_name, $element_name) => $attr_value,
                                );

                                if (isset($element[self::VAL])) {
                                    $attr_paths[$this->getAtrributeSelector($attr_name, $element_name, $attr_value)] = $original_value;
                                }

                                foreach ($attr_paths as $path => $value_to_insert) {
                                    if ($schema && !in_array($path, $schema)) {
                                        continue;
                                    }

                                    if (!isset($contents[$content_counter][$path])) {
                                        if ($schema === null) {
                                            $this->spliceAttributeValue(
                                                $contents[$content_counter],
                                                $element_name,
                                                $attr_name,
                                                $value_to_insert,
                                                $path
                                            );
                                        } else {
                                            $contents[$content_counter][$path] = $value_to_insert;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $counter++;
            }
        }

        $result->setData($contents);
        return $result;
    }

    /** @inheritdoc */
    public function getApproximateLinesCount()
    {
        $target_node_path = $this->getTargetPath();
        $nodes_to_count = end($target_node_path);

        if (!empty($nodes_to_count)) {
            $dom = new DOMDocument();
            $dom->load($this->path);

            $nodes_list = $dom->getElementsByTagName($nodes_to_count);
            return $nodes_list->length;
        }

        return 0;
    }

    /** @inheritdoc */
    public function getExtension()
    {
        return 'xml';
    }

    /**
     * Parses xml into array
     *
     * @param int $count Quantity of target nodes to parse
     *
     * @return array
     */
    public function parse($count = -1)
    {
        $target_node_path = $this->getTargetPath();
        $target_node = end($target_node_path);
        $parsing_target = false;

        $opened_nodes = array();

        $this->result = array();
        $this->current_path = array();
        $this->reader->open($this->path);

        while ($this->reader->read()) {
            $node_name = $this->reader->name;
            $node_type = $this->reader->nodeType;

            if ($node_type === XMLReader::END_ELEMENT) {

                if (end($opened_nodes) === $node_name) {
                    array_pop($opened_nodes);

                    if ($node_name === $target_node) {
                        $parsing_target = false;

                        $count--;

                        if ($count === 0) {
                            break;
                        }
                    }
                }

                continue;

            } if ($node_type === XMLReader::ELEMENT) {

                if (!$parsing_target && !in_array($node_name, $target_node_path)) {
                    continue;
                }

                if ($node_name === $target_node) {
                    $parsing_target = true;
                }

                $opened_nodes[] = $node_name;
                $path_key = $this->reader->depth;

                $this->current_path[$path_key] = $node_name;

                foreach ($this->current_path as $key => $path_item) {
                    if ($key > $path_key) {
                        unset($this->current_path[$key]);
                    }
                }

                $attributes = $this->getAttributes();
                $this->createParentItem($attributes);

            } elseif ($node_type === XMLReader::TEXT || $node_type === XMLReader::CDATA) {

                if (!$parsing_target) {
                    continue;
                }

                $value = '';

                if ($this->reader->hasValue) {
                    $value = $this->reader->value;
                }

                $this->storeValue($value);
            }
        }

        $this->reader->close();
        return $this->result;
    }

    /**
     * Fetches array of target elements from xml
     *
     * @param int $count Quantity of target nodes to be parsed
     *
     * @return array|bool|mixed
     */
    protected function getTargetNode($count = -1)
    {
        $node = false;
        $target_node_path = $this->getTargetPath();

        if (!empty($target_node_path)) {
            $node = $this->parse((int) $count);

            foreach ($target_node_path as $key) {
                if (isset($node[$key])) {
                    $node = $node[$key];
                } elseif (isset($node[self::VALS]) && isset($node[self::VALS][0][$key])) {
                    $node = [
                        self::VALS => array_map(function ($target_node) use ($key) {
                            return array_merge($target_node[$key], $target_node[self::ATTS]);
                        }, $node[self::VALS])
                    ];
                } else {
                    $node = false;
                    break;
                }
            }
        }

        return $node;
    }

    /**
     * Fetches node's attributes
     *
     * @return array
     */
    protected function getAttributes()
    {
        $attributes = array();

        if ($this->reader->hasAttributes) {
            while ($this->reader->moveToNextAttribute()) {
                $attributes[$this->reader->name] = $this->reader->value;
            }
        }

        return $attributes;
    }

    /**
     * Created node inside the result array
     *
     * @param array $attributes Node's attributes
     *
     * @return $this
     */
    protected function createParentItem($attributes = array())
    {
        $path_length = count($this->current_path);
        $current_element = &$this->result;

        foreach ($this->current_path as $key) {
            $path_length--;

            if (!array_key_exists($key, $current_element)) {
                $current_element[$key] = array();
            }

            if (isset($current_element[$key])) {
                $current_element = &$current_element[$key];
            }

            if ($path_length === 0) {

                if (!empty($current_element)) {

                    if (isset($current_element[self::VALS])) {
                        $max_key = max(array_keys($current_element[self::VALS]));
                        $current_element[self::VALS][$max_key + 1][self::ATTS] = $attributes;

                    } else {
                        $attr = $current_element[self::ATTS];
                        unset($current_element[self::ATTS]);
                        $rest = $current_element;

                        foreach ($current_element as $key => $value) {
                            unset($current_element[$key]);
                        }

                        $current_element[self::VALS][0] = $rest;
                        $current_element[self::VALS][0][self::ATTS] = $attr;
                        $current_element[self::VALS][1][self::ATTS] = $attributes;
                    }
                } else {
                    $current_element[self::ATTS] = $attributes;
                }

            } elseif (isset($current_element[self::VALS])) {
                $max_key = max(array_keys($current_element[self::VALS]));
                $current_element = &$current_element[self::VALS][$max_key];
            }
        }

        unset($current_element);

        return $this;
    }

    /**
     * Stores value into the result array
     *
     * @param mixed $value Value to be stored
     *
     * @return $this
     */
    protected function storeValue($value = null)
    {
        $path_length = count($this->current_path);
        $current_element = &$this->result;

        foreach ($this->current_path as $key) {
            $path_length--;

            if (isset($current_element[$key])) {
                $current_element = &$current_element[$key];

                if (isset($current_element[self::VALS])) {
                    $maxKey = max(array_keys($current_element[self::VALS]));
                    $current_element = &$current_element[self::VALS][$maxKey];
                }
            }

            if ($path_length === 0) {
                if (!empty($current_element[self::VAL])) {
                    $current_element[self::VAL] .= $value;
                } else {
                    $current_element[self::VAL] = $value;
                }
            }
        }

        unset($current_element);

        return $this;
    }

    /**
     * Fetches all keys that have "values" key inside
     *
     * @param array $path Prefix path array
     * @param array $data Data to look in
     *
     * @return array
     */
    protected function getTargetNodes(array $path, $data)
    {
        $result = array();

        foreach ($data as $key => $values) {
            $current_path = $path;
            $current_path[] = $key;

            if (isset($values[self::VALS])) {
                $result[] = $current_path;
                break;
            } elseif (is_array($values) && !isset($values[self::VAL])) {
                unset($values[self::ATTS]);
                $tmp = $this->getTargetNodes($current_path, $values);
                $result = array_merge($result, $tmp);
            }
        }

        return $result;
    }

    /**
     * Fetches path to target node in xml file
     *
     * @return array
     */
    protected function getTargetPath()
    {
        $target_path = array();

        if (!empty($this->options['target_node'])) {
            $this->options['target_node'] = str_replace(
                self::PATH_DELIMITER_LEGACY,
                self::PATH_DELIMITER,
                $this->options['target_node']
            );
            $target_path = explode(self::PATH_DELIMITER, $this->options['target_node']);
        }

        return $target_path;
    }

    /**
     * Converts nested arrays of an imported item into a flat array with fully qualified paths.
     *
     * @param array  $item        Imported item
     * @param string $parent_path Stored parent element path
     *
     * @return array
     */
    protected function flattenNode(array $item, $parent_path = '')
    {
        $flat_item = array();

        if (isset($item[self::VAL])) {
            $item = array('' => $item);
        }

        foreach ($item as $elm_name => $elm) {
            // skip hidden properties from processing
            if ($elm_name && $elm_name[0] == '.') {
                continue;
            }

            $process_value    = isset($elm[self::VAL]);
            $process_values   = isset($elm[self::VALS]);
            $has_attributes   = isset($elm[self::ATTS]);
            $process_subnodes = count($elm) - (int) $process_value - (int) $process_values - (int) $has_attributes > 0;
            $process_elm      = !$process_subnodes && !$process_values;

            $path = rtrim($parent_path . self::PATH_DELIMITER . $elm_name, self::PATH_DELIMITER);

            // convert nested nodes to fully qualified xpath nodes
            if ($process_subnodes) {

                $flat_item[$path] = array();

                $flat_item = array_merge(
                    $flat_item,
                    $this->flattenNode($elm, $path)
                );
            }

            // aggregate values from subnodes within a node having the same name
            if ($process_values) {

                $flat_item[$path] = array();

                foreach ($elm[self::VALS] as $value) {

                    $elm_tmp_item = $this->flattenNode($value, $path);
                    foreach ($elm_tmp_item as $sub_path => $flat_elm) {

                        if (isset($flat_item[$sub_path][self::VALS])
                            && isset($flat_elm[self::VAL])
                        ) {
                            $flat_item[$sub_path][self::VALS][] = $flat_elm;

                        } elseif (isset($flat_item[$sub_path][self::VAL])
                            && isset($flat_elm[self::VAL])
                        ) {
                            $flat_item[$sub_path][self::VALS] = array($flat_item[$sub_path]);
                            $flat_item[$sub_path][self::VALS][] = $flat_elm;
                            unset($flat_item[$sub_path][self::VAL]);

                        } else {
                            $flat_item[$sub_path] = $flat_elm;
                        }
                    }
                }
            }

            // store node as is
            if ($process_elm) {
                $flat_item[$path] = $elm;
            }
        }

        unset($item);

        return $flat_item;
    }

    /**
     * Builds attribute path selector.
     *
     * @param string      $element_name    Element name
     * @param string      $attribute_name  Attribute name
     * @param string|null $attribute_value Attribute value
     *
     * @return string
     */
    protected function getAtrributeSelector(string $attribute_name, string $element_name = '', $attribute_value = null)
    {
        $selector = $element_name . self::ATTR_OPEN . $attribute_name;

        if ($attribute_value !== null) {
            $selector .= '="' . addslashes($attribute_value) . '"';
        }

        return $selector;
    }

    /**
     * Builds attrbute path prefix selector.
     *
     * @param string $element_name   Element name
     * @param string $attribute_name Attribute name
     *
     * @return string
     */
    protected function getAttributePrefixSelector($element_name, $attribute_name)
    {
        return $selector = $element_name . self::ATTR_OPEN . $attribute_name . '=';
    }

    /**
     * Splices attribute value field into an item after the attribute definition field
     * or the latest attribute value field.
     *
     * @param array  $item      Item to splice into
     * @param string $node_name Node the attribute relates to
     * @param string $attribute Attribute name
     * @param string $value     Attribute value
     * @param string $path      Field key
     */
    protected function spliceAttributeValue(array &$item, $node_name, $attribute, $value, $path)
    {
        $attr_selector = $this->getAtrributeSelector($attribute, $node_name);
        $attr_prefix_selector = $this->getAttributePrefixSelector($node_name, $attribute);
        $element_any_attr_selector = explode(self::ATTR_OPEN, $path, 2);
        $element_selector = reset($element_any_attr_selector);
        $element_any_attr_selector = $element_selector . self::ATTR_OPEN;

        $splice_position = count($item);
        foreach (array_reverse(array_keys($item)) as $key) {
            if (strpos($key, $attr_selector) !== false
                || strpos($key, $attr_prefix_selector) !== false
            ) {
                break;
            }
            $splice_position--;
        }

        if ($splice_position === 0) {
            $splice_position = count($item);
            foreach (array_reverse(array_keys($item)) as $key) {
                if (strpos($key, $element_any_attr_selector) !== false
                    || $key === $element_selector
                ) {
                    break;
                }
                $splice_position--;
            }
        }

        if ($splice_position == 0) {
            $splice_position = count($item);
        }

        $item =
            array_slice($item, 0, $splice_position, true)
            + array($path => $value)
            + array_slice($item, $splice_position, null, true);
    }
}
