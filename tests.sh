#!/bin/sh
# wrapper to peform unit tests
#

ME_ABOUT='wrapper to peform unit tests'
ME_USAGE='[<...OPTIONS>] [--] [<TEST-SUITE>] [<...passthru args>]'
ME_COPYRIGHT='Copyright (c) 2016-2026, Doug Bird. All Rights Reserved.'
ME_NAME='tests.sh'
ME_DIR=$(CDPATH= cd -P "$(dirname "$0")" && pwd -P) || {
   printf '%s: failed to determine app root directory\n' "$ME_NAME" >&2
   exit 1
}

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
   printf '  Hint, try: %s --usage\n' "$ME_NAME"
}

PRINT_COVERAGE=0
HTML_COVERAGE_REPORT=0
SKIP_COVERAGE_REPORT=0
OPTION_STATUS=0
while [ $# -gt 0 ]; do
   case $1 in
      --) shift; break ;;
      -h|-u|-a|--help|--usage|--about) HELP_MODE=1 ;;
      --skip-coverage) SKIP_COVERAGE_REPORT=1 ;;
      --html-coverage) HTML_COVERAGE_REPORT=1 ;;
      --print-coverage|--show-coverage|--coverage) PRINT_COVERAGE=1 ;;
      --*) >&2 printf '%s: unrecognized long option %s\n' "$ME_NAME" "$1"; OPTION_STATUS=$ME_ERROR_USAGE ;;
      -?*) >&2 printf '%s: unrecognized option %s\n' "$ME_NAME" "$1"; OPTION_STATUS=$ME_ERROR_USAGE ;;
      *) break ;;
   esac
   shift
done
[ "$OPTION_STATUS" != "0" ] && { >&2 printf '%s: (FATAL) one or more invalid options\n' "$ME_NAME"; >&2 print_hint; exit "$OPTION_STATUS"; }

if [ "$HELP_MODE" ]; then
   printf '%s\n' "$ME_NAME"
   printf '%s\n' "$ME_ABOUT"
   printf '%s\n' "$ME_COPYRIGHT"
   printf '\n'
   printf 'Usage:\n'
   printf '  %s %s\n' "$ME_NAME" "$ME_USAGE"
   printf '\n'
   printf 'Arguments:\n'
   printf '  <TEST-SUITE>\n'
   printf '  Optionally specify a test suite; otherwise all test suites are performed.\n'
   printf '  Acceptable Values: phpunit\n'
   printf '  Test Suite Descriptions:\n'
   printf '    phpunit: "Unit" phpunit test suite; see phpunit.xml\n'
   printf "       If xdebug is available, a coverage report in text format is (re)generated unless the '--skip-coverage' option is provided.\n"
   printf '       Coverage report path: %s/coverage.txt\n' "$ME_DIR"
   printf '       HTML coverage report dir: %s/.coverage\n' "$HTML_ROOT"
   printf '\n'
   printf 'Options:\n'
   printf '  --skip-coverage\n'
   printf '    Always skip creating coverage reports.\n'
   printf '\n'
   printf '  --html-coverage\n'
   printf "    Creates a coverage report in HTML format in a hidden folder in the project's 'web' directory.\n"
   printf '    Ignored if xdebug is not available.\n'
   printf '\n'
   printf '  --print-coverage\n'
   printf '    Outputs a text coverage report after unit test completion.\n'
   printf '    Ignored if xdebug is not available.\n'
   printf '\n'
   printf 'Exit code meanings:\n'
   printf '    %s: command-line usage error\n' "$ME_ERROR_USAGE"
   printf '    %s: missing required dependency\n' "$ME_ERROR_MISSING_DEP"
   printf '    %s: one or more tests failed\n' "$ME_ERROR_ONE_OR_MORE_TESTS_FAILED"
   exit 0
fi

CDPATH= cd -P "$ME_DIR" || {
   >&2 printf '%s: failed to change to app root directory\n' "$ME_NAME"
   exit 1
}

cmd_status_filter() {
   cmd_status=$1
   case " $CMD_STATUS_DONTUSE " in *" $cmd_status "*) return 1;; esac
   # only re-propagate a status in the range conventionally used for an
   # application's own meaningful exit codes; anything outside 1..125 is
   # shell/signal territory, and POSIX does not guarantee a portable
   # signal-number encoding to unpack there.
   [ "$cmd_status" -ge 1 ] && [ "$cmd_status" -le 125 ] && return "$cmd_status"
   return 1
}

PHPUNIT_STATUS=-1
phpunit_sanity_check() {
   [ "$PHPUNIT_STATUS" != "-1" ] && return $PHPUNIT_STATUS
   if [ ! -f "$PHPUNIT_BIN" ]; then
      >&2 printf "%s: phpunit binary '%s' is missing or inaccessible, have you run composer?\n" "$ME_NAME" "$PHPUNIT_BIN"
      PHPUNIT_STATUS=$ME_ERROR_MISSING_DEP
      return $ME_ERROR_MISSING_DEP
   fi
   if [ ! -x "$PHPUNIT_BIN" ]; then
      >&2 printf "%s: phpunit binary '%s' is not executable\n" "$ME_NAME" "$PHPUNIT_BIN"
      PHPUNIT_STATUS=$ME_ERROR_MISSING_DEP
      return $ME_ERROR_MISSING_DEP
   fi
   PHPUNIT_STATUS=0
}

#
# phpunit wrapper function
#
phpunit() {
   "$PHPUNIT_BIN" "$@" || {
      cmd_status=$?
      >&2 printf '%s: phpunit failed with exit code %s\n' "$ME_NAME" "$cmd_status"
      cmd_status_filter "$cmd_status"
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
      >&2 printf '%s: (NOTICE) xdebug is not available, will skip coverage reports\n' "$ME_NAME"
   }
   return $XDEBUG_STATUS
}

phpunit_coverage_check() {
   [ "$SKIP_COVERAGE_REPORT" = "0" ] || return 1
   xdebug_sanity_check
}

print_phpunit_text_coverage_path() {
   test_suffix=$1
   if [ -z "$test_suffix" ]; then
      printf '%s' 'coverage.txt'
   else
      printf '%s' "coverage-$test_suffix.txt"
   fi
}

print_phpunit_html_coverage_path() {
   test_suffix=$1
   if [ -z "$test_suffix" ]; then
      printf '%s' "$HTML_ROOT/.coverage"
   else
      printf '%s' "$HTML_ROOT/.coverage-$test_suffix"
   fi
}

print_phpunit_coverage_report() {
   test_suffix=$1
   phpunit_coverage_check || return 0
   [ "$PRINT_COVERAGE" = "1" ] || return 0
   coverage_path=$(print_phpunit_text_coverage_path "$test_suffix")
   [ -f "$coverage_path" ] || return 0
   printf '\n%s:\n' "$coverage_path"
   cat "$coverage_path"
}

#
# runs one phpunit suite: sanity-checked already by the caller.
# $1: path to a phpunit config file, or "" for the default phpunit.xml
# $2: coverage-report filename suffix, or "" for none
# remaining args: passed through to phpunit
#
run_phpunit_suite() {
   suite_config=$1
   suite_suffix=$2
   shift 2
   # build phpunit's argv via "set --" (real argument list, not a
   # word-split string) so nothing here breaks on paths containing spaces
   if [ -n "$suite_config" ]; then
      set -- -c "$suite_config" "$@"
   fi
   if phpunit_coverage_check; then
      set -- "--coverage-text=$(print_phpunit_text_coverage_path "$suite_suffix")" "$@"
      if [ "$HTML_COVERAGE_REPORT" = "1" ]; then
         set -- "--coverage-html=$(print_phpunit_html_coverage_path "$suite_suffix")" "$@"
      fi
   fi
   phpunit "$@"
   suite_status=$?
   [ "$suite_status" = "0" ] && print_phpunit_coverage_report "$suite_suffix"
   return $suite_status
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
      run_phpunit_suite "" "" "$@" || {
         cmd_status_filter $?
         exit
      }
      exit 0
   fi
   case $TEST_SUITE in
      phpunit-*)
      TEST_SUITE_XML=${TEST_SUITE%.xml}
      if [ -f "$TEST_SUITE_XML.xml" ]; then
         TEST_SUFFIX=${TEST_SUITE_XML#phpunit-}
         phpunit_sanity_check || exit
         run_phpunit_suite "$TEST_SUITE_XML.xml" "$TEST_SUFFIX" "$@" || {
            cmd_status_filter $?
            exit
         }
         exit 0
      fi
      ;;
   esac

   >&2 printf '%s: (FATAL) unrecognized test suite: %s\n' "$ME_NAME" "$TEST_SUITE"
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

#
# run all phpunit tests
#
run_phpunit_suite "" "" || TESTS_STATUS=$ME_ERROR_ONE_OR_MORE_TESTS_FAILED

for file in phpunit-*.xml; do
   [ -f "$file" ] || continue
   TEST_SUFFIX=${file#phpunit-}
   TEST_SUFFIX=${TEST_SUFFIX%.xml}
   run_phpunit_suite "$file" "$TEST_SUFFIX" || TESTS_STATUS=$ME_ERROR_ONE_OR_MORE_TESTS_FAILED
done

[ "$TESTS_STATUS" -eq "0" ] || {
   >&2 printf '%s: one or more tests failed\n' "$ME_NAME"
   exit $TESTS_STATUS
}
