<?php namespace Slackwolf\Game;

use Exception;

class OptionName
{
    const CHANGE_VOTE = 'changevote';

    const GAME_MODE = 'game_mode';
    const GAME_MODE_CHAOS = 'chaos';
    const GAME_MODE_VANILLA = 'vanilla';
    const GAME_MODE_CLASSIC = 'classic';

    const NO_LYNCH = 'no_lynch';

    const MODS = 'mods';
    const EBOLA = 'ebola';

    const ROLE_BEHOLDER = 'role_beholder';
    const ROLE_BODYGUARD = 'role_bodyguard';
    const ROLE_HUNTER = "role_hunter";
    const ROLE_LYCAN = 'role_lycan';
    const ROLE_SEER = 'role_seer';
    const ROLE_TANNER = 'role_tanner';
    const ROLE_WITCH = 'role_witch';
    const ROLE_WOLFMAN = 'role_wolfman';
    const ROLE_FOOL = 'role_fool';
    const ROLE_CURSED = 'role_cursed';

    const START_MODE_OPTIONS = array(OptionName::GAME_MODE_CHAOS, OptionName::GAME_MODE_VANILLA, OptionName::GAME_MODE_CLASSIC);
    const NEW_MODE_OPTIONS = array(OptionName::GAME_MODE_CHAOS, OptionName::GAME_MODE_VANILLA, OptionName::GAME_MODE_CLASSIC);
}

class OptionType
{
    const Bool = 0;
    const Int = 1;
    const String = 2;
    const StringArray = 3;
    const UserArray = 4;
}

class Option
{
    public $name = "";

    /**
     * @var OptionType $optionType
     */
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

    /**
     * OptionsManager constructor.
     */
    public function __construct()
    {
        $this->options[OptionName::CHANGE_VOTE] = new Option(OptionName::CHANGE_VOTE, OptionType::Bool, "on",
            "When enabled votes can be changed until the final vote is cast.");
        $this->options[OptionName::NO_LYNCH] = new Option(OptionName::NO_LYNCH, OptionType::Bool, "on",
            "When enabled townsfolk can vote not to lynch anybody.");
        $this->options[OptionName::ROLE_BEHOLDER] = new Option(OptionName::ROLE_BEHOLDER, OptionType::Bool, "on",
            "Use Beholder role in random games.");
        $this->options[OptionName::ROLE_BODYGUARD] = new Option(OptionName::ROLE_BODYGUARD, OptionType::Bool, "on",
            "Use Bodyguard role in random games.");
        $this->options[OptionName::ROLE_HUNTER] = new Option(OptionName::ROLE_HUNTER, OptionType::Bool, "on",
            "Use Hunter role in random games.");
        $this->options[OptionName::ROLE_LYCAN] = new Option(OptionName::ROLE_LYCAN, OptionType::Bool, "on",
            "Use Lycan role in random games.");
        $this->options[OptionName::ROLE_SEER] = new Option(OptionName::ROLE_SEER, OptionType::Bool, "on",
            "Use Seer role in random games.");
        $this->options[OptionName::ROLE_TANNER] = new Option(OptionName::ROLE_TANNER, OptionType::Bool, "on",
            "Use Tanner role in random games.");
        $this->options[OptionName::ROLE_WITCH] = new Option(OptionName::ROLE_WITCH, OptionType::Bool, "on",
            "Use Witch role in random games.");
        $this->options[OptionName::ROLE_WOLFMAN] = new Option(OptionName::ROLE_WOLFMAN, OptionType::Bool, "on",
            "Use Wolf Man role in random games.");
        $this->options[OptionName::ROLE_FOOL] = new Option(OptionName::ROLE_FOOL, OptionType::Bool, "on",
            "Use Fool role in random games.");
        $this->options[OptionName::ROLE_CURSED] = new Option(OptionName::ROLE_CURSED, OptionType::Bool, "on",
            "Use Cursed role in random games.");
        $this->options[OptionName::GAME_MODE] = new Option(OptionName::GAME_MODE, OptionType::String, "classic",
            "Choose game mode: classic, chaos, vanilla");
        $this->options[OptionName::EBOLA] = new Option(OptionName::EBOLA, OptionType::Int, "10",
            "Ebola will strike 1 in n times, where n is this number. 0 for off.");

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
                foreach ($optionsLoaded as $loadedOption) {
                    /** @var Option $loadedOption */
                    if ($loadedOption->optionType == OptionType::Bool) {
                        $this->setOptionValue([$loadedOption->name, $loadedOption->value ? "on" : "off"], false);
                    } else {
                        $this->setOptionValue([$loadedOption->name, $loadedOption->value], false);
                    }
                }
            } catch (Exception $e) {
            }
        }
    }

    public function saveOptions()
    {
        file_put_contents(OptionsManager::optionsFileName, json_encode($this->options));
    }

    /**
     * @param array $args
     * @param $doSave
     * @return String message
     */
    public function setOptionValue(array $args, $doSave)
    {

        try {
            if (count($args) < 2) {
                return "minimum name/value required";
            }
            /** @var Option $option */
            $option = null;
            foreach ($this->options as $searchOption) {
                /** @var Option $searchOption */
                if ($searchOption->name == $args[0]) {
                    $option = $searchOption;
                    break;
                }
            }
            if ($option == null) {
                return $args[0] . " is an invalid option";
            }
            $newValue = $option->value;
            $setValue = $args[1];
            switch ($option->optionType) {
                case OptionType::Bool:
                    $newValue = strcasecmp($setValue, "on") == 0 ? true : false;
                    break;
                case OptionType::Int:
                    $newValue = ctype_digit($setValue) ? intval($setValue) : $option->value;
                    break;
                case OptionType::String:
                    $newValue = $setValue;
                    break;
                case OptionType::StringArray:
                case OptionType::UserArray:
                    if (count($args) < 3) {
                        return "name add|remove value, all required for " . $option->name;
                    }
                    if ($option->optionType == OptionType::UserArray) {
                        $this->client->getChannelGroupOrDMByID($this->channel)
                            ->then(function (Channel $channel) {
                                return $channel->getMembers();
                            })
                            ->then(function (array $users) use ($gameManager, $message, $client) {
                                /** @var \Slack\User[] $users */
                                $setValue = UserIdFormatter::format($setValue, $users);
                            });
                    }
                    switch (strtolower($args[1])) {
                        //TODO: Work!
                        case "add":
                            $newValue[] = $args[2];
                            break;
                        case "remove":
                            //TODO: if option name ='mods' user is same username as .env admin, do not allow removal
                            unset($newValue[$args[2]]);
                            break;
                        default:
                            return "invalid action for " . $option->name;
                    }
                    break;
            }
            $option->value = $newValue;
            if ($doSave) {
                try {
                    $this->saveOptions();
                } catch (Exception $e) {
                    return "failed to save option " . $option->name;
                }
            }
            return $option->name . " option change successful to " . $setValue;
        } catch (Exception $e) {
            return "failed to modify option value for " . $e;
        }
    }

    /**
     * @param $optionName
     * @return null
     */
    public function getOptionValue($optionName)
    {
        /** @var Option $option */
        $option = $this->options[$optionName];

        return ($option == null ? null : $option->value);
    }

    /**
     * @param $gameMode
     * @return bool
     */
    public function isGameMode($gameMode)
    {
        /** @var $optionValue */
        $optionValue = $this->getOptionValue(OptionName::GAME_MODE);
        return $optionValue == $gameMode;
    }
}
