# Shelfmark plugin for Organizr

Adds [Shelfmark](https://github.com/calibrain/shelfmark) to the Organizr homepage:

- a **Downloads** tab (active and recent downloads, with progress) and a **Requests** tab,
- shown as its own panel, or as a tab in the combined **Downloader** next to NZBGet, Sonarr and the rest,
- a count badge: active downloads, or pending requests when there are none.

It shows what Shelfmark's own page shows, including the items you have dismissed.

## Install

1. In Organizr, open **Settings > Plugins**, and in the **Marketplace** settings add this repo under **External Marketplace Repo**
   (GitHub repos only):
   `https://github.com/louisefindlay23/organizr-plugin-shelfmark`
2. Install **Shelfmark** from the marketplace, then enable it under **Settings > Plugins**.
3. Fill in the settings (below). For the Downloader tab, the **Downloader** item must be on your homepage.

Requires Organizr 2.1.0 or newer. The plugin installs to `data/plugins/shelfmark/`.

## Settings

| Setting | Notes |
|---|---|
| Shelfmark URL | For example `http://192.168.1.80:8086`. |
| Admin API Key | Shelfmark's `SHELFMARK_API_KEY`. See the warning below. |
| Homepage Title | Title of the standalone panel. |
| Combine into Downloader | Show as a Downloader tab instead of a panel. |
| Show request type | Show the ebook/audiobook column in the Requests tab. |
| Minimum Authentication | Lowest Organizr group that can see it. |

## Security

The plugin reads Shelfmark's `GET /api/activity/snapshot`, which needs an **admin** API key. That key can change Shelfmark settings,
including post-download scripts, so treat it like a root password. Organizr stores it in plain text in its config, as it does
for its other download clients.

The key stays on the Organizr server. The browser only receives trimmed display fields (no usernames, notes or file paths),
and links are removed from status messages. The plugin only sends GET requests. Titles are HTML-escaped when shown.

## Credits

The logo is Shelfmark's.
