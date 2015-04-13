Extend Link Syntax plugin
=========================
Extend DokuWiki Link syntax to 
* allow formatting the title text of the link
*  specify a frame where the linked page/media to be opened, eg. in a same tab, new tab or popup window

Syntax Usage/Example
--------------------

### Formatting the link text###

Put `!>` after `[[` or `{{` to enable extended link syntax. Then you may use bold or itlic formatting for the link text. In case of the interwiki, just put "<code>!  </code>" – one white space required after `!` – for interwiki links that already have `>` after the interwiki shortcut name. Any formatting syntax, such as typography plugin, will be applicable for the link text. You may also use image as a link title. 

```
[[!>ns:page|**internal** link title]]                internal page
[[!>http://example.com|**external** link title]]     external url
[[! doku>plugin:extlink|**interwiki** title]]        interwiki
{{!>:ns:example.pdf|**media** title}}                media file
```


### Specify how the linked page will be opened ###

In order to specify a frame where the linked page/media to be opened, put a framename after `!` without space. The framename corresponds to the target attribute of the link (\<a\> tag of xhtml). This means you can override the default target config settings for different link types. 

```
[[!_blank >ns:page|**internal** link title]]         open in a new tab (or window)
[[!_self >ns:page|**internal** link title]]          open in same tab
[[!framename >ns:page|**internal** link title]]      open in the named frame
[[!! w640 h400 >ns:page|**internal** link title]]    open in a popup window (W600xH400) 
```

### Prepare named iframe in your page ###
This plugin includes **iframe** syntax conponent that embed inline frames in your wiki page. The named frame is available as the target of the extended link syntax where the linked page to be shown. 

```
{{iframe name="framename" w720px h600px >ns:iframe:default}}
```

----
Licensed under the GNU Public License (GPL) version 2


(c) 2015 Satoshi Sahara \<sahara.satoshi@gmail.com>
