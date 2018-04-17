#!/usr/bin/env bash

DEFAULT_COLOR='\033[0m'
SUCCESS_COLOR='\033[32m'
NOTICE_COLOR='\033[33m'
ERROR_COLOR='\033[31m'

GIT_DIR=`git rev-parse --show-toplevel`
I=`whoami`

cd $GIT_DIR
echo -e "${NOTICE_COLOR}Executed by ${I} at ${GIT_DIR}${DEFAULT_COLOR}"

echo -e "${NOTICE_COLOR}Install code sniffer to pre-commit hook${DEFAULT_COLOR}"
bash $GIT_DIR/scripts/install-git-pre-commit.sh

echo -e "${SUCCESS_COLOR}End of the install script execution${DEFAULT_COLOR}"

exit 0