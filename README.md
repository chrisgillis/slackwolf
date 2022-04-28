# Slackwolf

**This project is not actively maintained any longer: ** I have not been diligently maintaining this project for several years. If you're interested in updating or maintaining Slackwolf, please send me a message! 

Slackwolf is a bot for Slack. After inviting the bot to a channel, you can play the timeless game of [Werewolf (also called Mafia)](https://en.wikipedia.org/wiki/Mafia_(party_game)).

![ProjectImage](http://i.imgur.com/0Kwd8oe.png)

## Roles
Slackwolf currently supports Seer, Bodyguard, Witch, Lycan, Tanner, Beholder, Villager, Wolfman, and Werewolf. You'll need at least a 6 player game in order to see roles other than Seer/Villager/Werewolf given out.

## How to play
`/invite` the bot (and some friends) to a channel and type !help

## Installation via Docker
Run the below command to start a container.
```
docker run -d --name slackwolf --restart always \
   -e "BOT_TOKEN=xoxb-16776859568-RxBtmpeS7isMAQonAEqS1hYLb" \
   -e "TIMEZONE=America/Los_Angeles" \
   -e "BOT_NAME=werewolf-moderator" \
   -e "DEBUG=1" \
   gillisct/slackwolf
```

## Development via Docker
In order to use a docker container for development of this project, first build
the container with the following command:
```
docker build -t {namespace}/slackwolf .
```
Substituting `namespace` for a relevant namespace you wish to refer the
container in, commonly your dockerhub username. Then modify following command
with your Slack Credentials and chosen namespace:  

```
docker run -it --name slackwolf \
-e "BOT_TOKEN=" \
-e "TIMEZONE=" \
-e "BOT_NAME=" \
-e "DEBUG=1" \
{namespace}/slackwolf \
-v "$(pwd)/src":/usr/src/slackwolf/src \
/bin/bash
```
This will provide you wish a shell inside the container, while still having the
changes made within the `src` directory reflected within the container
environment.

To start the bot within the container type `php bot.php`

## Source Installation
If you don't want to use docker, you can install from source.

Slackwolf requires PHP 5.5+ and [Composer](https://getcomposer.org/).

```
git clone http://github.com/chrisgillis/slackwolf
cd slackwolf
composer install
```

Rename `.env.default` to `.env` and edit it with a valid real-time messaging bot token from Slack. Get a valid token from the "Custom Integrations" tab of your Slack "Configure Apps" page. Also be sure to put the correct bot name in the `.env` file as well.

To start the bot type `php bot.php`



## Contributing

We're very accepting of pull requests. This is a fun project to get your feet wet with PHP or open source. If you're making a large change, create an Issue first and lets talk about it.

## License

MIT License.
