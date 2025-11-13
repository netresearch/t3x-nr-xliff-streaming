#!/usr/bin/env bash

#
# TYPO3 Extension test runner based on docker/podman
#
# Heavily inspired by TYPO3 Core and Tea extension
#

# Function to write a .env file
# This is needed for CI and some docker versions
setUpDockerComposeDotEnv() {
    # Delete possibly existing local .env file
    [ -f .env ] && rm .env
    # Set up a new .env file for docker compose
    {
        echo "COMPOSE_PROJECT_NAME=${PROJECT_NAME}"
        echo "HOST_UID=$(id -u)"
        echo "HOST_PID=$(id -g)"
        echo "PHP_VERSION=${PHP_VERSION}"
        echo "TYPO3_VERSION=${TYPO3_VERSION}"
        echo "DOCKER_PHP_IMAGE=${DOCKER_PHP_IMAGE}"
    } > .env
}

# Options
TEST_SUITE="unit"
PHP_VERSION="8.2"
TYPO3_VERSION="13"
DOCKER_PHP_IMAGE=""
PROJECT_NAME="t3x-nr-xliff-streaming"
CONTAINER_BIN="docker"
CONTAINER_COMPOSE="docker compose"
SCRIPT_VERBOSE=0

# Help
loadHelp() {
    cat <<EOF
TYPO3 Extension test runner based on docker/podman

Usage: $0 [options] [file]

Options:
    -s <...>
        Specifies which test suite to run
            - unit (default): PHP unit tests

    -p <7.4|8.0|8.1|8.2|8.3|8.4>
        Specifies the PHP version to use
            - 8.2 (default)

    -t <13>
        Only with -s functional|functionalDeprecated
        Specifies the TYPO3 CORE version to use
            - 13 (default)

    -v
        Enable verbose output

    -h
        Show this help

Examples:
    # Run unit tests with PHP 8.2
    ./Build/Scripts/runTests.sh

    # Run unit tests with PHP 8.3
    ./Build/Scripts/runTests.sh -p 8.3

    # Run unit tests with verbose output
    ./Build/Scripts/runTests.sh -v
EOF
}

# Test if docker exists, else exit
if ! type "$CONTAINER_BIN" >/dev/null 2>&1; then
    echo "Container environment '$CONTAINER_BIN' not found. Please install docker or podman." >&2
    exit 1
fi

# Go to extension root directory
SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
cd "$SCRIPT_DIR/../../" || exit 1

# Option parsing
while getopts ":s:p:t:hv" opt; do
    case ${opt} in
        s)
            TEST_SUITE=${OPTARG}
            ;;
        p)
            PHP_VERSION=${OPTARG}
            if ! [[ ${PHP_VERSION} =~ ^(8.2|8.3|8.4)$ ]]; then
                echo "Invalid -p option argument ${OPTARG}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        t)
            TYPO3_VERSION=${OPTARG}
            if ! [[ ${TYPO3_VERSION} =~ ^(13)$ ]]; then
                echo "Invalid -t option argument ${OPTARG}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        h)
            loadHelp
            exit 0
            ;;
        v)
            SCRIPT_VERBOSE=1
            ;;
        \?)
            echo "Invalid option: -${OPTARG}" >&2
            echo >&2
            echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
            exit 1
            ;;
        :)
            echo "Option -${OPTARG} requires an argument" >&2
            echo >&2
            echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
            exit 1
            ;;
    esac
done

# Determine PHP Docker image
DOCKER_PHP_IMAGE="php:${PHP_VERSION}-cli-alpine"

# Verbose output
if [ ${SCRIPT_VERBOSE} -eq 1 ]; then
    set -x
fi

# Suite execution
case ${TEST_SUITE} in
    unit)
        setUpDockerComposeDotEnv
        ${CONTAINER_BIN} run --rm \
            -v "$(pwd)":/app \
            -w /app \
            "${DOCKER_PHP_IMAGE}" \
            sh -c "
                apk add --no-cache bash git libxml2-dev && \
                php -v && \
                php -m && \
                composer install && \
                .Build/bin/phpunit -c Build/phpunit/UnitTests.xml --colors
            "
        ;;
    *)
        echo "Invalid -s option argument ${TEST_SUITE}" >&2
        echo >&2
        echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
        exit 1
        ;;
esac
