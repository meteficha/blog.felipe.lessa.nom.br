---
title: Announcing esqueleto, a type-safe EDSL for SQL queries
---

I'm very pleased to announce a preview release of <a
href="http://hackage.haskell.org/package/esqueleto">esqueleto</a>, a
bare bones, type-safe EDSL for SQL queries.

<!--more-->

On the first part of this blog post I'll talk about persistent and
HaskellDB.  You may jump <a href="#esqueleto">right into where I talk
about esqueleto</a> if you want, though.

## Background

<a href="http://www.yesodweb.com/">Yesod</a> is very modular, and each
of its components may be used separately.  You may also take libraries
from other web frameworks.  However, it's more convenient to use Yesod
with its standard set of libraries.

One of those libraries is <a
href="http://hackage.haskell.org/package/persistent">persistent</a>. It
fills the role of communicating with the database: serialization and
deserialization of data types, insertions, updates, queries, schema
migration, and so on.  It currently has both SQL and NoSQL backends,
such as <a href="http://hackage.haskell.org/package/persistent-mysql">persistent-mysql</a>,
<a href="http://hackage.haskell.org/package/persistent-postgresql">persistent-postgresql</a>,
<a href="http://hackage.haskell.org/package/persistent-sqlite">persistent-sqlite</a>
and <a href="http://hackage.haskell.org/package/persistent-mongoDB">persistent-mongoDB</a>.

## Persistent's weakness

Even though persistent's use of Template Haskell has been criticized
by some, it's almost consensual that its largest drawback lies on its
query API.  For example, if you want to see all of John's posts, and
you know that John's key on the database is
`johnId`, then you may write:

```haskell
posts <- selectList [BlogPostAuthorId ==. johnId] []
```

Even though the meaning of those lists is somewhat cryptic, it's not a
bad line of code.  However, what if you didn't know John's key, just
its e-mail (assuming that there is an uniqueness constraint on
e-mails)?  Unfortunately, with persistent you'll need either two
queries:

```haskell
Entity johnId john <- getBy (UniqueEmail john'sEmail)
posts <- selectList [BlogPostAuthorId ==. johnId] []
```

Or you could do it in one query using the ad hoc <a
href="http://hackage.haskell.org/packages/archive/persistent/1.0.1/doc/html/Database-Persist-Query-Join-Sql.html">Database.Persist.Query.Join.Sql</a>
module:

```haskell
runJoin $ SelectOneMany
            { somFilterOne      = [ PersonEmail ==. john'sEmail ]
            , somOrderOne       = []
            , somFilterMany     = []
            , somOrderMany      = []
            , somFilterKeys     = (BlogPostAuthorId <-.)
            , somGetKey         = blogPostAuthorId
            , somIncludeNoMatch = False
            }
```

Compare to the equivalent SQL query:

```sql
SELECT BlogPost.*
FROM Person INNER JOIN BlogPost
ON Person.id = BlogPost.authorId
WHERE Person.email = ?
```

But it gets even worse: you have support only for simple queries
(using `selectList`) and for simple one-to-many joins (using the ad
hoc `SelectOneMany`).  If you need anything else then you're on your
own.  For instance, there's no support for doing many-to-many joins.

For these reasons I've created the <a
href="http://hackage.haskell.org/packages/archive/persistent/1.0.1/doc/html/Database-Persist-GenericSql.html#v:rawSql">`rawSql`</a>
function <a href="https://github.com/yesodweb/persistent/pull/34">8
month ago</a>.  It was nice to be able to write raw SQL queries and
still be able to use persistent to deserialize the results, but we
still get all the drawbacks of using raw SQL queries:

* No compile-time checks whatsoever.  You could alleviate this problem
  using <a href="http://hackage.haskell.org/package/hssqlppp">hssqlppp</a>, for
  example, but you'd still not get compile-time checks for the types
  of your entities---there would still be plenty of ways of shooting
  yourself in the foot.

* No composability.  Suppose you have a query for people and their
  latest blog post.  You can't reuse this query to reorder the results
  or filter them.

* Zero coolness factor.  C'mon, we're not using Haskell for nothing! =)


## HaskellDB: a solution?

<a href="http://hackage.haskell.org/package/haskelldb">HaskellDB</a>
is a type-safe EDSL that allows you to write SQL queries using
relational algebra.  It's as old as Parsec, having been introduced in
1999!

Recently some people have been showing interest in using it with
Yesod.  Last month <a
href="https://groups.google.com/d/msg/yesodweb/nQjQuwydfI8/vB9mM47uZ0AJ">Mats
Rauhala</a> wrote the following summary about his opinion at the time
on Yesod's mailing list:

> 1. Direct sql<br>
>         - No, or very little type safety<br>
>         - No, or very little compile time checks<br>
>         - Raw queries are ugly<br>
>         + Full power of SQL
>
> 2. ORM<br>
>         + Type safety (ala persistent)<br>
>         - No control over queries<br>
>         - No proper join support(?, persistent)<br>
>         + Abstract<br>
>         +- High-level
>
> 3. Relational algebra<br>
>         + Type safety (ala HaskellDB)<br>
>         + Great control over queries<br>
>         + Good control over abstractions<br>
>         + It's algebra, therefore fits Haskell (tongue in cheek)<br>
>         - Forgotten (HaskellDB)

Although it's rather painful to setup HaskellDB's tables, I'll won't
count that as a big drawback since some Template Haskell could
certainly solve this shortcoming.  Its lack of migration capabilities
is not a huge problem, too, since you may use persistent just for the
migrations (although I'm not sure if anyone has ever put something
like this into production).

HaskellDB's biggest drawback is being a relational algebra library.  "What?", I hear you say.  I like relational algebra as much as the next functional programmer, but it comes with two drawbacks, one of them being a major one:

1. Being something different than what we're used to means that it
   takes some time to learn how to use it and get productive.  This is
   a minor drawback, but nevertheless it is a drawback.

2. However, the biggest drawback is that <strong>it's very hard to map
   into efficient SQL</strong>.  Back in 2008, Geoff Wilson <a
   href="http://pseudofish.com/blog/2008/05/18/haskelldb-performance/">wrote
   a blog post about HaskellDB's performance</a>.  A simple
   `INNER JOIN` was taking between 40x and
   <strong>160x</strong> more time to execute when using HaskellDB and
   comparing against a handwritten SQL query.

While I'm sure that some work has been done on HaskellDB's optimizer
since that blog post, Chris Done <a
href="http://chrisdone.com/posts/2011-11-06-haskelldb-tutorial.html">found
out last November</a> that it still isn't very good:

> Don’t expect good performance from HaskellDB if you’re using MySQL.

Even if I started using PostgreSQL, I wouldn't want to rely on its
optimizer when doing, say, a join between five tables on HaskellDB.
(If you didn't already know, I'm <a
href="http://hackage.haskell.org/package/persistent-mysql">persistent-mysql</a>'s
author.)

Please don't get me wrong, HaskellDB is amazing!  But it won't work
for my production systems.


<a name="esqueleto"></a>

## Esqueleto rises

Last Sunday my co-worker was bitten by a nasty bug due to a raw SQL
query.  He changed an entity's field so that it would be optional.
After fixing all type errors, he found out that some parts of the
application were not working, but no error messages were to be found
anywhere.  Turns out an implicit join in a raw SQL query started
dropping rows from the result since the value was `NULL`.  After we
found the bug, he proceeded to show me <a
href="http://squeryl.org/">Squeryl</a>. My initial thought after
seeing the examples was: how could I write this in Haskell?

Thus <a href="http://hackage.haskell.org/package/esqueleto">esqueleto</a> was
born.  It's a bare bones, type-safe EDSL for SQL-queries. Like
HaskellDB, it has composable, type-checked queries.  Unlike HaskellDB,
it's not relational algebra, it's SQL.  I was inspired by Squeryl but
created esqueleto from scratch.

It sits on top of persistent and requires no further setup: if you're
already using persistent then you already have everything it takes to
use esqueleto.  Although I haven't tested, yet, it should work on any
SQL backend.

Let's remember the first query we did on the beginning of this post:

```haskell
selectList [BlogPostAuthorId ==. johnId] []
```

You could write the same query in plain SQL as:

```sql
SELECT BlogPost.*
FROM BlogPost
WHERE BlogPost.authorId = ?
```

With esqueleto, you may say:

```haskell
select $
from $ \b -> do
where_ (b ^. BlogPostAuthorId ==. val johnId)
return b
```

Arguably more verbose than persistent's `selectList`, but extremely
close to the handwriten SQL: just take the `return` and move it to the
top!

Better still, the SQL that esqueleto generates is:

```sql
SELECT BlogPost.id, BlogPost.title
FROM BlogPost
WHERE BlogPost.authorId = ?
```

I'm not kidding!  The only difference from the handwritten query is
the explicit list of columns, which is needed for correctness (the
order your database returns the columns may not be what persistent
expects).

Remember the one-to-many join?  Here's the esqueleto version:

```haskell
select $
from $ \(p `InnerJoin` b) -> do
on (p ^. PersonId ==. b ^. BlogPostAuthorId)
where_ (p ^. PersonEmail ==. val john'sEmail)
return b
```

The SQL esqueleto generates for this query is:

```sql
SELECT BlogPost.id, BlogPost.title
FROM Person INNER JOIN BlogPost
ON Person.id = BlogPost.authorId
WHERE Person.email = ?
```

Let's take <a href="http://pseudofish.com/blog/2008/05/18/haskelldb-performance/">Geoff
Wilson's post</a> as an example again.  Since this is a preview
release of esqueleto, it does not have support for `IN` yet, so I'll
rewrite his code slightly. The HaskellDB code he wrote was:

```haskell
query db $ do
  s <- table S.stock
  e <- table E.end_of_day
  restrict (s!S.stock_id .==. e!E.stock_id .&&.
            s!S.ticker .==. constant ticker .&&.
            e!E.trade_date .==. constant stockDate)
  r <- project (closing_price << e!E.closing_price #
                trade_date    << e!E.trade_date)
  return r
```

The SQL that HaskellDB generated on 2008 was (adapted):

```sql
SELECT closing_price2 as closing_price,
       trade_date2 as trade_date
FROM (SELECT stock_id as stock_id2,
             trade_date as trade_date2,
             closing_price as closing_price2
      FROM end_of_day as T1) as T1,
     (SELECT stock_id as stock_id1,
             ticker as ticker1
      FROM stock as T1) as T2
WHERE stock_id1 = stock_id2
  AND ticker1 = 'FXJ'
  AND trade_date2 = '2008-02-14 00:00:00';
```

His handwritten SQL was (adapted as well):

```sql
SELECT e.closing_price as closing_price,
       e.trade_date as trade_date
FROM
    stock s, end_of_day e
WHERE
    s.stock_id = e.stock_id
    AND s.ticker = 'FXJ'
    AND e.trade_date = '2008-02-14 00:00:00';
```

The same query could be written using persistent and esqueleto as:

```haskell
select $
from $ \(s, e) -> do
where_ (s ^. StockId ==. e ^. EndOfDayStockId &&.
        s ^. StockTicker ==. val ticker &&.
        s ^. EndOfDayTradeDate ==. val stockDate)
return (e ^. EndOfDayClosingPrice, e ^. EndOfDayTradeDate)
```

The generated SQL would be:

```sql
SELECT end_of_day.closing_price, end_of_day.trade_date
FROM stock, end_of_day
WHERE stock.stock_id = end_of_day.stock_id AND (stock.ticker = ? AND end_of_day.trade_date = ?)
```


## Conclusion

The full power of raw SQL.  Type-checked queries, no type signatures
required.  Complete control over the resulting SQL.  The robustness
and performance of persistent.  And with only 800 source lines of code
(+ 400 SLOC for the test suite).  What's not to like about esqueleto?
=D

This is just a preview release.  I'm eager to hear what you have to
say about it.  Send pull requests, open issues, comment about it
<a href="http://www.reddit.com/r/haskell/comments/zh3h6/announcing_esqueleto_a_typesafe_edsl_for_sql/">on
reddit</a> or send e-mails to Yesod's mailing list.  Or, even better,
give it a spin a let me know how it went!  Its
<a href="http://hackage.haskell.org/packages/archive/esqueleto/latest/doc/html/Database-Esqueleto.html">Haddock
documentation</a> should get you started.

Thanks for reading this rather long blog post! =)
