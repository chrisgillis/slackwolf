<?php namespace Slackwolf\Game;

use Exception;

class OptionType
{
    const Bool = 0;
    const Int = 1;
    const String = 2;
}

class Option
{
    public $name = "";
    public $optionType;
    public $value;
    public $helpText = "";

    /*
     * @param string $name
     * @param OptionType $optionType
     * @param string $helpText
     */
    public function __construct($name, $optionType, $value, $helpText)
    {
        $this->name = $name;
        $this->optionType = $optionType;
        $this->value = $value;
        $this->helpText = $helpText;
    }
}

class OptionsManager
{
    const optionsFileName = "options.json";
    /** @var Option[] $options */
    public $options = [];
    
    public function __construct()
    {
        $this->options[] = new Option("changevote", OptionType::Bool, "on", "When enabled votes can be changed until the final vote is cast.");
        $this->loadOptions();
    }
    
    public function loadOptions()
    {        
        /*
         * Load existing options
         */
        if (file_exists(OptionsManager::optionsFileName)) {
            try {
                $optionsLoaded = json_decode(file_get_contents(OptionsManager::optionsFileName));
                foreach($optionsLoaded as $loadedOption)
                {
                    /** @var Option $loadedOption */
                    if ($loadedOption->optionType == OptionType::Bool)
                    {
                        $this->setOptionValue($loadedOption->name, $loadedOption->value ? "on" : "off", false);
                    }
                    else
                    {
                        $this->setOptionValue($loadedOption->name, $loadedOption->value, false);
                    }
                }
            } catch(Exception $e) { }
        }
    }

    public function saveOptions()
    {
        try {
            file_put_contents(OptionsManager::optionsFileName,json_encode($this->options));
        } catch (Exception $e) {}
    }

    public function setOptionValue($optionName, $setValue, $doSave)
    {
        /** @var Option $option */
        $option = null;
        
        foreach ($this->options as $searchOption)
        {
            /** @var Option $searchOption */
            if ($searchOption->name == $optionName){
                $option = $searchOption;
                break;
            }
        }
        
        if ($option==null) { return; }
        
        $newValue = $option->value;
        switch($option->optionType)
        {
          case OptionType::Bool:
              $newValue = strcasecmp($setValue, "on") == 0 ? true : false;
              break;
          case OptionType::Int:
              $newValue = ctype_digit($setValue) ? intval($setValue) : $option->value;
              break;
          case OptionType::String:
              $newValue = $setValue;
              break;
        }
        $option->value = $newValue;
        
        if ($doSave) {                   
            $this->saveOptions();
        }
    }
    
    public function getOptionValue($optionName)
    {
        /** @var Option $option */
        $option = null;
        
        foreach ($this->options as $searchOption)
        {
            /** @var Option $searchOption */
            if ($searchOption->name == $optionName){
                $option = $searchOption;
                break;
            }
        }
        
        return ($option==null ? null : $option->value);
    }
}
