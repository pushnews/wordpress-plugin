Contributing
===

- SVN Repository: [http://plugins.svn.wordpress.org/pushnews/](http://plugins.svn.wordpress.org/pushnews/)
- Plugin Page: [https://wordpress.org/plugins/pushnews/](https://wordpress.org/plugins/pushnews/)


# Updating the plugin

You should clone our Github repo as this is our main CVS.

All changes should be performed taking into consideration:

- Minimum PHP Version: 5.3
- Wordpress Plugin Handbook: [https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- Wordpress Best Practices: [https://developer.wordpress.org/plugins/the-basics/best-practices/](https://developer.wordpress.org/plugins/the-basics/best-practices/)


After your branch is accepted into master, you need to make a copy into SVN - check the next section.

# Translations

Extract all translations with:

`$ wp i18n make-pot . languages/pushnews.pot`

This will generate a `languages/pushnews.pot` file.

Now open the `.po` files (`pt_PT.po` and `pt_BR.po`), in [Poedit](https://poedit.net/), go to "Translation > Update from POT file..." and fill in the missing translations.

When you hit save, an updated `pt_PT.mo` and `pt_BR.mo` files will be generated.


# Working with SVN

Wordpress plugins rely on good old SVN repositories so here's a list of useful commands.


**Cloning SVN repository**

`$ svn co http://plugins.svn.wordpress.org/pushnews/` 


**Updating SVN's trunk (aka master) branch**

`$ cp -R /path/to/pushnews-wordpress-plugin-git-clone/* /path/to/pushnews-wordpress-plugin-svn-clone/trunk/`

**Add files fo SVN**

`$ svn add file1 file2 ...`

**Commit (aka push) added files into remote SVN repo**

`$ svn ci -m "comment here, usually just the tag name"`

(it should ask you for your SVN username and password)

**Releasing a new Tag**

After committing to trunk, all you have to do is copy it's contents to a new tag path.
 
Imagine you are releasing tag `1.3.0`, here's what you should do:

```
$ svn cp trunk tags/1.3.0
$ svn ci -m "1.3.0"
```
