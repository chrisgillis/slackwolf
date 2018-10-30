docker run -it --name slackwolf \
-e "BOT_TOKEN=" \
-e "TIMEZONE=" \
-e "BOT_NAME=" \
-e "DEBUG=1" \
owena11/slackwolf \
#-v "$(pwd)/src":/usr/src/slackwolf/src \
#/bin/bash
