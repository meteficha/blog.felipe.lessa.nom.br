---
title: Using stack on NixOS
---

I've got myself a new laptop recently and decided to try NixOS.  It's
been a great experience so far, but there are some rough edges.  One
of them is stack not completely working out of the box for my
projects.

<!--more-->

The reason is that some Haskell packages depend on system C libraries,
but neither stack nor Cabal-the-library are able to find them on
NixOS.  As an example, you won't find `/usr/lib/libz.so` on my system.
Instead, right now it's at
`/nix/store/2zmlykvqx69q5bh1l3jqyhrj2493vqdx-zlib-1.2.8/lib/libz.so`.

Being a NixOS newbie, I've tried some solutions but none of them
worked.  I've then asked for Peter Simon's help, which he gladly and
swiftly provided (thanks, Peter!).  For my use case, I've adapted his
suggestions into the following script:

```bash
#!/usr/bin/env bash
ZLIB="$(nix-build --no-out-link "<nixpkgs>" -A zlib)"
PSQL="$(nix-build --no-out-link "<nixpkgs>" -A postgresql)"
exec stack                                                             \
     --extra-lib-dirs=${ZLIB}/lib --extra-include-dirs=${ZLIB}/include \
     --extra-lib-dirs=${PSQL}/lib --extra-include-dirs=${PSQL}/include \
     $*
```

My transitive dependencies need zlib and postgresql libraries, so I
use nix-build to find out where these packages are and pass their
directories to stack explicitly.

This solution is not without drawbacks.  The biggest one is that your
built Haskell libraries will be hardcoded to these C libraries, but
NixOS doesn't know anything about this dependency.  If you upgrade
your system and garbage collect the old C libraries, you'll have to
recompile the Haskell libraries (probably with `rm -R
~/.stack/snapshots`).  However, I quite like its conciseness, and one
doesn't need to understand much about NixOS's internals to use it.

At the moment this hack is serving me well.  If you're reading this
blog post more than a couple of months after I wrote it, take a look
around to see if a better solution has been developed in the mean time :).
