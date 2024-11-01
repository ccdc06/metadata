# Next release when?

Every 2 weeks (maybe)[.](https://tenor.com/view/anime-whatever-kawaii-dont-worry-gif-12242087)

# What is your goal?

- Create an unofficial set of torrent files with all galleries uploaded to Koharu and HentaiNexus
- Exclude duplicates between these sources
- Split them into directories (collections) with up to 1000 files

# Do I need to download everything again to keep up with your release format?

If you did not change any folder names or edit the cbz files, you should be able to remove an older release from your torrent client (make sure you do it without deleting the existing files) and download a new version over it.

Your torrent client probably is smart enough to download only the missing and changed files while skipping those already downloaded, but it probably won't remove files that were replaced with a newer version. There is more information on how to deal wth that [here](#are-my-collections-complete-what-files-were-added-and-removed-on-the-last-update).

# Why some collections have less than 1000 files?

Some galleries may have been removed or replaced by new versions within the source site so I also remove them from the corresponding release.

Example: Anchira id 9703 (Night Play Roly-Poly) was replaced with Anchira id 12538 (same title, same content but better quality). Because of this, the Anchira 9001-10000 batch has one less file.

# What does \[v1\], \[v2\] mean in release names?

The source sites might remove galleries from id ranges that are in an already finished collection.

Removals often happen because a gallery was replaced with a new version. Removals and additions may also occur when a gallery is placed in the wrong collection.

Whenever a new revision of a collection is released, I will tag it with a new version number and hide/delete the older versions from nyaa.

This is also used to version control the latest batches which size is supposed to increase for each release.

# Can you add \<whatever\> to your release?

I do not manage individual galleries. If you want to see something in my releases, upload it directly to Koharu or HentaiNexus. When it gets published to at least one of them, it will be included in the next release.

# Are my collections complete? What files were added and removed on the last update?

I don't have a list of what files were replaced, added or removed but there is a [repo with a csv file](https://raw.githubusercontent.com/ccdc06/metadata/master/indexes/list.csv) that lists all the files in the latest releases.

You can write some code yourself to parse it and find out what's missing and delete what was replaced.

You can also use [this tool](https://github.com/ccdc06/tidy) that does exatctly that (Windows users will most likely want to download and run the `ccdc06-tidy-windows-amd64.exe` binary from the [latest release](https://github.com/ccdc06/tidy/releases/latest))

# Where are the Koharu/Anchira batches between id 15000 and 22000?

Koharu once uploaded a big batch of almost 9k files from [https://panda.chaika.moe](panda.chaika.moe). This batch is located between ids 14263 and 23248. Since this batch includes censored, non-english and poorly/machine translated galleries, this whole batch was skipped.

If there is something worth keeping in this range, I can include it manually on request (as long as it is oficially uncensored and has good quality english translation).

Occasionally more files from Chaika are uploaded in smaller batches and I'm filtering them manually to exclude anything including these tags:

```
japanese
full censorship
mosaic censorship
mosaics
text cleaned
rough translation
rough grammar
```

# Why are all your uploads anonymous?

Since nyaa doesn't allow new user registrations, I'm using a borrowed acocunt to upload my releases. The account owner asked me to always upload anonymously. This is also why I can't respond to comments there.

# I have a question, suggestion or complaint

Send a private message to ccdc06 on Discord or [mail me](mailto:ccdc06@proton.me).
