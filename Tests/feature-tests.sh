#!/bin/sh

# heu... didn't find a better way
DRY_RUN=false
if [ -n $1 ];
then
	if [ $1 == '--dry-run' ];
	then
		DRY_RUN=true
	fi
fi

# Get path to build
RESOURCES_PATH=$(cd `dirname "${BASH_SOURCE[0]}"` && pwd)/Resources
PHAR_PATH=$(cd `dirname "${BASH_SOURCE[0]}"` && pwd)/../Build

tests=(
	"php ${PHAR_PATH}/get-the-docs.phar"
	"php ${PHAR_PATH}/get-the-docs.phar render ${RESOURCES_PATH}/TestingPackage"
	"php ${PHAR_PATH}/get-the-docs.phar render ${RESOURCES_PATH}/TestingPackage/Documentation"
	"php ${PHAR_PATH}/get-the-docs.phar render --html --json --gettext --epub --pdf --zip ${RESOURCES_PATH}/TestingPackage"
	"php ${PHAR_PATH}/get-the-docs.phar render --html --json --gettext --epub --pdf --zip ${RESOURCES_PATH}/TestingPackage/Documentation"
	"php ${PHAR_PATH}/get-the-docs.phar convert ${RESOURCES_PATH}/manual.sxw"
)

echo $DRY_RUN
for test in "${tests[@]}"
do
	if [ $DRY_RUN == true ];
	then
		echo ${test}
	else
		echo "Command executed:"
		echo ${test}
		echo ""
		echo "Result:"
		$test
		echo "----------------------------------------"
	fi
done


