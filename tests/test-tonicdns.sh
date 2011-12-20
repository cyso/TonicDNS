#!/bin/bash
phpunit --coverage-html tonicdns/coverage --log-junit tonicdns/results/result.xml --coverage-clover tonicdns/coverage/coverage.xml tonicdns/
