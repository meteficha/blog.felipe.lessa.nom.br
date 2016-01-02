---
title: Esqueleto now supports IN and EXISTS
---

Since version 0.2.9 that I've just released, <a
href="http://hackage.haskell.org/package/esqueleto">esqueleto</a>
supports `IN` and `EXISTS` operators (and their negated counterparts).
For example:

```haskell
select $
from $ \person -> do
where_ $ exists $
         from $ \post -> do
         where_ (post ^. BlogPostAuthorId ==. person ^. PersonId)
return person
```

Enjoy! =)

PS: I'll try to post more in the future, so keep tuned =).
