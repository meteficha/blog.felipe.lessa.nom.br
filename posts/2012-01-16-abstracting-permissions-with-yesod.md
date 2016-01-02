---
title: Abstracting permissions with Yesod
---

<a href="http://www.yesodweb.com/">Yesod</a> is a terrific framework
for web applications in <a href="http://www.haskell.org/">Haskell</a>.
It has many, many built-in features.  One of them is that there's nice
support for authentication and authorization.  In this post I'm
interested in talking about how you could write your authorization
code such that it's harder to make mistakes.

<!--more-->

As shown on the <a
href="http://www.yesodweb.com/blog/2012/01/blog-example">recent
example</a> of creating a blog web app, Yesod's approach to
authorization lies within the `isAuthorized` function:

```haskell
class ... => Yesod a where
  ...
  isAuthorized :: Route a -> Bool -> GHandler s a AuthResult
  ...
```

So `isAuthorized` takes a `Route a`, such as `EntryR 10`, and a `Bool`
telling if the request may do writes (e.g. POST or PUT) or not
(e.g. GET or HEAD).  It must return an `AuthResult` that decides if
the user is `Authorized`, `Unauthorized` or if he needs to be
authenticated first (`AuthenticationRequired`).

While keeping authorization code in a single place is nice, using
`isAuthorized` alone makes it very difficult to test your
authorization code.  Using the <a
href="http://www.yesodweb.com/blog/2012/01/blog-example">blog
example</a> again, only the admin should be able to create blog posts.
So let's take a look at the example code that decides if the user
should be authorized to post to the blog:

```haskell
isAuthorized BlogR True = do
  mauth <- maybeAuth
  case mauth of
    Nothing -> return AuthenticationRequired
    Just (_, user)
      | isAdmin user -> return Authorized
      | otherwise    -> unauthorizedI MsgNotAnAdmin
```

There are many things going on here:

1. There's authentication code that checks if the current user is
   logged in via `maybeAuth`.

2. If the user is not logged in, it returns `AuthenticationRequired`.

3. Otherwise the user's credentials are checked to see if he should be
   authorized or not.


Step 1 is pretty standard housekeeping-style code.  Steps 2 and 3,
however, are part of your business logic that decides who should be
able to do what.  This means that you should be able to create (a) an
unit test that asserts that the admin can create blog posts and (b) an
unit test that asserts that a non-admin can't create blog posts.
Unfortunately, with the code above writing unit tests is really
difficult.  You not only need to artificially run the `GHandler` monad
(boring), but you need to fake the current session so that Step 1's
`maybeAuth` gets the right information (difficult).  Even then, your
`isAuthorized` function is allowed to do anything, since it's inside
`GHandler`.

Enter permissions.  What we're really trying to say in the code above
is that (a) to create a blog post you need the "post" permission and
(b) admin has "post" permission, non-admins don't.  So let's split
these things!  First of all, we need a list of the permissions that
we'll need:

```haskell
data Permission = Post | Comment
```

You should read these constructors names with "permission to" before
their names.  For example, the admin has permission to `Post` and any
logged user has permission to `Comment`.

Each request needs to decide which permissions it requires.  This is
one of the most important pieces of your application's security, since
forgetting to ask for permissions could lead to catastrophic problems.
Instead of having this core piece of your app diluted in
`isAuthorized`, we use a simple, clear, pure function called
`permissionsRequiredFor`.  The idea is to make
`permissionsRequiredFor` as simple as possible, such that with code
review alone you could determine if you're asking for the right
permissions.

```haskell
permissionsRequiredFor :: Route Blog -> Bool -> [Permission]
permissionsRequiredFor BlogR      True = [Post]
permissionsRequiredFor (EntryR _) True = [Comment]
permissionsRequiredFor _          _    = []
```

We also need to decide if the currently logged user has the necessary
permissions or not.  This is the other important piece of your
authorization puzzle, and a piece that we need to make easily
testable.  In order to do so, we avoid `maybeAuth` and take the user
as an argument.

```haskell
hasPermissionTo :: (UserId, User)
                -> Permission
                -> YesodDB sub Blog AuthResult
(_, user) `hasPermissionTo` Post
  | isAdmin user = return Authorized
  | otherwise    = lift $ unauthorizedI MsgNotAnAdmin
_ `hasPermissionTo` Comment = return Authorized


isAuthorizedTo :: Maybe (UserId, User)
               -> [Permission]
               -> YesodDB sub Blog AuthResult
_       `isAuthorizedTo` []     = return Authorized
Nothing `isAuthorizedTo` (_:_)  = return AuthenticationRequired
Just u  `isAuthorizedTo` (p:ps) = do
  r <- u `hasPermissionTo` p
  case r of
    Authorized -> Just u `isAuthorizedTo` ps
    _          -> return r -- unauthorized
```

The `hasPermissionTo` function decides if the user has a given
permission or not.  While in this example `hasPermissionTo` could have
been a pure function, in general you may need to access the database.
The `isAuthorizedTo` function then (a) decides if the user needs to be
authenticated and (b) checks all permissions required from the list
using `hasPermissionTo`.

Finally, we need to implement `isAuthorized` gluing everything
together:

```haskell
isAuthorized route isWrite = do
  mauth <- maybeAuth
  runDB $ mauth `isAuthorizedTo` permissionsRequiredFor route isWrite
```

Ok, so what did we gain by writing three more functions?

* You can easily review `permissionsRequiredFor` to see if you didn't
  leave a restricted route in the open.

* If you don't use the wildcards on the last line of
  `permissionsRequiredFor` and instead list all of your routes one by
  one, then you'd get a compiler warning and a runtime error every
  time you forgot to set the permissions of a newly added route.

* If you have many routes that needed the same permissions, you don't
  need to recode the permission code everywhere.  You just need to
  code it once on `hasPermissionTo` and then ask for that permission
  on each of your routes. In my experience, the set of permissions
  (i.e. the `Permission` data type) is a lot smaller than the set of
  possible routes.

* You may easily create unit tests for `hasPermissionTo`, increasing
  your confidence on your code's correctness.

I should also note that this approach is easily extendable.  For
example, suppose that you wanted to restrict the visibility of some of
your blog posts.  You could change the `Permission` data type into:

```haskell
data Permission = Post | CommentOn EntryId | View EntryId
```

Now your `hasPermissionTo` function is able to give a different answer
depending on which blog post we're talking about.

So far this approach has been successfully used on my day job's Yesod
application.  It looks like a cousin of Yesod's i18n support using
data types.

Thanks for reading along this far!  Please use the comment section
below to say what think of this approach. =)
