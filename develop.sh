#!/bin/sh
set -e
stack build
stack exec -- site rebuild
stack exec -- site watch
