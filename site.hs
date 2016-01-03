{-# LANGUAGE OverloadedStrings #-}
import Data.List (isPrefixOf)
import Data.Monoid ((<>))
import Hakyll

main :: IO ()
main = hakyll $ do
  match "images/*" $ do
    route   idRoute
    compile copyFileCompiler

  match "css/*" $ do
    route   idRoute
    compile compressCssCompiler

  match (fromList ["about.md", "contact.md"]) $ do
    route   $ setExtension "html"
    compile $ pandocCompiler
      >>= loadAndApplyTemplate "templates/default.html" defaultContext
      >>= relativizeUrls

  match "posts/*" $ do
    route $ setExtension "html"
    compile $ pandocCompiler
      >>= saveSnapshot "content"
      >>= loadAndApplyTemplate "templates/post.html"  postCtx
      >>= loadAndApplyTemplate "templates/default.html" postCtx
      >>= relativizeUrls

  create ["archive.html"] $ do
    route idRoute
    compile $ do
      posts <- recentFirst =<< loadAllSnapshots "posts/*" "content"
      let archiveCtx =
            listField "posts" postCtx (return posts) <>
            constField "title" "Archives"            <>
            defaultContext

      makeItem ""
        >>= loadAndApplyTemplate "templates/archive.html" archiveCtx
        >>= loadAndApplyTemplate "templates/default.html" archiveCtx
        >>= relativizeUrls

  match "index.html" $ do
    route idRoute
    compile $ do
      posts <- fmap (take 7) . recentFirst =<< loadAllSnapshots "posts/*" "content"
      let indexCtx =
            listField "posts" postCtx (return posts) <>
            constField "title" "Latest posts"        <>
            defaultContext

      getResourceBody
        >>= applyAsTemplate indexCtx
        >>= loadAndApplyTemplate "templates/default.html" indexCtx
        >>= relativizeUrls

  match "templates/*" $
    compile templateCompiler

  create ["atom.xml"] $
    renderFeed renderAtom

  create ["rss.xml"] $
    renderFeed renderRss


postCtx :: Context String
postCtx =
  dateField "date" "%B %e, %Y"                 <>
  mapContext makePreview (bodyField "preview") <>
  mapContext makeTeaser  (bodyField "teaser")  <>
  defaultContext
  where
    makePreview = (++ "...") . unwords . take 40 . words . stripTags
    makeTeaser = demoteHeaders . takeUntilMore
    takeUntilMore xss@(x:xs) = if "<!--more-->" `isPrefixOf` xss then "" else x : takeUntilMore xs
    takeUntilMore ""         = "" :: String


----------------------------------------------------------------------


renderFeed :: (FeedConfiguration -> Context String -> [Item String] -> Compiler (Item String)) -> Rules ()
renderFeed renderer = do
  route idRoute
  compile $ do
    let feedCtx = postCtx <> bodyField "description"
    posts <- recentFirst =<< loadAllSnapshots "posts/*" "content"
    renderer feedConf feedCtx (take 10 posts)

feedConf :: FeedConfiguration
feedConf =
  FeedConfiguration
    { feedTitle       = "Felipe Lessa"
    , feedDescription = "Some random blog posts"
    , feedAuthorName  = "Felipe Almeida Lessa"
    , feedAuthorEmail = "felipe.lessa@gmail.com"
    , feedRoot        = "http://blog.felipe.lessa.nom.br"
    }
