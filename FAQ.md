# Next release when?

[TBA](https://tenor.com/view/anime-whatever-kawaii-dont-worry-gif-12242087)

# What is your goal?

- Create a set of torrent files with all galleries uploaded to Koharu and HentaiNexus
- Exclude duplicates between these sources (giving priority to Koharu versions over HentaiNexus versions)
- Split them into directories (collections) with up to 1000 files

# Do I need to download everything again to keep up with your release format?

If you did not change any folder names or edit the cbz files, you should be able to remove an older release from your torrent client (make sure you do it without deleting the existing files) and download a new version over it.

Your torrent client probably is smart enough to download only the missing and changed files while skipping those already downloaded.

# Why some collections have less than 1000 files?

Some galleries may have been removed or replaced by new versions within the source site so I also remove them from the corresponding release.

Example: Anchira id 9703 (Night Play Roly-Poly) was replaced with Anchira id 12538 (same title, same content but better quality). Because of this, the Anchira 9001-10000 batch has one less file.

# What does \[v1\], \[v2\] mean in release names?

The source sites might remove galleries from id ranges that are in an already finished collection.

Removals often happen because a gallery was replaced with a new version. Removals and additions may also occur when a gallery is placed in the wrong collection.

Whenever a new revision of a collection is released, I will tag it with a new version number and hide/delete the older versions from nyaa.

# What does "ongoing" and "X% complete" mean in release names?

Collections are split by their numeric id numbers. The most recent release from each source will always be a partial "ongoing" release.

For example, if the most recent id of the download source is `12665`, the current ongoing release will be `example.com_12001-13000`, and the completion percentage will be 66% (12**66**5).

# Can you add \<whatever\> to your release?

I do not manage individual galleries. If you want to see something in my releases, upload it directly to Koharu or HentaiNexus. When it gets published to at least one of them, it will be included in the next release.

# Are my collections complete? What files were added and removed on the last update?

I don't have a list of what files were replaced, added or removed but there is a [repo with a csv file](https://raw.githubusercontent.com/ccdc06/metadata/master/indexes/list.csv) that lists all the files in the latest releases.

You can write some code yourself to parse it and find out what's missing and delete what was replaced.

You can also use [this tool](https://github.com/ccdc06/tidy) that does exatctly that (Windows users will most likely want to download and run the `ccdc06-tidy-windows-amd64.exe` binary from the [latest release](https://github.com/ccdc06/tidy/releases/latest))

# Where is the 17001-18000 HentaiNexus batch?

To make managing duplicate galleries easier, on 26/jul/2024 I decided to replace all duplicate galleries from Nexus with their respective versions from Koharu.

As of the time I wrote this, 100% of the galleries in the 17001-18000 id range had a Koharu version available, so the whole folder was removed.

# Why are all your uploads anonymous?

Since nyaa doesn't allow new user registrations, I'm using a borrowed acocunt to upload my releases. The account owner asked me to always upload anonymously. This is also why I can't respond to comments there.

# I have a question, suggestion or complaint

Send a private message to ccdc06 on Discord or [mail me](mailto:ccdc06@proton.me).
