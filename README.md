# Super Simple Backup

This is a backup utility, which is born out of necessity. For a project on a shared hosting environment, I had to implement a backup system.
As there were only web crons available to trigger the backup routine periodically, this script was born.

The current available client system does three things:

* Create a backup of one single database
* Create a backup of a selection of files and directories
* Upload this backup to an FTP host, if enabled.

So no delta backups, just grab all the files, push them to the a remote server and hopefully you have some kind of cleanup in place to remove old backups. Luckily the backups are timestamped!

## License

The MIT License (MIT)

Copyright (c) 2018 David Bohn

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
