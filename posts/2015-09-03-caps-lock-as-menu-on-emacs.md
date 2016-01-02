---
title: Using Caps Lock as Menu/Apps key on Emacs
---

I'm an <a href="http://ergoemacs.github.io/">ergoemacs-mode</a> user, a mode that changes most key bindings so that they put less strain on your hands.  For example, it uses Alt instead of Ctrl most of the time, which is easier to press: use your curled thumb instead of a <a href="http://ergoemacs.org/emacs/swap_CapsLock_Ctrl.html">karate chop</a>.  Also, many commands are activated by first pressing the Menu/Apps key (that key near the Right Ctrl which usually opens the context menu).  For example, pressing Menu then T allows you to switch buffers.

However, the keyboard on my new notebook doesn't have a dedicated Menu key.  Instead, one needs to press Fn+Right Ctrl, which is of course extremely painful.

<a class="picture" href="../images/2015-09-menu-key-full.jpg" title="Full size image"><img alt="Picture of menu key on my notebook" src="../images/2015-09-menu-key.jpg" /> Menu key hidden on the Right Ctrl.</a>

I've found a workaround, though.  A very hackish workaround.

<!--more-->

The <a href="http://ergoemacs.github.io/faq.html">ergoemacs-mode FAQ</a> suggests using Caps Lock as a Menu/Apps key for Mac users.  Using xmodmap it's trivial to make Caps Lock a Menu key:
<pre>$ xmodmap -e "keycode 66 = Menu"</pre>
However, using xmodmap properly with Gnome is nigh impossible.  It's recommend to use xkb instead, but xkb doesn't support mapping Caps Lock to the Menu key out-of-the-box (<a href="https://bugs.freedesktop.org/show_bug.cgi?id=91867">at least not yet</a>).  At this point, having wandered through many documentation pages, I've decided to try using some of the xkb options that already exist.

At first I tried setting Caps Lock as the Hyper key.  However, by default the Hyper key gets the same modifier code as the Super key (which is usually the key with the Windows logo).  There's a straightforward way of separating them, but I couldn't find a way to make it play nice with Gnome.  And even if I could, it's not clear to me if I could use the Hyper key as a substitute for the Menu key on emacs.

When ready to admit defeat, I've set the Caps Lock behavior to "Caps Lock is disabled" in preparation of trying a hack using xmodmap.  Much to my surprise, I accidentally discovered that emacs then began treating the disabled Caps Lock key as &lt;VoidSymbol&gt;! The gears started turning in my head, then I added the following line to my ~/.emacs file:
<pre>(define-key key-translation-map (kbd "&lt;VoidSymbol&gt;") (kbd "&lt;menu&gt;"))</pre>
Surprisingly, it worked!  Now pressing Caps Lock then T will switch buffers, for example.  As a bonus, pressing Caps Lock accidentally while on another application won't do anything.

It's not clear to me how fragile this hack really is.  I'll update this blog post if I ever find some drawback to it.  But right now it seems to work quite nicely.

_2016-01-02: Still working fine, didn't have any issues with this hack so far :)._
