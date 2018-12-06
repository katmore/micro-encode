#!/bin/sh
# wrapper to peform unit tests
#

ME_ABOUT='wrapper to peform unit tests'
ME_USAGE='[<...OPTIONS>] [<TEST-SUITE>] [[--]<...passthru args>]'
ME_COPYRIGHT='Copyright (c) 2016-2018, Doug Bird. All Rights Reserved.'
ME_NAME='tests.sh'
ME_DIR="/$0"; ME_DIR=${ME_DIR%/*}; ME_DIR=${ME_DIR:-.}; ME_DIR=${ME_DIR#/}/; ME_DIR=$(cd "$ME_DIR"; pwd)
ME_SOURCE="$ME_DIR/$0"

#
# paths
#
HTML_ROOT=$ME_DIR/web
PHPUNIT_BIN=$ME_DIR/vendor/bin/phpunit
PHPUNIT_TESTS_ROOT=$ME_DIR/tests

#
# exit codes
#
ME_ERROR_USAGE=2
ME_ERROR_ONE_OR_MORE_TESTS_FAILED=4
ME_ERROR_MISSING_DEP=3

CMD_STATUS_DONTUSE="255 $ME_ERROR_USAGE $ME_ERROR_ONE_OR_MORE_TESTS_FAILED $ME_ERROR_MISSING_DEP"

print_hint() {
	echo "  Hint, try: $ME_NAME --usage"
}

PRINT_COVERAGE=0
HTML_COVERAGE_REPORT=0
SKIP_COVERAGE_REPORT=0
OPTION_STATUS=0
while getopts :?qhua-: arg; do { case $arg in
   h|u|a) HELP_MODE=1;;
   -) LONG_OPTARG="${OPTARG#*=}"; case $OPTARG in
      help|usage|about) HELP_MODE=1;;
      skip-coverage) SKIP_COVERAGE_REPORT=1;;
      html-coverage) HTML_COVERAGE_REPORT=1;;
      print-coverage) PRINT_COVERAGE=1;;
      show-coverage) PRINT_COVERAGE=1;;
      coverage) PRINT_COVERAGE=1;;
      *) >&2 echo "$ME_NAME: unrecognized long option --$OPTARG"; OPTION_STATUS=$ME_ERROR_USAGE;;
   esac ;; 
   *) >&2 echo "$ME_NAME: unrecognized option -$OPTARG"; OPTION_STATUS=$ME_ERROR_USAGE;;
esac } done
shift $((OPTIND-1)) # remove parsed options and args from $@ list
[ "$OPTION_STATUS" != "0" ] && { >&2 echo "$ME_NAME: (FATAL) one or more invalid options"; >&2 print_hint; exit $OPTION_STATUS; }

if [ "$HELP_MODE" ]; then
   echo "$ME_NAME"
   echo "$ME_ABOUT"
   echo "$ME_COPYRIGHT"
   echo ""
   echo "Usage:"
   echo "  $ME_NAME $ME_USAGE"
   echo ""
   echo "Arguments:"
   echo "  <TEST-SUITE>"
   echo "  Optionally specify a test suite; otherwise all test suites are performed."
   echo "  Acceptable Values: phpunit"
   echo "  Test Suite Descriptions:"
   echo "    phpunit: \"Unit\" phpunit test suite; see phpunit.xml"
   echo "       If xdebug is available, a coverage report in text format is (re)generated unless the '--skip-coverage' option is provided."
   echo "       Coverage report path: $ME_DIR/coverage.txt"
   echo "       HTML coverage report dir: $HTML_ROOT/.coverage"
   echo ""
   echo "Options:"
   echo "  --skip-coverage"
   echo "    Always skip creating coverage reports."
   echo ""
   echo "  --html-coverage"
   echo "    Creates a coverage report in HTML format in a hidden folder in the project's 'web' directory."
   echo "    Ignored if xdebug is not available."
   echo ""
   echo "  --print-coverage"
   echo "    Outputs a text coverage report after unit test completion."
   echo "    Ignored if xdebug is not available."
   echo ""
   echo "Exit code meanings:"
   echo "    $ME_ERROR_USAGE: command-line usage error"
   echo "    $ME_ERROR_MISSING_DEP: missing required dependency"
   echo "    $ME_ERROR_ONE_OR_MORE_TESTS_FAILED: one or more tests failed"
   exit 0
fi

if [ "$ME_DIR" != "$(pwd)" ]; then
  cd $ME_DIR || {
     >&2 echo "$ME_NAME: failed to change to app root directory"
     exit 1
  }
fi

cmd_status_filter() {
   cmd_status=$1
   ! [ "$cmd_status" -eq "$cmd_status" ] 2> /dev/null && return 1
   test "${CMD_STATUS_DONTUSE#*$cmd_status}" != "$CMD_STATUS_DONTUSE" && return 1
   ( [ "$cmd_status" -lt "126" ] || [ "$cmd_status" -gt "165" ] ) && return $cmd_status
   return 1
}

PHPUNIT_STATUS=-1
phpunit_sanity_check() {
	 [ "$PHPUNIT_STATUS" != "-1" ] && return $PHPUNIT_STATUS
   if [ ! -f "$PHPUNIT_BIN" ]; then
      >&2 echo "$ME_NAME: phpunit binary '$PHPUNIT_BIN' is missing or inaccessible, have you run composer?"
      PHPUNIT_STATUS=$ME_ERROR_MISSING_DEP
      return $ME_ERROR_MISSING_DEP
   fi
   if [ ! -x "$PHPUNIT_BIN" ]; then
      >&2 echo "$ME_NAME: phpunit binary '$PHPUNIT_BIN' is not executable"
      PHPUNIT_STATUS=$ME_ERROR_MISSING_DEP
      return $ME_ERROR_MISSING_DEP
   fi
   PHPUNIT_STATUS=0
}

#
# phpunit wrapper function
#
phpunit() {
   $PHPUNIT_BIN "$@" || {
      cmd_status=$?
      >&2 echo "$ME_NAME: phpunit failed with exit code $cmd_status"
      cmd_status_filter $cmd_status
      return
   }
   return 0
}

XDEBUG_STATUS=-1
xdebug_sanity_check() {
	 [ "$XDEBUG_STATUS" != "-1" ] && return $XDEBUG_STATUS
	 php -m 2> /dev/null | grep xdebug > /dev/null 2>&1
	 XDEBUG_STATUS=$?
	 [ "$XDEBUG_STATUS" = "0" ] || {
	 	  >&2 echo "$ME_NAME: (NOTICE) xdebug is not available, will skip coverage reports"
	 }
	 return $XDEBUG_STATUS
}

phpunit_coverage_check() {
	[ "$SKIP_COVERAGE_REPORT" = "0" ] || return 0
   xdebug_sanity_check || return 0
}

print_phpunit_text_coverage_path() {
	 local test_suffix=$1
	 if [ -z "$test_suffix" ]; then
	 	  printf "coverage.txt"
 	 else
 	    printf "coverage-$test_suffix.txt"
 	 fi
}

print_phpunit_html_coverage_path() {
	 local test_suffix=$1
	 if [ -z "$test_suffix" ]; then
	 	  printf "$HTML_ROOT/.coverage"
 	 else
 	    printf "$HTML_ROOT/.coverage-$test_suffix"
 	 fi
}

print_phpunit_coverage_opt() {
	 local test_suffix=$1
	 [ "$SKIP_COVERAGE_REPORT" = "0" ] || return 0
   xdebug_sanity_check || return 0
   if [ "$HTML_COVERAGE_REPORT" = "1" ]; then
   	 printf " --coverage-html=$(print_phpunit_html_coverage_path $test_suffix) "
   fi
	 printf " --coverage-text=$(print_phpunit_text_coverage_path $test_suffix) "
}

print_phpunit_coverage_report() {
	 local test_suffix=$1
	 phpunit_coverage_check || return 0
	 [ "$PRINT_COVERAGE" = "1" ] || return 0
	 [ -f "$(print_phpunit_text_coverage_path $test_suffix)" ] || return 0
	 printf "\n$(print_phpunit_text_coverage_path):\n"
	 cat $(print_phpunit_text_coverage_path)
}

TEST_SUITE=$1

#
# determine if wrapper mode specified by TEST_SUITE
#
if [ -n "$TEST_SUITE" ]; then
   shift
   #
   # apply phpunit wrapper mode
   #
   if [ "$TEST_SUITE" = "phpunit" ]; then
      phpunit_sanity_check || exit
      phpunit $(print_phpunit_coverage_opt) "$@" || {
      	 cmd_status_filter $?
      	 exit
      }
      print_phpunit_coverage_report
      exit 0
   fi
   case $TEST_SUITE in
      phpunit-*)
      if [ -f "$TEST_SUITE.xml" ]; then
      	 TEST_SUFFIX=$(echo $file | sed -e 's/phpunit-//g')
   	     TEST_SUFFIX=$(echo $TEST_SUFFIX | sed -e 's/.xml//g')
         phpunit_sanity_check || exit
         phpunit $(print_phpunit_coverage_opt $TEST_SUFFIX) -c "$TEST_SUITE.xml" "$@" || {
      	    cmd_status_filter $?
      	    exit
     	   }
     	   print_phpunit_coverage_report $TEST_SUFFIX
         exit 0
      fi
      ;; 
   esac

   >&2 echo "$ME_NAME: (FATAL) unrecognized test suite: $TEST_SUITE"
   >&2 print_hint
   exit $ME_ERROR_USAGE
fi

#
# no TEST_SUITE specified: perform ALL tests
#

#
# sanity check test commands
#
phpunit_sanity_check || exit

#
# tests status
#
TESTS_STATUS=0

echo "print_phpunit_coverage_opt: $(print_phpunit_coverage_opt)"
#
# run all phpunit tests
#
phpunit $(print_phpunit_coverage_opt)
CMD_STATUS=$?
if [ "$CMD_STATUS" = "0" ]; then
	 print_phpunit_coverage_report
else
  TESTS_STATUS=$ME_ERROR_ONE_OR_MORE_TESTS_FAILED
fi

for file in phpunit-*.xml; do
   [ -f "$file" ] || continue
   TEST_SUFFIX=$(echo $file | sed -e 's/phpunit-//g')
   TEST_SUFFIX=$(echo $TEST_SUFFIX | sed -e 's/.xml//g')
   phpunit $(print_phpunit_coverage_opt $TEST_SUFFIX) -c $(basename $file)
   CMD_STATUS=$?
   if [ "$CMD_STATUS" = "0" ]; then
  	  print_phpunit_coverage_report $TEST_SUFFIX
   else
      TESTS_STATUS=$ME_ERROR_ONE_OR_MORE_TESTS_FAILED
   fi
done



[ "$TESTS_STATUS" -eq "0" ] || {
   >&2 echo "$ME_NAME: one or more tests failed"
   exit $TESTS_STATUS
}