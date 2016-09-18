# Slackwolf
Slackwolf is a bot for Slack. After inviting the bot to a channel, you can play the timeless game of [Werewolf (also called Mafia)](https://en.wikipedia.org/wiki/Mafia_(party_game)).

![ProjectImage](http://i.imgur.com/0Kwd8oe.png)

## Roles
Slackwolf currently supports Seer, Bodyguard, Witch, Lycan, Tanner, Beholder, Villager, Wolfman, and Werewolf. You'll need at least a 6 player game in order to see roles other than Seer/Villager/Werewolf given out.

## How to play
`/invite` the bot (and some friends) to a channel and type !help

## Installation
Slackwolf requires PHP 5.5+ and [Composer](https://getcomposer.org/).

```
git clone http://github.com/chrisgillis/slackwolf
cd slackwolf
composer install
```

Rename `.env.default` to `.env` and edit it with a valid real-time messaging bot token from Slack. Get a valid token from the "Custom Integrations" tab of your Slack "Configure Apps" page. Also be sure to put the correct bot name in the `.env` file as well.

To start the bot type `php bot.php`

### Installation via Docker
```
docker run -d --name slackwolf --restart always \
   -e "BOT_TOKEN=xoxb-16776859568-RxBtmpeS7iMAQonAEqS1hYLb" \
   -e "TIMEZONE=America/Los_Angeles" \
   -e "BOT_NAME=werewolf-moderator" \
   -e "DEBUG=1" \
   gillisct/slackwolf
```

## Contributing

We're very accepting of pull requests. This is a fun project to get your feet wet with PHP or open source. If you're making a large change, create an Issue first and lets talk about it.

## License

MIT License.
