#!/bin/bash
cd ..;
phpunit --coverage-html tests/tonicdns/coverage --log-junit tests/tonicdns/results/result.xml --coverage-clover tests/tonicdns/coverage/coverage.xml tests/tonicdns/
