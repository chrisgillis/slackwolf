<?php namespace Slackwolf\Game\Formatter;

use Slackwolf\Game\Option;
use Slackwolf\Game\OptionType;

/**
 * Defines the OptionFormatter class.
 */
class OptionFormatter
{
    /**
     * @param Option $option
     *
     * @return string
     */
    public static function format(Option $option)
    {        
        $rtn = "|_  ".$option->name." ";
        switch($option->optionType)
        {
            case OptionType::Bool:
                $rtn .= "on|off (".($option->value ? "on" : "off").")";
                break;
            case OptionType::Int:
                $rtn .= "intValue (".$option->value.")";
                break;
            case OptionType::String:
                $rtn .= "stringValue (".$option->value.")";
                break;
            case OptionType::StringArray:
                $rtn .= "add|remove stringValue (".implode(', ', $option->value).")";
                break;
            case OptionType::UserArray:
                $rtn .= "add|remove @user (".implode(', ', $option->value).")";
                break;
            default:
                $rtn .= "value (".$option->value.")";
                break;
        }

        return $rtn."\t\t".$option->helpText."\r\n";
    }
}