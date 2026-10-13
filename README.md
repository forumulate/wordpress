# Forumulate for WordPress

Embed [Forumulate](https://forumulate.com) in a WordPress site: comments (optionally
replacing WordPress comments on the post types you choose), the docs search / ask
widget, the "What's new" changelog bell, and cached blog, changelog or docs feeds.
Everything is available as the **Forumulate** block and as shortcodes.

Version 1.0.0. Requires WordPress 6.1+ and PHP 7.4+.

## Install

Download this repository as a ZIP (or a release), then **Plugins → Add New →
Upload Plugin**. Activate it and open **Settings → Forumulate**.

## Quickstart

1. Enter your organization id and default space id (from your console address,
   `/o/<organization id>/…`, and each space's settings).
2. Optionally turn on **Replace WordPress comments** and pick post types.
3. Add the **Forumulate** block, or a shortcode:

```text
[forumulate_comments]
[forumulate_widget mode="search"]
[forumulate_changelog]
[forumulate_feed type="blog" limit="5"]
```

Public embeds need no token. An optional API token is used only server-side, is
stored encrypted (or set `FORUMULATE_TOKEN` in `wp-config.php`), and is never
sent to visitors. `readme.txt` has the full description, the external-services
disclosure and the FAQ.

## Development

```sh
node scripts/lint.mjs   # php -l every file
phpcs                   # WordPress Coding Standards, configured by phpcs.xml.dist
```

`.distignore` lists what is left out of the plugin ZIP.

## License

GPLv2 or later, as WordPress.org requires. See [LICENSE.txt](LICENSE.txt).

---

## About this repository

This repository is a public mirror, refreshed on each Forumulate release
(one commit per release). Pull requests are welcome; accepted changes are
applied upstream and arrive here with the next release.

- API reference: https://forumulate.com/reference
- Guides: https://forumulate.com/guides
- Community and support: https://forumulate.com/community
- Source: https://github.com/forumulate/wordpress
