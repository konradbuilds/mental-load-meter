# Mental Load Meter

A WordPress plugin. Adds reading time and a 1 to 5 mental load score to your posts, so readers know what they are walking into.

Reading time is estimated for you, and you can override it. The score you set yourself.

Minutes measure length. The score measures depth. A seven-minute news piece and a seven-minute spec are not the same ask. Telling people which one they are about to get is just kind — they can read it now, make a coffee first, or save it for later.

## What readers see

One line above the post:

```
● ● ● ○ ○   Needs focus · 7 min read
```

The dots are decoration, marked `aria-hidden`. The wording carries the meaning, so colour is never the only signal.

## The five levels

| Level | Label |
|---|---|
| 1 | Light, skim it |
| 2 | Easy read |
| 3 | Needs focus |
| 4 | Heavy, grab a coffee |
| 5 | Dense, you'll reread parts |

## Install

Download the latest zip from Releases, then Plugins › Add New › Upload Plugin.

For development, clone into `wp-content/plugins/mental-load-meter`.

## Requirements

WordPress 6.0, PHP 7.4. Block editor only.

## Filters

| Filter | Default | Does |
|---|---|---|
| `mlm_words_per_minute` | `200` | Reading speed for the estimate |
| `mlm_post_types` | `['post','page']` | Where it applies |
| `mlm_auto_output` | `true` | Set false to place it yourself |
| `mlm_output_html` | markup | Replace the markup |

Shortcode: `[mental_load]`

## Build

None. No npm, no compile step. The editor panel is plain JS through the `wp.*` globals.

To build a release zip:

```bash
rsync -a --exclude-from=.distignore ./ /tmp/mental-load-meter/
cd /tmp && zip -r mental-load-meter.zip mental-load-meter
```

## No lock-in

Both values are plain post meta (`_mlm_level`, `_mlm_minutes`). Deactivate the plugin and your content renders exactly as before.

## Licence

GPL-2.0-or-later. Konrad Sroka — [konradbuilds.github.io](https://konradbuilds.github.io/)  
Plugin: [github.com/konradbuilds/mental-load-meter](https://github.com/konradbuilds/mental-load-meter)
