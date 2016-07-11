#!/bin/sh
set -e
stack build
stack exec -- site rebuild
stack exec -- site watch --host=0.0.0.0 --port=8000
