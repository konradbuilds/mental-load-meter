=== Mental Load Meter ===
Contributors: konradsroka
Tags: reading time, readability, accessibility, editor, usability
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add reading time and a 1 to 5 mental load score to your posts, so readers know what they are walking into.

== Description ==

This plugin adds reading time to your posts. It is estimated for you, and you can override it when the estimate is wrong.

It also adds something reading time cannot tell anyone: a 1 to 5 score for how heavy the post is. You set it yourself.

Minutes measure length. The score measures depth. A seven-minute news piece and a seven-minute spec are not the same ask, and right now readers find that out the hard way.

Telling them up front is just kind. They can read it now, make a coffee first, or save it for later.

**In the editor**

* Reading time — estimated, override it if you want.
* Mental load — pick one of five.

**On the front end**

One line above the post:

`● ● ● ○ ○   Needs focus · 7 min read`

**The five levels**

1. Light, skim it
2. Easy read
3. Needs focus
4. Heavy, grab a coffee
5. Dense, you'll reread parts

The dots are decoration. The wording carries the meaning, so colour is never the only signal.

No settings page. Deactivate it and your content renders exactly as before.

= Filters =

* `mlm_words_per_minute` — reading speed for the estimate. Default 200.
* `mlm_post_types` — where it applies. Default posts and pages.
* `mlm_auto_output` — set false to place it yourself.
* `mlm_output_html` — replace the markup.

= Shortcode =

`[mental_load]`

== Installation ==

1. Install and activate.
2. Open a post. The "Mental load" panel is in the editor sidebar.

== Frequently Asked Questions ==

= Does it work with the classic editor? =

No. Block editor only.

= Why five levels and not ten? =

Nobody can tell a 6 from a 7. Each level has words, so it means the same thing to everyone.

= Does it score the post automatically? =

No. You wrote it, you know. A guess would be worse.

= Does it output schema markup? =

No. Your SEO plugin already outputs article schema and a second block risks conflicts.

== Changelog ==

= 1.0.0 =
* First release.
