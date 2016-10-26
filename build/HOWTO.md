How to build a minified version of converse.js for this plugin
==============================================================

converse.js from git comes with all files pre-built. There is no longer any
 need to build the minified files yourself. Just copy the necessary resources
 from converse.js:
   ```
   cp devel/converse.js/dist/*.min.js js/
   cp devel/converse.js/dist/{locales,templates}.js js/
   cp devel/converse.js/css/*.min.css css/
   cp devel/converse.js/css/theme.css css/
   cp -r devel/converse.js/css/images css/
   cp -r devel/converse.js/fonticons .
   ```

[conversedocs]: http://conversejs.org/docs/html/index.html#development
