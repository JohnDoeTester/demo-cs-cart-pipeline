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

namespace Tygh\Addons\AdvancedImport\Modifiers\Parsers;

use Tygh\Enum\Addons\AdvancedImport\ModifierParsingState;
use Tygh\Addons\AdvancedImport\Exceptions\InvalidModifierFormatException;
use Tygh\Addons\AdvancedImport\Exceptions\InvalidModifierParameterException;

/**
 * The class responsible parsing modifier using single pass approach. Embedded operations are not allowed.
 *
 * @package Tygh\Addons\AdvancedImport\Modifiers\Parsers
 */
class SinglePassModifierParser implements IModifierParser
{
    const PARAMETER_LIST_OPENER = '(';
    const PARAMETER_LIST_CLOSER = ')';
    const PARAMETER_DELIMITER = ',';

    /** @var int Current parsing state */
    protected $parsingState;

    /** @var array Character list that invalid for operator to begin with */
    protected $invalidOperatorChars = array('(');

    /** @var array Character list that a parameter can be wrapped in */
    protected $parameterWrapperVariants = array("'", '"');

    /** @var array Array that contains function name characters as its items */
    protected $function = array();

    /** @var array Array that contains parameter characters as its items */
    protected $parameter = array();

    /** @var array Array of parsed parameters */
    protected $parameters = array();

    /** @var null|string the function's parameter wrapper that is expecting to be at the end of parameter */
    protected $expectedParameterCloserWrapper = null;

    /** @var null|string wrapper parameter that is expecting to be at the end of the value parameter */
    protected $expectedInnerParameterCloserWrapper = null;
    
    /**
     * @inheritdoc
     */
    public function parse($modifier)
    {
        $this->resetParser();
        $modifier = trim($modifier);
        $safe_threshold = 50000;

        $chars = str_split($modifier);

        while (count($chars) > 0 && $safe_threshold > 0) {
            $safe_threshold--;
            $char = array_shift($chars);

            if ($this->parsingState === ModifierParsingState::STARTING_PARSING_MODIFIER) {
                $this->startParsingModifier($char);

            } elseif ($this->parsingState === ModifierParsingState::EXPECTING_OPENING_BRACKET) {
                $this->parseFunctionName($char);

            } elseif ($this->parsingState === ModifierParsingState::STARTING_PARSING_PARAMETER) {
                $this->startParsingParameter($char);

            } elseif ($this->parsingState === ModifierParsingState::EXPECTING_PARAMETER_WRAPPER) {
                $this->parseParameterWithWrapper($char);

            } elseif ($this->parsingState === ModifierParsingState::EXPECTING_PARAMETER_DELIMITER) {
                $this->parseParameter($char);

            } elseif ($this->parsingState === ModifierParsingState::PARAMETER_PARSING_FINISHED) {
                $this->parseAfterParameterParseFinished($char);

            } elseif ($this->parsingState === ModifierParsingState::PARSING_FINISHED) {
                break;
            }
        }

        $this->checkParserStateAfterParse($modifier);

        return array(
            'function' => trim(implode('', $this->function)),
            'parameters' => $this->parameters,
        );
    }

    /**
     * Starts modifier parsing by filtering out invalid characters at the beginning of the function name
     *
     * @param string $char Character of the modifier string
     *
     * @throws \Tygh\Addons\AdvancedImport\Exceptions\InvalidModifierFormatException
     */
    protected function startParsingModifier($char)
    {
        // Skip any whitespace character
        if (!$this->isWhitespaceChar($char)) {

            // The function name cannot start with opening bracket character
            if ($char === self::PARAMETER_LIST_OPENER) {
                throw new InvalidModifierFormatException(
                    __('advanced_import.invalid_function_firs_character',
                        array('[character]' => $char)
                    )
                );
            }

            $this->parsingState = ModifierParsingState::EXPECTING_OPENING_BRACKET;
            $this->function[] = $char;
        }
    }

    /**
     * Parses the function name by checking if the character is an opening bracket
     *
     * @param string $char Character of the modifier string
     */
    protected function parseFunctionName($char)
    {
        if ($char === self::PARAMETER_LIST_OPENER) {
            $this->parsingState = ModifierParsingState::STARTING_PARSING_PARAMETER;
        } elseif (!$this->isWhitespaceChar($char)) {
            $this->function[] = $char;
        }
    }

    /**
     * Starts parsing parameter, by checking if it starts with wrapper or not
     *
     * @param string $char Character of the modifier string
     */
    protected function startParsingParameter($char)
    {
        if ($char === self::PARAMETER_LIST_CLOSER) {
            // we might have no params at all
            $this->parsingState = ModifierParsingState::PARSING_FINISHED;
        } elseif ($char === self::PARAMETER_DELIMITER) {
            // ignore any number of dividers (outside wrappers) when starting parsing parameter

        } elseif (in_array($char, $this->parameterWrapperVariants)) {
            // if it starting with a wrapper character, we will expect the corresponding one at the end of the parameter
            $this->expectedParameterCloserWrapper = $char;
            $this->parsingState = ModifierParsingState::EXPECTING_PARAMETER_WRAPPER;

        } elseif (!$this->isWhitespaceChar($char)) {
            // if the character is not white space we will parse until the parameters delimiter or the closing bracket
            $this->parameter[] = $char;
            $this->parsingState = ModifierParsingState::EXPECTING_PARAMETER_DELIMITER;
        }
    }

    /**
     * Parses parameter by checking whether the character is a wrapper
     *
     * @param string $char Character of the modifier string
     */
    protected function parseParameterWithWrapper($char)
    {
        // We are waiting for encountering the corresponding wrapper, if we find it we consider parameter is parsed
        if ($char === $this->expectedParameterCloserWrapper) {
            $this->addParsedParameter();
            $this->parsingState = ModifierParsingState::PARAMETER_PARSING_FINISHED;
        } else {
            $this->parameter[] = $char;
        }
    }

    /**
     * Parses parameter without wrapper
     *
     * @param string $char Character of the modifier string
     */
    protected function parseParameter($char)
    {
        if (($char === self::PARAMETER_DELIMITER || $char === self::PARAMETER_LIST_CLOSER) && empty($this->expectedInnerParameterCloserWrapper)) {
            $this->addParsedParameter('trim');
            $this->parsingState = ModifierParsingState::STARTING_PARSING_PARAMETER;

            if ($char === self::PARAMETER_LIST_CLOSER) {
                $this->parsingState = ModifierParsingState::PARSING_FINISHED;
            }
        } else {
            $this->parameter[] = $char;
        }

        if (empty($this->expectedInnerParameterCloserWrapper) && in_array($char, $this->parameterWrapperVariants)) {
            $this->expectedInnerParameterCloserWrapper = $char;
        } elseif ($char === $this->expectedInnerParameterCloserWrapper) {
            $this->expectedInnerParameterCloserWrapper = null;
        }
    }

    /**
     * Handles parsing process after parameter parsing has been finished
     *
     * @param string $char Character of the modifier string
     *
     * @throws \Tygh\Addons\AdvancedImport\Exceptions\InvalidModifierParameterException
     */
    protected function parseAfterParameterParseFinished($char)
    {
        if ($char === self::PARAMETER_DELIMITER) {
            $this->parsingState = ModifierParsingState::STARTING_PARSING_PARAMETER;
        } elseif ($char === self::PARAMETER_LIST_CLOSER) {
            $this->parsingState = ModifierParsingState::PARSING_FINISHED;
        } elseif (!$this->isWhitespaceChar($char)) {
            throw new InvalidModifierParameterException(
                __('advanced_import.unexpected_parameter_passed',
                    array('[delimiter]' => self::PARAMETER_DELIMITER, '[closer]' => self::PARAMETER_LIST_CLOSER, '[char]' => $char)
                )
            );
        }
    }

    /**
     * Check if the character is one of whitespace characters
     *
     * @param string $char Character of the modifier string
     *
     * @return bool
     */
    protected function isWhitespaceChar($char)
    {
        return ctype_space($char);
    }

    /**
     * Adds parsed parameter to parameter array
     *
     * @param null $parameterHandler Callback function for applying it to the parameter
     */
    protected function addParsedParameter($parameterHandler = null)
    {
        $parameter = implode('', $this->parameter);

        if (is_callable($parameterHandler)) {
            $parameter = $parameterHandler($parameter);
        }

        $this->parameters[] = $parameter;
        $this->expectedParameterCloserWrapper = null;
        $this->parameter = array();
    }

    /**
     * Resets parser state to initial
     */
    protected function resetParser()
    {
        $this->parsingState = ModifierParsingState::STARTING_PARSING_MODIFIER;
        $this->function = $this->parameters = $this->parameter = array();
    }

    /**
     * Checks if the modifier is properly terminated.
     *
     * @throws \Tygh\Addons\AdvancedImport\Exceptions\InvalidModifierFormatException
     */
    protected function checkParserStateAfterParse($modifier)
    {
        if ($this->parsingState !== ModifierParsingState::PARSING_FINISHED) {
            throw new InvalidModifierFormatException(
                __('advanced_import.invalid_modifier_message',
                    array(
                        '[modifier]' => $modifier,
                        '[message]'  => __('advanced_import.missing_parameters_list_closer', array(
                            '[closer]' => self::PARAMETER_LIST_CLOSER,
                        )),
                    )
                )
            );
        }
    }
}