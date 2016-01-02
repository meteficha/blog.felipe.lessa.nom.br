---
title: Announcing serversession
---

I'm pleased to announce the <a href="https://github.com/yesodweb/serversession" title="serversession's GitHub page"><strong>serversession family of packages</strong></a>.

<h2> Background</h2>

HTTP is a stateless protocol.  Cookies are used to create <strong>sessions</strong> out of otherwise independent requests made by the browser to the server.  There are many ways of managing sessions via cookies, but they can be mostly separated into two big camps:

<dl>
  <dt>Client-side sessions</dt>
  <dd>The cookie data contains the session data.  For example, it could contain a shopper's login and cart contents.</dd>
  <dt>Server-side sessions</dt>
  <dd>The cookie data contains a session identifier, and the session data is kept on a database indexed by the session identifiers.</dd>
</dl>

<!--more-->

The <a href="http://hackage.haskell.org/package/clientsession" title="clientsession package on Hackage">clientsession</a> package has existed for many years already.  It's both <a href="http://hackage.haskell.org/package/yesod-core-1.4.18.1/docs/Yesod-Core.html#v:makeSessionBackend" title="Yesod.Core">Yesod</a>'s and <a href="http://hackage.haskell.org/package/snap-0.14.0.6/docs/Snap-Snaplet-Session-Backends-CookieSession.html" title="Snap.Snaplet.Session.Backends.CookieSession">Snap</a>'s de facto session backend.  It performs well and is battle tested by many different people and companies.

However, there are many reasons why one may want to favor server-side sessions over client-side ones:

<ul>
  <li>Saving <strong>arbitrary amounts of session data</strong> without being constrained by cookie size limits.  Even if your data fits on a cookie, you may want to spare having to bounce all that data around on every request and response.</li>
  <li><strong>Securely invalidating a session</strong>.  User logout with client-side sessions erases the cookie from the user's browser but doesn't invalidate its data.  The old cookie is still valid until its expiration.  Server-side sessions, however, can be securely invalidate by simply erasing them from your session database.  This can be a critical feature depending on your needs.</li>
</ul>

The biggest disadvantage of server-side sessions is that they need more server-side resources.  Not only it needs space for sessions storage, but it also incurs the overhead of zero to two DB transactions per HTTP request.

One solution isn't inherently better than the other.  It all depends on your use case.  And if your use case needs server-side sessions, this is your lucky day.

<h2> The serversession packages </h2>

I'd like to fill the gap that currently exists on the Haskell ecosystem with respect to server-side session support.  Preferably once and for all.  That's why there are many serversession packages.

The main one, aptly called <a href="https://hackage.haskell.org/package/serversession" title="serversession's page on Hackage"><strong>serversession</strong></a>, contains the core logic about server-side sessions.  It's abstracted over a <strong>backend</strong> which will store the session's data (usually a database).  And it's meant to be used by a <strong>frontend</strong>, such as Yesod or Snap.  Besides having a nice <a href="https://travis-ci.org/yesodweb/serversession" title="serversession's page on Travis-CI">test suite</a>, it's also unencumbered by the minutia of dealing with databases and thus is easier to review.  It also defines a standard test suite that every backend needs to pass.

Out-of-the-box you'll find support for many different backends:

<ul>
<li>The <a href="https://hackage.haskell.org/package/serversession-backend-persistent" title="serversession-backend-persistent's page on Hackage"><strong>serversession-backend-persistent</strong></a> package allows one to use any SQL database that persistent supports, including PostgreSQL, MySQL, and SQLite.</li>
<li>Through the <a href="https://hackage.haskell.org/package/serversession-backend-acid-state" title="serversession-backend-acid-state's page on Hackage"><strong>serversession-backend-acid-state</strong></a> package you may use <a href="https://hackage.haskell.org/package/acid-state" title="acid-state's page on Hackage">acid-state</a>. This backend keeps sessions in memory but provides ACID guarantees using a transaction log.</li>
<li>We also support Redis via the <a href="https://hackage.haskell.org/package/serversession-backend-redis" title="serversession-backend-redis's page on Hackage"><strong>serversession-backend-redis</strong></a> package.</li>
</ul>

We also already officially support the most popular frontends:

<ul>
<li>Yesod, through the <a href="https://hackage.haskell.org/package/serversession-frontend-yesod" title="serversession-frontend-yesod's page on Hackage"><strong>serversession-frontend-yesod</strong></a> package.  Provides a drop-in replacement for clientsession. </li>
<li>Snap, through the <a href="https://hackage.haskell.org/package/serversession-frontend-snap" title="serversession-frontend-snap's page on Hackage"><strong>serversession-frontend-snap</strong></a> package.  Also provides a drop-in replacement for clientsession. </li>
<li>Even plain WAI apps, through the <a href="https://hackage.haskell.org/package/serversession-frontend-wai" title="serversession-frontend-wai's page on Hackage"><strong>serversession-frontend-wai</strong></a> package.
</ul>

Adding a new backend is very straightforward, specially because there's already a test suite for free.  Adding a new frontend is a bit more complicated depending on how well your frontend's concept of sessions maps to serversession's.  If you'd like to support your favorite backend/frontend, please send your contributions back upstream so they become official packages as well!

<h2> Usage example </h2>

If you have an Yesod app, you're probably using persistent.  Changing your app to support serversession is just a matter of setting up the session storage entities and changing Yesod's default session backend (not to be confused with a serversession backend).

To setup the entities, you'll have to teach persistent how to set them up on your database.  Please check <a href="http://www.stackage.org/package/serversession-backend-persistent" title="serversession-backend-persistent's page on Stackage">serversession-backend-persistent's docs</a> for details, but it all boils down to changing your migration Template Haskell code from:

```haskell
-- On Model.hs
share [mkPersist sqlSettings, mkMigrate "migrateAll"]
```

to:

```haskell
-- On Model.hs
share [mkPersist sqlSettings, mkSave "entityDefs"]

-- On Application.hs
mkMigrate "migrateAll" (serverSessionDefs (Proxy :: Proxy SessionMap) ++ entityDefs)
```

Changing the default session backend is even easier.  Again, please read <a href="http://www.stackage.org/package/serversession-frontend-yesod" title="serversession-frontend-yesod's page on Stackage">the docs</a>, but you'll just add the following to your `instance Yesod App`:

```haskell
    makeSessionBackend = simpleBackend id . SqlStorage . appConnPool
```

And you're set!  Please take a look at the <a href="https://github.com/yesodweb/serversession/tree/master/examples/serversession-example-yesod-persistent" title="Example project using serversession with Yesod and persistent on GitHub">included example project</a> to see how everything fits together.

<h2>Final words</h2>

One of clientsession's biggest success was in being used by many different projects.  This is desirable both for reducing duplicate community efforts and for increasing the number of eyeballs over a security-related piece of code.  I'd like to make serversession equally successful in the same way, which is why it supports from the get-go both the major Haskell web frameworks that today use clientsession by default.

I'd like to invite your criticism and feedback.  And your success stories as well!

Happy New Year!
