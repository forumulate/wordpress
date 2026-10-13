=== Forumulate ===
Contributors: forumulate
Tags: comments, forum, changelog, documentation, community
Requires at least: 6.1
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Forumulate comments, docs search, the changelog bell and blog, changelog or docs feeds in WordPress, as a block or shortcodes.

== Description ==

[Forumulate](https://forumulate.com) hosts your documentation, community forum, blog and changelog in one place. This plugin brings them into a WordPress site:

* **Comments.** One Forumulate thread per post, created the first time the post is visited. Optionally replaces WordPress comments on the post types you choose (classic and block themes). Visitors can read and, if your community space allows it, reply without leaving the page.
* **Docs search or ask.** A search box over your docs, or an "ask a question" box answered from them.
* **Changelog bell.** A "What's new" bell with an unread badge and a popover of recent releases.
* **Feeds.** A list of recent blog posts, changelog releases or updated docs pages, read from Forumulate's public API on the server and cached.

Everything is available as the **Forumulate** block and as shortcodes. Public embeds need only your organization and space ids; no account or token is needed for them.

= Shortcodes =

* `[forumulate_comments space="" topic="" theme="light"]`
* `[forumulate_widget space="" mode="search" placeholder=""]` (mode: `search` or `ask`)
* `[forumulate_changelog space=""]`
* `[forumulate_feed type="blog" space="" limit="5" excerpt="yes" title=""]` (type: `blog`, `changelog` or `docs`)

Every attribute is optional. `space` falls back to the space set in Settings → Forumulate.

= Filters =

* `forumulate_replace_comments` (bool, WP_Post): turn Forumulate comments off for a single post.

== External services ==

This plugin connects to the Forumulate service (https://forumulate.com), operated by Forumulate, to show your Forumulate content. It sends nothing until you configure an organization and space.

* **In visitors' browsers.** Pages with a comments, search or changelog embed load a script from `https://forumulate.com/api/embed/` (or the API address you set). That script requests your public content from Forumulate. The visitor's IP address and browser details reach Forumulate as with any web request. A visitor who posts a comment sends the name and text they enter. The changelog bell stores the time of the last release the visitor saw in their browser's local storage.
* **From your server.** Feeds are fetched by your WordPress server from Forumulate's public API and cached. "Check connection" calls Forumulate from your server. If you save an API token, your server sends it to Forumulate to verify it and to look up where a docs space is published; the token is never sent to visitors.

Terms of service: https://forumulate.com/policies/terms
Privacy policy: https://forumulate.com/policies/privacy

== Installation ==

1. Install and activate the plugin.
2. Go to **Settings → Forumulate**.
3. Enter your **organization id** and **default space id**. Both appear in your Forumulate console address (`/o/<organization id>/…`) and in each space's settings.
4. Optionally set separate spaces for comments and the changelog, and turn on **Replace WordPress comments** for the post types you want.
5. Click **Check connection**.
6. Add the **Forumulate** block to a page, or use a shortcode.

An API token is optional. To keep it out of the database entirely, define it in `wp-config.php`:

`define( 'FORUMULATE_TOKEN', 'ak_...' );`

A token saved on the settings page is stored encrypted with a key derived from your site's security keys, is not autoloaded, and is never shown again.

== Frequently Asked Questions ==

= Do I need a token? =

No. Comments, search, the changelog bell and blog and changelog feeds use public endpoints. A token only lets the plugin find your docs space's published address for docs-feed links (you can also type that address in) and verify the connection.

= What happens to my existing WordPress comments? =

Nothing. They stay in your database and come back if you turn the replacement off.

= Can visitors reply? =

If your Forumulate community space allows visitor replies, the comments embed shows a reply box. Otherwise it links to sign in on your forum.

= The feed shows old items. =

Feeds are cached for the number of minutes set under Feeds (10 by default). **Clear feed cache** on the settings page empties it.

== Screenshots ==

1. Settings → Forumulate.
2. The Forumulate block's settings in the editor.
3. Forumulate comments under a post.

== Changelog ==

= 1.0.0 =
* First release: settings page, the Forumulate block, four shortcodes, optional replacement of WordPress comments, cached blog, changelog and docs feeds.

== Upgrade Notice ==

= 1.0.0 =
First release.
