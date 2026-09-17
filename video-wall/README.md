# Offline Video Wall

A local PHP video library that scans multiple folders, displays a searchable video wall, streams files outside the web root, and opens videos in a theater player with a horizontal library strip.

## Install with XAMPP

1. Extract the `video-wall` folder into `B:\xampp\htdocs\`.
2. Start Apache from the XAMPP Control Panel.
3. Open `http://localhost/video-wall/`.
4. Use **Browse** to navigate the drives that Apache can read.
5. The complete path, such as `D:\Videos\Movies`, appears at the top of the browser. Select **Use this folder**, then **Build my video wall**.

Use **Folders** to change the locations or **Rescan** after adding videos. The `data` folder must be writable by PHP. Playback progress is remembered in the browser.

Videos remain in their original folders. The app saves only their full paths and streams the original files; it does not upload, move, or copy them. Wall thumbnails show a still frame and play a muted snippet while hovered. The normal player is intentionally limited in size, with a separate full-screen button and a video strip underneath.

While playing, use **Home** to return to the wall, **Next** to immediately play the following video, and **Rename** to save a custom display title without renaming the original file. The Folders settings include controls for automatic next-video playback and starting videos muted or unmuted.

## Optional AI video analysis

The Rename/Edit screen includes **Analyze with Ollama**, which extracts up to three temporary local still frames and sends them, together with the file name and creation-derived published date, to your local Ollama service. It returns review-only suggestions for a neutral title, actors, characters, productions, and categories. Nothing is saved until you choose **Use suggestions** and then **Save video**. Temporary frames are deleted after each request.

Install Ollama locally and download `qwen3-vl:2b`; it is the default local vision model for this feature. Optionally set `OLLAMA_VIDEO_ANALYSIS_MODEL` in the Apache/XAMPP environment to use a different installed vision-capable model. `OLLAMA_HOST` is optional and defaults to `http://127.0.0.1:11434`; `0.0.0.0:11434` is also safely treated as that same local address. For privacy, the app accepts only a local Ollama host. No API key is required, and frames never leave this computer.

For each video, the Windows filesystem creation date is used as the initial Published date when that field is blank. A date manually saved in Edit Video is never overwritten by later scans.

Create your own categories in Settings and assign one category to each video. Use the category selector above the wall together with search. All saved paths, catalog entries, display names, categories, FFmpeg settings, and player preferences are stored in `data/video-wall.sqlite`.

Version 1.7 uses custom one-category-per-video organization. Create or delete categories in Settings, then use **Rename** while playing to change both the display title and category. The scanner removes duplicate entries when overlapping library folders lead to the same original file, and SQLite prevents duplicate category names.

Removing a video is non-destructive: SQLite sets its `active` field to `0` and hides it while leaving the original file untouched. Open Settings and use **Restore** under Removed videos to set it back to `1`.

Each video also supports Actors and Characters metadata. Edit these fields through **Rename**; they are displayed beneath the player, stored in SQLite, preserved during rescans, and included in library searches.

Version 2.0 adds a separate Admin section for custom categories and removed-video restoration. **Rename** now opens a complete edit page with video name, category, actors, characters, publish date, and production/studio. All metadata is searchable and preserved in SQLite during rescans.

Version 2.1 adds an **Active in library** switch directly to the edit page. Turning it off saves all edits, sets the SQLite `active` field to `0`, and redirects to Admin without deleting the original video.

Duplicate detection compares files of the same size using a full SHA-256 hash. Rescanning or selecting **Check duplicates** in Admin keeps one copy active, deactivates matching copies, records the active original in `duplicate_of`, and labels duplicates in the Removed videos list.

Version 2.3 remembers the last category selected on the home video wall. Returning Home from the player, Edit Video, Admin, or another page restores that category; if it was deleted, the wall safely returns to All categories.

Version 2.4 lets each video belong to multiple categories. Existing category assignments migrate automatically. Open **Rename** on a playing video, check any number of categories on the Edit Video page, and save. Rescans preserve all assignments, and the remembered home category filter matches a video when any of its categories is selected.

Version 2.5 makes the Admin page ready for side-by-side cards.
The Removed Videos card stays in the left column.
leaves room for a new ccard on its right, and contains long video names and full paths without overflowing. Cards stack automatically on smaller screens.


## Supported files

The scanner recognizes MP4, M4V, WebM, OGV, OGG, MOV, AVI, MKV, MPEG, and MPG. Actual playback depends on the browser's installed codecs. MP4 using H.264 video and AAC audio has the widest browser compatibility.

## Video screenshots

The app can use the FFmpeg executable already included with Dowe LanCaster. Enter its complete `ffmpeg.exe` path in the setup or Folders window. The app also checks common Dowe LanCaster locations and PATH automatically. If FFmpeg cannot be found, the browser captures and caches still frames from formats it can play. Nothing is downloaded.

## Important

This app is intended for private, offline/local-network use. Do not expose it directly to the public internet because it provides access to the configured local video folders.
