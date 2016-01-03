#!/bin/sh
set -e
stack-nix build
stack-nix exec -- site rebuild
stack-nix exec -- site watch
